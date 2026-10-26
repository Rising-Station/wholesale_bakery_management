<?php

namespace App\Models;

use App\Enums\StockLocationType;
use App\Enums\StockMovementType;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'movement_type',
    'source_location',
    'destination_location',
    'reference_type',
    'reference_id',
    'quantity',
    'balance_before',
    'balance_after',
    'remarks',
    'created_by',
])]
class StockMovement extends Model
{
    use HasAuditTrail, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'source_location' => StockLocationType::class,
            'destination_location' => StockLocationType::class,
            'quantity' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    /**
     * Product associated with this movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
