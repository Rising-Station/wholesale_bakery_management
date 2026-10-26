<?php

namespace App\Models;

use App\Enums\PackagingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'category_id',
    'unit_id',
    'packaging_type_id',
    'name',
    'code',
    'packaging_type',
    'units_per_cartoon',
    'weight_per_unit_kg',
    'pieces_per_packet',
    'cost_price',
    'wholesale_price',
    'warehouse_stock',
    'min_alert_quantity',
    'is_active',
    'notes',
])]
class Product extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'packaging_type' => PackagingType::class,
            'unit_id' => 'integer',
            'packaging_type_id' => 'integer',
            'units_per_cartoon' => 'integer',
            'weight_per_unit_kg' => 'decimal:3',
            'pieces_per_packet' => 'integer',
            'cost_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'warehouse_stock' => 'decimal:2',
            'min_alert_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Category this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Unit this product is measured in.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Packaging type model if linked.
     */
    public function packagingTypeRelation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PackagingType::class, 'packaging_type_id');
    }

    /**
     * Stock movements for this product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    /**
     * Damage / scrap inventory entries for this product.
     */
    public function damageScraps(): HasMany
    {
        return $this->hasMany(DamageScrapInventory::class, 'product_id');
    }

    /**
     * Scope for active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for products below or at minimum alert stock.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('warehouse_stock', '<=', DB::raw('min_alert_quantity'));
    }

    /**
     * Whether the product currently has low stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->warehouse_stock <= $this->min_alert_quantity;
    }
}
