<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'company_name',
    'phone',
    'email',
    'address',
    'opening_due',
    'current_due',
    'is_active',
    'notes',
    'created_by',
])]
class Supplier extends Model
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
            'opening_due' => 'decimal:2',
            'current_due' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Purchases procured from this supplier.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    /**
     * Payments made to this supplier.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_id');
    }
}
