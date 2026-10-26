<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'code',
    'short_label',
    'description',
    'is_active',
])]
class PackagingType extends Model
{
    use HasFactory;

    /**
     * Boot model events.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (PackagingType $packagingType): void {
            if (empty($packagingType->code)) {
                $packagingType->code = strtoupper(Str::slug($packagingType->name, '_'));
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Products using this packaging type.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'packaging_type_id');
    }
}
