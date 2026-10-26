<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_trip_id',
    'product_id',
    'loaded_quantity',
    'sold_quantity',
    'damaged_quantity',
    'exchanged_quantity',
    'returned_quantity',
])]
class VehicleTripItem extends Model
{
    use HasFactory;

    protected $appends = ['available_trip_stock'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loaded_quantity' => 'decimal:2',
            'sold_quantity' => 'decimal:2',
            'damaged_quantity' => 'decimal:2',
            'exchanged_quantity' => 'decimal:2',
            'returned_quantity' => 'decimal:2',
        ];
    }

    /**
     * Parent vehicle trip.
     */
    public function vehicleTrip(): BelongsTo
    {
        return $this->belongsTo(VehicleTrip::class, 'vehicle_trip_id');
    }

    /**
     * Loaded product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Net available remaining stock inside vehicle during active trip.
     */
    public function getAvailableTripStockAttribute(): float
    {
        return (float) ($this->loaded_quantity + $this->exchanged_quantity - $this->sold_quantity - $this->damaged_quantity);
    }
}
