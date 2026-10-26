<?php

namespace App\Models;

use App\Enums\PackagingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_memo_id',
    'product_id',
    'packaging_type',
    'quantity',
    'unit_price',
    'line_total',
])]
class SalesMemoItem extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'packaging_type' => PackagingType::class,
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * Parent sales memo.
     */
    public function salesMemo(): BelongsTo
    {
        return $this->belongsTo(SalesMemo::class, 'sales_memo_id');
    }

    /**
     * Sold product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
