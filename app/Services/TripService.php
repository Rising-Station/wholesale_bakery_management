<?php

namespace App\Services;

use App\Enums\StockLocationType;
use App\Enums\StockMovementType;
use App\Enums\TripStatus;
use App\Models\Product;
use App\Models\VehicleTrip;
use App\Models\VehicleTripItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TripService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Get paginated filtered vehicle trips.
     */
    public function getFilteredTrips(
        ?int $roadId = null,
        ?string $status = null,
        ?string $date = null
    ): LengthAwarePaginator {
        $query = VehicleTrip::with(['road', 'driver', 'creator:id,name'])
            ->withCount('items')
            ->latest('trip_date');

        if ($roadId) {
            $query->where('road_id', $roadId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($date) {
            $query->whereDate('trip_date', $date);
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * Create a new trip, validate available stock, transfer stock to vehicle, and log audit ledger.
     */
    public function createTrip(array $data, ?int $userId = null): VehicleTrip
    {
        return DB::transaction(function () use ($data, $userId): VehicleTrip {
            $user = $userId ?? Auth::id();
            $date = Carbon::parse($data['trip_date']);
            $countToday = VehicleTrip::whereDate('trip_date', $data['trip_date'])->count() + 1;
            $tripNo = sprintf('TRIP-%s-%04d', $date->format('Ymd'), $countToday);

            // 1. Verify stock availability for all products before creating (0 stock cannot be loaded)
            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $loadedQty = (float) $item['loaded_quantity'];

                if ((float) $product->warehouse_stock <= 0.0) {
                    throw new Exception("Cannot load '{$product->name}' (SKU: {$product->code}) onto vehicle: Central Warehouse stock is 0. Please purchase stock from a manufacturing supplier first before loading.");
                }

                if ((float) $product->warehouse_stock < $loadedQty) {
                    throw new Exception("Insufficient stock in Central Warehouse for '{$product->name}' (SKU: {$product->code}). Available: {$product->warehouse_stock}, Requested: {$loadedQty}. Please purchase additional stock from supplier first.");
                }
            }

            // 2. Create the vehicle trip record
            $trip = VehicleTrip::create([
                'trip_no' => $tripNo,
                'road_id' => $data['road_id'],
                'trip_date' => $data['trip_date'],
                'vehicle_number' => $data['vehicle_number'],
                'driver_id' => $data['driver_id'] ?? null,
                'driver_name' => $data['driver_name'],
                'driver_phone' => $data['driver_phone'] ?? null,
                'status' => TripStatus::Loaded,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user,
            ]);

            // 3. Deduct stock and record transfer movements
            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $loadedQty = (float) $item['loaded_quantity'];
                $balanceBefore = (float) $product->warehouse_stock;
                $balanceAfter = $balanceBefore - $loadedQty;

                VehicleTripItem::create([
                    'vehicle_trip_id' => $trip->id,
                    'product_id' => $product->id,
                    'loaded_quantity' => $loadedQty,
                    'sold_quantity' => 0.00,
                    'damaged_quantity' => 0.00,
                    'exchanged_quantity' => 0.00,
                    'returned_quantity' => 0.00,
                ]);

                $product->warehouse_stock = $balanceAfter;
                $product->save();

                $this->stockService->recordMovement(
                    product: $product,
                    type: StockMovementType::LoadToVehicle,
                    source: StockLocationType::CentralWarehouse,
                    destination: StockLocationType::VehicleTransit,
                    quantity: $loadedQty,
                    balanceBefore: $balanceBefore,
                    balanceAfter: $balanceAfter,
                    referenceType: 'VehicleTrip',
                    referenceId: $trip->id,
                    remarks: "Loaded onto vehicle {$data['vehicle_number']} for {$tripNo}",
                    userId: $user
                );
            }

            return $trip;
        });
    }

    /**
     * Dispatch the vehicle on its road route.
     */
    public function dispatchTrip(VehicleTrip $trip): VehicleTrip
    {
        $trip->status = TripStatus::Dispatched;
        $trip->dispatched_at = now();
        $trip->save();

        return $trip;
    }

    /**
     * Get printable loading sheet manifest and trip details.
     */
    public function getTripManifest(VehicleTrip $trip): VehicleTrip
    {
        return $trip->load([
            'road',
            'driver',
            'creator:id,name',
            'items.product',
        ]);
    }

    /**
     * Get trip with full reconciliation details (items, memos, collections, and financial aggregations).
     */
    public function getTripForReconciliation(VehicleTrip $trip): array
    {
        $trip->load([
            'road',
            'driver',
            'creator:id,name',
            'items.product',
            'memos.retailer',
            'payments.retailer',
            'returns.product',
        ]);

        $itemDetails = $trip->items->map(function (VehicleTripItem $item) {
            $loaded = (float) $item->loaded_quantity;
            $sold = (float) $item->sold_quantity;
            $damaged = (float) $item->damaged_quantity;
            $exchanged = (float) $item->exchanged_quantity;
            $returned = (float) $item->returned_quantity;
            $expectedReturn = max(0.00, round(($loaded + $exchanged) - $sold, 2));
            $variance = round(($loaded + $exchanged) - ($sold + $returned), 2);

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_code' => $item->product->code,
                'packaging_type' => $item->product->packaging_type,
                'wholesale_price' => (float) $item->product->wholesale_price,
                'loaded_quantity' => $loaded,
                'sold_quantity' => $sold,
                'damaged_quantity' => $damaged,
                'exchanged_quantity' => $exchanged,
                'expected_return' => $expectedReturn,
                'returned_quantity' => $returned,
                'variance' => $variance,
            ];
        });

        $memos = $trip->memos;
        $payments = $trip->payments;

        $grossSales = (float) $memos->sum('subtotal');
        $damageDeductions = (float) $memos->sum('damage_deduction');
        $exchangeDeductions = (float) $memos->sum('exchange_deduction');
        $customDeductions = (float) $memos->sum('custom_deduction');
        $netSales = (float) $memos->sum('grand_total');
        $duesGenerated = (float) $memos->sum('due_amount');
        $totalCollected = (float) $payments->sum('amount');

        $cashCollected = (float) $payments->filter(fn ($p) => $p->payment_method->value === 'CASH')->sum('amount');
        $digitalCollected = (float) $payments->filter(fn ($p) => $p->payment_method->value !== 'CASH')->sum('amount');

        return [
            'trip' => $trip,
            'items' => $itemDetails,
            'summary' => [
                'memos_count' => $memos->count(),
                'gross_sales' => $grossSales,
                'damage_deductions' => $damageDeductions,
                'exchange_deductions' => $exchangeDeductions,
                'custom_deductions' => $customDeductions,
                'net_sales' => $netSales,
                'dues_generated' => $duesGenerated,
                'total_collected' => $totalCollected,
                'cash_collected' => $cashCollected,
                'digital_collected' => $digitalCollected,
            ],
        ];
    }

    /**
     * Complete trip reconciliation: log unsold return movements, update stock and close trip.
     */
    public function completeReconciliation(VehicleTrip $trip, array $data, ?int $userId = null): VehicleTrip
    {
        if ($trip->status === TripStatus::Completed) {
            throw new Exception("Trip {$trip->trip_no} has already been closed and settled.");
        }

        return DB::transaction(function () use ($trip, $data, $userId): VehicleTrip {
            $user = $userId ?? Auth::id();

            foreach ($data['items'] as $itemData) {
                /** @var VehicleTripItem $tripItem */
                $tripItem = VehicleTripItem::where('vehicle_trip_id', $trip->id)
                    ->where('product_id', $itemData['product_id'])
                    ->firstOrFail();

                $returnedQty = max(0.00, (float) $itemData['returned_quantity']);
                $tripItem->returned_quantity = $returnedQty;
                $tripItem->save();

                if ($returnedQty > 0) {
                    /** @var Product $product */
                    $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);
                    $balanceBefore = (float) $product->warehouse_stock;
                    $balanceAfter = $balanceBefore + $returnedQty;
                    $product->warehouse_stock = $balanceAfter;
                    $product->save();

                    $this->stockService->recordMovement(
                        product: $product,
                        type: StockMovementType::ReturnFromVehicle,
                        source: StockLocationType::VehicleTransit,
                        destination: StockLocationType::CentralWarehouse,
                        quantity: $returnedQty,
                        balanceBefore: $balanceBefore,
                        balanceAfter: $balanceAfter,
                        referenceType: 'VehicleTrip',
                        referenceId: $trip->id,
                        remarks: "Unsold stock returned from trip {$trip->trip_no} to Central Warehouse",
                        userId: $user
                    );
                }
            }

            $trip->status = TripStatus::Completed;
            $trip->completed_at = now();
            if (! empty($data['notes'])) {
                $trip->notes = $data['notes'];
            }
            $trip->save();

            return $trip;
        });
    }
}
