<?php

namespace App\Models;

use App\Enums\DamageType;
use App\Enums\ReturnType;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_memo_id',
    'retailer_id',
    'product_id',
    'vehicle_trip_id',
    'return_type',
    'damage_type',
    'quantity',
    'unit_deduction_rate',
    'total_deduction_amount',
    'affects_sellable_stock',
    'is_custom_amount',
    'notes',
    'approved_by',
    'created_by',
])]
class DamageReturn extends Model
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
            'return_type' => ReturnType::class,
            'damage_type' => DamageType::class,
            'quantity' => 'decimal:2',
            'unit_deduction_rate' => 'decimal:2',
            'total_deduction_amount' => 'decimal:2',
            'affects_sellable_stock' => 'boolean',
            'is_custom_amount' => 'boolean',
        ];
    }

    /**
     * Parent sales memo where deduction was applied.
     */
    public function salesMemo(): BelongsTo
    {
        return $this->belongsTo(SalesMemo::class, 'sales_memo_id');
    }

    /**
     * Retailer returning the goods.
     */
    public function retailer(): BelongsTo
    {
        return $this->belongsTo(RetailerShop::class, 'retailer_id');
    }

    /**
     * Product returned.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Vehicle trip during which the return was accepted.
     */
    public function vehicleTrip(): BelongsTo
    {
        return $this->belongsTo(VehicleTrip::class, 'vehicle_trip_id');
    }

    /**
     * Approving manager/driver user.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
