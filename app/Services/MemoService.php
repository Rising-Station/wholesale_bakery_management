<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\MemoStatus;
use App\Enums\ReturnType;
use App\Enums\StockLocationType;
use App\Enums\StockMovementType;
use App\Models\DamageReturn;
use App\Models\DamageScrapInventory;
use App\Models\Product;
use App\Models\RetailerPayment;
use App\Models\RetailerShop;
use App\Models\SalesMemo;
use App\Models\SalesMemoItem;
use App\Models\VehicleTripItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemoService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Get paginated filtered sales memos.
     */
    public function getFilteredMemos(
        ?int $roadId = null,
        ?int $retailerId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): LengthAwarePaginator {
        $query = SalesMemo::with(['retailer', 'road', 'creator:id,name'])
            ->latest('memo_date');

        if ($roadId) {
            $query->where('road_id', $roadId);
        }

        if ($retailerId) {
            $query->where('retailer_id', $retailerId);
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('memo_no', 'like', "%{$search}%")
                    ->orWhereHas('retailer', function ($rq) use ($search): void {
                        $rq->where('shop_name', 'like', "%{$search}%")
                            ->orWhere('owner_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($dateFrom) {
            $query->whereDate('memo_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('memo_date', '<=', $dateTo);
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * Create a new sales memo with dual-ledger due update, vehicle stock deduction, and returns processing.
     */
    public function createMemo(array $data, ?int $userId = null): SalesMemo
    {
        return DB::transaction(function () use ($data, $userId): SalesMemo {
            $user = $userId ?? Auth::id();
            $date = Carbon::parse($data['memo_date']);
            $countToday = SalesMemo::whereDate('memo_date', $data['memo_date'])->count() + 1;
            $memoNo = sprintf('MEMO-%s-%04d', $date->format('Ymd'), $countToday);

            /** @var RetailerShop $retailer */
            $retailer = RetailerShop::lockForUpdate()->findOrFail($data['retailer_id']);
            $previousDue = (float) $retailer->current_due;

            // 1. Verify stock availability & calculate sold items subtotal (0 stock cannot be sold)
            $subtotal = 0.00;
            $itemsToProcess = [];

            foreach ($data['items'] as $itemData) {
                $qty = (float) $itemData['quantity'];
                $price = (float) $itemData['unit_price'];
                $lineTotal = round($qty * $price, 2);
                $subtotal += $lineTotal;

                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

                if (! empty($data['vehicle_trip_id'])) {
                    $tripItem = VehicleTripItem::where('vehicle_trip_id', $data['vehicle_trip_id'])
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $tripItem) {
                        throw new Exception("Product '{$product->name}' (SKU: {$product->code}) was not loaded on this vehicle trip. 0 stock cannot be sold.");
                    }

                    $availableOnVehicle = round(
                        ((float) $tripItem->loaded_quantity + (float) $tripItem->exchanged_quantity)
                        - ((float) $tripItem->sold_quantity + (float) $tripItem->damaged_quantity),
                        2
                    );

                    if ($availableOnVehicle <= 0.0) {
                        throw new Exception("Cannot sell '{$product->name}' (SKU: {$product->code}): Available vehicle stock is 0. 0 stock cannot be sold.");
                    }

                    if ($availableOnVehicle < $qty) {
                        throw new Exception("Insufficient vehicle stock for '{$product->name}' (SKU: {$product->code}). Available on vehicle: {$availableOnVehicle}, Requested: {$qty}.");
                    }
                } else {
                    if ((float) $product->warehouse_stock <= 0.0) {
                        throw new Exception("Cannot sell '{$product->name}' (SKU: {$product->code}) from Central Warehouse: Stock is 0. Please purchase stock from a manufacturing supplier first before selling.");
                    }

                    if ((float) $product->warehouse_stock < $qty) {
                        throw new Exception("Insufficient Central Warehouse stock for '{$product->name}' (SKU: {$product->code}). Available: {$product->warehouse_stock}, Requested: {$qty}. Purchase additional stock from supplier first.");
                    }
                }

                $itemsToProcess[] = [
                    'product_id' => $itemData['product_id'],
                    'packaging_type' => $itemData['packaging_type'] ?? ($product->packaging_type?->value ?? 'PIECES'),
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ];
            }

            // 2. Process Returns (Scenario 1: Damage & Scenario 2: Exchange)
            $damageDeduction = 0.00;
            $exchangeDeduction = 0.00;
            $returnsToProcess = [];

            if (! empty($data['returns'])) {
                foreach ($data['returns'] as $ret) {
                    $retQty = (float) $ret['quantity'];
                    $rate = (float) $ret['unit_deduction_rate'];
                    $retTotal = round($retQty * $rate, 2);
                    $returnType = $ret['return_type'];

                    if ($returnType === ReturnType::Damage->value || $returnType === 'DAMAGE') {
                        $damageDeduction += $retTotal;
                        $affectsStock = false;
                    } else {
                        $exchangeDeduction += $retTotal;
                        $affectsStock = true;
                    }

                    $returnsToProcess[] = [
                        'product_id' => $ret['product_id'],
                        'return_type' => $returnType,
                        'damage_type' => $ret['damage_type'] ?? 'DAMAGED',
                        'quantity' => $retQty,
                        'unit_deduction_rate' => $rate,
                        'total_deduction_amount' => $retTotal,
                        'affects_sellable_stock' => $affectsStock,
                        'notes' => $ret['notes'] ?? null,
                    ];
                }
            }

            $customDeduction = (float) ($data['custom_deduction'] ?? 0.00);
            $grandTotal = max(0.00, round($subtotal - $damageDeduction - $exchangeDeduction - $customDeduction, 2));
            $totalNetPayable = round($previousDue + $grandTotal, 2);
            $paidAmount = min($totalNetPayable, (float) ($data['paid_amount'] ?? 0.00));
            $dueAmount = max(0.00, round($totalNetPayable - $paidAmount, 2));

            $status = match (true) {
                $dueAmount <= 0.00 => MemoStatus::Paid,
                $paidAmount > 0.00 => MemoStatus::Partial,
                default => MemoStatus::Due,
            };

            // 3. Create Sales Memo
            $memo = SalesMemo::create([
                'memo_no' => $memoNo,
                'retailer_id' => $retailer->id,
                'road_id' => $data['road_id'],
                'vehicle_trip_id' => $data['vehicle_trip_id'] ?? null,
                'memo_date' => $data['memo_date'],
                'subtotal' => $subtotal,
                'damage_deduction' => $damageDeduction,
                'exchange_deduction' => $exchangeDeduction,
                'custom_deduction' => $customDeduction,
                'grand_total' => $grandTotal,
                'previous_due' => $previousDue,
                'total_net_payable' => $totalNetPayable,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'memo_status' => $status,
                'delivery_status' => DeliveryStatus::Delivered,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user,
            ]);

            // 4. Save sold items and deduct from vehicle trip stock or central stock
            foreach ($itemsToProcess as $item) {
                SalesMemoItem::create([
                    'sales_memo_id' => $memo->id,
                    'product_id' => $item['product_id'],
                    'packaging_type' => $item['packaging_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);

                if (! empty($data['vehicle_trip_id'])) {
                    $tripItem = VehicleTripItem::where('vehicle_trip_id', $data['vehicle_trip_id'])
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if ($tripItem) {
                        $tripItem->sold_quantity += $item['quantity'];
                        $tripItem->save();
                    }
                } else {
                    /** @var Product $product */
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    $balanceBefore = (float) $product->warehouse_stock;
                    $balanceAfter = $balanceBefore - $item['quantity'];
                    $product->warehouse_stock = $balanceAfter;
                    $product->save();

                    $this->stockService->recordMovement(
                        product: $product,
                        type: StockMovementType::DirectSale,
                        source: StockLocationType::CentralWarehouse,
                        destination: StockLocationType::CentralWarehouse,
                        quantity: $item['quantity'],
                        balanceBefore: $balanceBefore,
                        balanceAfter: $balanceAfter,
                        referenceType: 'SalesMemo',
                        referenceId: $memo->id,
                        remarks: "Direct Sale via Memo #{$memoNo}",
                        userId: $user
                    );
                }
            }

            // 5. Save Returns and handle stock routing
            foreach ($returnsToProcess as $ret) {
                DamageReturn::create([
                    'sales_memo_id' => $memo->id,
                    'retailer_id' => $retailer->id,
                    'product_id' => $ret['product_id'],
                    'vehicle_trip_id' => $data['vehicle_trip_id'] ?? null,
                    'return_type' => $ret['return_type'],
                    'damage_type' => $ret['damage_type'],
                    'quantity' => $ret['quantity'],
                    'unit_deduction_rate' => $ret['unit_deduction_rate'],
                    'total_deduction_amount' => $ret['total_deduction_amount'],
                    'affects_sellable_stock' => $ret['affects_sellable_stock'],
                    'is_custom_amount' => false,
                    'notes' => $ret['notes'],
                    'approved_by' => $user,
                ]);

                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($ret['product_id']);

                if ($ret['return_type'] === ReturnType::Damage->value || $ret['return_type'] === 'DAMAGE') {
                    // Scenario 1: Damage Return -> Route to Scrap Quarantine
                    DamageScrapInventory::create([
                        'product_id' => $product->id,
                        'retailer_id' => $retailer->id,
                        'road_id' => $data['road_id'],
                        'damage_type' => $ret['damage_type'],
                        'quantity' => $ret['quantity'],
                        'status' => 'QUARANTINED',
                        'disposal_notes' => "Damage return from memo #{$memoNo}",
                        'created_by' => $user,
                    ]);

                    if (! empty($data['vehicle_trip_id'])) {
                        $tripItem = VehicleTripItem::where('vehicle_trip_id', $data['vehicle_trip_id'])
                            ->where('product_id', $product->id)
                            ->first();
                        if ($tripItem) {
                            $tripItem->damaged_quantity += $ret['quantity'];
                            $tripItem->save();
                        }
                    }
                } else {
                    // Scenario 2: Product Exchange -> Increases sellable stock!
                    if (! empty($data['vehicle_trip_id'])) {
                        $tripItem = VehicleTripItem::where('vehicle_trip_id', $data['vehicle_trip_id'])
                            ->where('product_id', $product->id)
                            ->first();
                        if ($tripItem) {
                            $tripItem->exchanged_quantity += $ret['quantity'];
                            $tripItem->save();
                        }
                    } else {
                        $balanceBefore = (float) $product->warehouse_stock;
                        $balanceAfter = $balanceBefore + $ret['quantity'];
                        $product->warehouse_stock = $balanceAfter;
                        $product->save();

                        $this->stockService->recordMovement(
                            product: $product,
                            type: StockMovementType::ExchangeReturn,
                            source: StockLocationType::CentralWarehouse,
                            destination: StockLocationType::CentralWarehouse,
                            quantity: $ret['quantity'],
                            balanceBefore: $balanceBefore,
                            balanceAfter: $balanceAfter,
                            referenceType: 'SalesMemo',
                            referenceId: $memo->id,
                            remarks: "Exchanged good product returned from memo #{$memoNo}",
                            userId: $user
                        );
                    }
                }
            }

            // 6. Record immediate payment if paid
            if ($paidAmount > 0 && ! empty($data['payment_method'])) {
                RetailerPayment::create([
                    'retailer_id' => $retailer->id,
                    'sales_memo_id' => $memo->id,
                    'road_id' => $data['road_id'],
                    'vehicle_trip_id' => $data['vehicle_trip_id'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'amount' => $paidAmount,
                    'payment_date' => $data['memo_date'],
                    'transaction_ref' => "Payment for {$memoNo}",
                    'notes' => 'Collected upon memo delivery',
                    'collected_by' => $user,
                ]);
            }

            // 7. Atomically update retailer's live due balance
            $retailer->current_due = $dueAmount;
            $retailer->save();

            return $memo;
        });
    }

    /**
     * Record a direct due payment collection from a retailer.
     */
    public function collectDuePayment(array $data, ?int $userId = null): RetailerPayment
    {
        return DB::transaction(function () use ($data, $userId): RetailerPayment {
            $user = $userId ?? Auth::id();
            /** @var RetailerShop $retailer */
            $retailer = RetailerShop::lockForUpdate()->findOrFail($data['retailer_id']);
            $amount = (float) $data['amount'];

            $payment = RetailerPayment::create([
                'retailer_id' => $retailer->id,
                'sales_memo_id' => $data['sales_memo_id'] ?? null,
                'road_id' => $data['road_id'],
                'vehicle_trip_id' => $data['vehicle_trip_id'] ?? null,
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'notes' => $data['notes'] ?? null,
                'collected_by' => $user,
            ]);

            $retailer->current_due = max(0, (float) $retailer->current_due - $amount);
            $retailer->save();

            return $payment;
        });
    }

    /**
     * Get detailed memo profile with items, returns, payments, and road.
     */
    public function getMemoWithDetails(SalesMemo $memo): SalesMemo
    {
        return $memo->load([
            'retailer',
            'road',
            'vehicleTrip',
            'items.product',
            'returns.product',
            'payments',
            'creator:id,name',
        ]);
    }
}
