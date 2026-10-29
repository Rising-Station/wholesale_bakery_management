<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_name',
    'owner_name',
    'phone',
    'road_id',
    'message',
    'status',
])]
class RetailerInquiry extends Model
{
    use HasFactory;

    /**
     * Get the distribution road for this retailer inquiry.
     */
    public function road(): BelongsTo
    {
        return $this->belongsTo(Road::class);
    }
}
