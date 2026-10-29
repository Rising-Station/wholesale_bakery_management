<?php

namespace App\Services;

use App\Enums\StockLocationType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Get paginated warehouse inventory stock overview.
     */
    public function getWarehouseStockOverview(?int $categoryId = null, ?string $search = null, ?bool $lowStockOnly = false): LengthAwarePaginator
    {
        $query = Product::with('category')
            ->orderBy('name');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($lowStockOnly) {
            $query->where('warehouse_stock', '<=', DB::raw('min_alert_quantity'));
        }

        return $query->paginate(25)->withQueryString();
    }

    /**
     * Get low stock alert products for widgets and notifications.
     *
     * @return Collection<int, Product>
     */
    public function getLowStockAlertProducts(): Collection
    {
        return Product::with('category')
            ->lowStock()
            ->orderBy('warehouse_stock', 'asc')
            ->get();
    }

    /**
     * Get paginated immutable stock movement audit ledger.
     */
    public function getStockMovements(
        ?int $productId = null,
        ?string $movementType = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): LengthAwarePaginator {
        $query = StockMovement::with(['product', 'creator:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->paginate(30)->withQueryString();
    }

    /**
     * Record an immutable movement in the stock ledger.
     */
    public function recordMovement(
        Product $product,
        StockMovementType $type,
        StockLocationType $source,
        StockLocationType $destination,
        float $quantity,
        float $balanceBefore,
        float $balanceAfter,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $remarks = null,
        ?int $userId = null
    ): StockMovement {
        return StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => $type->value,
            'source_location' => $source->value,
            'destination_location' => $destination->value,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'remarks' => $remarks,
            'created_by' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Process a physical stock count audit adjustment.
     *
     * @param  array{adjustment_date: string, reason: string, notes?: ?string, items: array<int, array{product_id: int, physical_stock: float}>}  $data
     */
    public function processAdjustment(array $data, ?int $userId = null): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userId): StockAdjustment {
            $user = $userId ?? Auth::id();
            $today = Carbon::parse($data['adjustment_date'])->format('Ymd');
            $countToday = StockAdjustment::whereDate('adjustment_date', $data['adjustment_date'])->count() + 1;
            $adjustmentNo = sprintf('ADJ-%s-%04d', $today, $countToday);

            $adjustment = StockAdjustment::create([
                'adjustment_no' => $adjustmentNo,
                'adjustment_date' => $data['adjustment_date'],
                'reason' => $data['reason'],
                'status' => 'APPROVED',
                'approved_by' => $user,
                'created_by' => $user,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);
                $systemStock = (float) $product->warehouse_stock;
                $physicalStock = (float) $itemData['physical_stock'];
                $variance = round($physicalStock - $systemStock, 2);

                $adjustmentType = $variance >= 0 ? 'ADD' : 'REDUCE';

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id' => $product->id,
                    'system_stock' => $systemStock,
                    'physical_stock' => $physicalStock,
                    'variance_quantity' => abs($variance),
                    'adjustment_type' => $adjustmentType,
                ]);

                if ($variance != 0.0) {
                    $product->warehouse_stock = $physicalStock;
                    $product->save();

                    $actionType = $itemData['action_type'] ?? null;
                    $actionLabel = match ($actionType) {
                        'IN' => 'Stock In (+)',
                        'OUT' => 'Stock Out (-)',
                        default => 'Physical Audit Variance',
                    };

                    $this->recordMovement(
                        product: $product,
                        type: StockMovementType::AuditAdjustment,
                        source: StockLocationType::CentralWarehouse,
                        destination: StockLocationType::CentralWarehouse,
                        quantity: abs($variance),
                        balanceBefore: $systemStock,
                        balanceAfter: $physicalStock,
                        referenceType: 'StockAdjustment',
                        referenceId: $adjustment->id,
                        remarks: "{$actionLabel} ({$data['reason']})",
                        userId: $user
                    );
                }
            }

            return $adjustment;
        });
    }

    /**
     * Get paginated adjustment history.
     */
    public function getAdjustments(): LengthAwarePaginator
    {
        return StockAdjustment::with(['creator:id,name', 'approver:id,name', 'items.product'])
            ->orderByDesc('adjustment_date')
            ->orderByDesc('id')
            ->paginate(20);
    }
}
