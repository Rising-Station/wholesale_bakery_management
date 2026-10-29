<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'purchase_no',
    'supplier_id',
    'purchase_date',
    'subtotal',
    'discount',
    'grand_total',
    'paid_amount',
    'due_amount',
    'status',
    'notes',
    'created_by',
])]
class Purchase extends Model
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
            'purchase_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'status' => PurchaseStatus::class,
        ];
    }

    /**
     * Retrieve the model for a bound value (supports both ID and purchase_no).
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where('id', $value)
            ->orWhere('purchase_no', $value)
            ->firstOrFail();
    }

    /**
     * Supplier who provided the goods.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Purchase line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /**
     * Payments recorded against this purchase.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'purchase_id');
    }
}
