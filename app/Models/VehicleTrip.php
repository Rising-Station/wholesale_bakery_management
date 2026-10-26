<?php

namespace App\Models;

use App\Enums\TripStatus;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'trip_no',
    'road_id',
    'trip_date',
    'vehicle_number',
    'driver_id',
    'driver_name',
    'driver_phone',
    'status',
    'notes',
    'created_by',
    'dispatched_at',
    'completed_at',
])]
class VehicleTrip extends Model
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
            'trip_date' => 'date',
            'status' => TripStatus::class,
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Road route this vehicle is dispatched on (Road A, B, or C).
     */
    public function road(): BelongsTo
    {
        return $this->belongsTo(Road::class, 'road_id');
    }

    /**
     * Driver in charge of the trip.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Loaded products on this vehicle trip.
     */
    public function items(): HasMany
    {
        return $this->hasMany(VehicleTripItem::class, 'vehicle_trip_id');
    }

    /**
     * Sales memos generated during this vehicle trip.
     */
    public function memos(): HasMany
    {
        return $this->hasMany(SalesMemo::class, 'vehicle_trip_id');
    }

    /**
     * Retailer payments collected during this vehicle trip.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(RetailerPayment::class, 'vehicle_trip_id');
    }

    /**
     * Damage returns and exchanges accepted during this vehicle trip.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(DamageReturn::class, 'vehicle_trip_id');
    }
}
