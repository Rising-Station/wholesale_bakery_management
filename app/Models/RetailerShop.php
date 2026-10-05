<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'road_id',
    'shop_name',
    'owner_name',
    'phone',
    'alt_phone',
    'address',
    'credit_limit',
    'opening_due',
    'current_due',
    'is_active',
    'created_by',
])]
class RetailerShop extends Model
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
            'credit_limit' => 'decimal:2',
            'opening_due' => 'decimal:2',
            'current_due' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The road this retailer belongs to.
     */
    public function road(): BelongsTo
    {
        return $this->belongsTo(Road::class);
    }
}
