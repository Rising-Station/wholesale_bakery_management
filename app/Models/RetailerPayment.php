<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'retailer_id',
    'sales_memo_id',
    'road_id',
    'vehicle_trip_id',
    'payment_method',
    'amount',
    'payment_date',
    'transaction_ref',
    'notes',
    'collected_by',
    'created_by',
])]
class RetailerPayment extends Model
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
            'payment_method' => PaymentMethod::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Retailer shop paying.
     */
    public function retailer(): BelongsTo
    {
        return $this->belongsTo(RetailerShop::class, 'retailer_id');
    }

    /**
     * Sales memo payment was applied to (if any).
     */
    public function salesMemo(): BelongsTo
    {
        return $this->belongsTo(SalesMemo::class, 'sales_memo_id');
    }

    /**
     * Road where the collection occurred.
     */
    public function road(): BelongsTo
    {
        return $this->belongsTo(Road::class, 'road_id');
    }

    /**
     * Vehicle trip during which cash was collected.
     */
    public function vehicleTrip(): BelongsTo
    {
        return $this->belongsTo(VehicleTrip::class, 'vehicle_trip_id');
    }

    /**
     * Staff/Driver who collected the payment.
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
