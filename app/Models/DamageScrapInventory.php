<?php

namespace App\Models;

use App\Enums\DamageType;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'retailer_id',
    'road_id',
    'damage_type',
    'quantity',
    'status',
    'disposal_date',
    'disposal_notes',
    'created_by',
])]
class DamageScrapInventory extends Model
{
    use HasAuditTrail, HasFactory;

    protected $table = 'damage_scrap_inventory';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'damage_type' => DamageType::class,
            'quantity' => 'decimal:2',
            'disposal_date' => 'date',
        ];
    }

    /**
     * Product that is damaged/expired.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Retailer shop where the damage came from (if applicable).
     */
    public function retailerShop(): BelongsTo
    {
        return $this->belongsTo(RetailerShop::class, 'retailer_id');
    }

    /**
     * Road where the damage occurred or returned from (if applicable).
     */
    public function road(): BelongsTo
    {
        return $this->belongsTo(Road::class, 'road_id');
    }
}
