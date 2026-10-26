<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\MemoStatus;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'memo_no',
    'retailer_id',
    'road_id',
    'vehicle_trip_id',
    'memo_date',
    'subtotal',
    'damage_deduction',
    'exchange_deduction',
    'custom_deduction',
    'grand_total',
    'previous_due',
    'total_net_payable',
    'paid_amount',
    'due_amount',
    'memo_status',
    'delivery_status',
    'notes',
    'created_by',
])]
class SalesMemo extends Model
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
            'memo_date' => 'date',
            'subtotal' => 'decimal:2',
            'damage_deduction' => 'decimal:2',
            'exchange_deduction' => 'decimal:2',
            'custom_deduction' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'previous_due' => 'decimal:2',
            'total_net_payable' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'memo_status' => MemoStatus::class,
            'delivery_status' => DeliveryStatus::class,
        ];
    }

    /**
     * Retailer shop billed.
     */
    public function retailer(): BelongsTo
    {
        return $this->belongsTo(RetailerShop::class, 'retailer_id');
    }

    /**
     * Road distribution route.
     */
    public function road(): BelongsTo
    {
        return $this->belongsTo(Road::class, 'road_id');
    }

    /**
     * Delivery vehicle trip during which the memo was issued.
     */
    public function vehicleTrip(): BelongsTo
    {
        return $this->belongsTo(VehicleTrip::class, 'vehicle_trip_id');
    }

    /**
     * Sold products line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesMemoItem::class, 'sales_memo_id');
    }

    /**
     * Returns and exchanges recorded against this sales memo.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(DamageReturn::class, 'sales_memo_id');
    }

    /**
     * Payments collected against this sales memo.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(RetailerPayment::class, 'sales_memo_id');
    }
}
