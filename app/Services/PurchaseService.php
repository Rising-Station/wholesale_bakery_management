<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\StockLocationType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Get paginated filtered purchase invoices.
     */
    public function getFilteredPurchases(
        ?int $supplierId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): LengthAwarePaginator {
        $query = Purchase::with(['supplier', 'items.product', 'creator:id,name'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('purchase_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search): void {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($dateFrom) {
            $query->whereDate('purchase_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('purchase_date', '<=', $dateTo);
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * Create a new purchase invoice, auto-increment stock, and log audit trail.
     */
    public function createPurchase(array $data, ?int $userId = null): Purchase
    {
        return DB::transaction(function () use ($data, $userId): Purchase {
            $user = $userId ?? Auth::id();
            $date = Carbon::parse($data['purchase_date']);
            $countToday = Purchase::whereDate('purchase_date', $data['purchase_date'])->count() + 1;
            $purchaseNo = sprintf('PUR-%s-%04d', $date->format('Ymd'), $countToday);

            // Compute line totals and subtotal
            $subtotal = 0.00;
            $itemsToProcess = [];

            foreach ($data['items'] as $itemData) {
                $qty = (float) $itemData['quantity'];
                $cost = (float) $itemData['unit_cost'];
                $lineTotal = round($qty * $cost, 2);
                $subtotal += $lineTotal;

                $itemsToProcess[] = [
                    'product_id' => $itemData['product_id'],
                    'packaging_type' => $itemData['packaging_type'] ?? 'PIECES',
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'line_total' => $lineTotal,
                ];
            }

            $discount = (float) ($data['discount'] ?? 0.00);
            $grandTotal = max(0.00, round($subtotal - $discount, 2));
            $paidAmount = min($grandTotal, (float) ($data['paid_amount'] ?? 0.00));
            $dueAmount = max(0.00, round($grandTotal - $paidAmount, 2));

            $status = match (true) {
                $dueAmount <= 0.00 => PurchaseStatus::Received,
                $paidAmount > 0.00 => PurchaseStatus::Partial,
                default => PurchaseStatus::Received,
            };

            $purchase = Purchase::create([
                'purchase_no' => $purchaseNo,
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user,
            ]);

            // Save line items and immediately increment central warehouse stock
            foreach ($itemsToProcess as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'packaging_type' => $item['packaging_type'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['line_total'],
                ]);

                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $balanceBefore = (float) $product->warehouse_stock;
                $balanceAfter = $balanceBefore + $item['quantity'];

                $product->warehouse_stock = $balanceAfter;
                // Also update default cost_price to match latest procurement cost
                $product->cost_price = $item['unit_cost'];
                $product->save();

                $this->stockService->recordMovement(
                    product: $product,
                    type: StockMovementType::PurchaseIn,
                    source: StockLocationType::CentralWarehouse,
                    destination: StockLocationType::CentralWarehouse,
                    quantity: $item['quantity'],
                    balanceBefore: $balanceBefore,
                    balanceAfter: $balanceAfter,
                    referenceType: 'Purchase',
                    referenceId: $purchase->id,
                    remarks: "Procured from supplier via Purchase #{$purchaseNo}",
                    userId: $user
                );
            }

            // Record immediate initial payment if provided
            if ($paidAmount > 0 && ! empty($data['payment_method'])) {
                SupplierPayment::create([
                    'supplier_id' => $data['supplier_id'],
                    'purchase_id' => $purchase->id,
                    'payment_method' => $data['payment_method'],
                    'amount' => $paidAmount,
                    'payment_date' => $data['purchase_date'],
                    'transaction_ref' => "Initial Payment for {$purchaseNo}",
                    'notes' => 'Paid at time of purchase bill creation',
                    'created_by' => $user,
                ]);
            }

            // Atomically update supplier's current due balance
            /** @var Supplier $supplier */
            $supplier = Supplier::lockForUpdate()->findOrFail($data['supplier_id']);
            $supplier->current_due += $dueAmount;
            $supplier->save();

            return $purchase;
        });
    }

    /**
     * Get purchase with all relations loaded for invoice view and print.
     */
    public function getPurchaseWithDetails(Purchase $purchase): Purchase
    {
        return $purchase->load([
            'supplier',
            'items.product',
            'creator:id,name',
            'payments',
        ]);
    }
}
