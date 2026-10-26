<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'short_code',
    'description',
    'is_active',
])]
class Unit extends Model
{
    use HasFactory;

    /**
     * Boot model events.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Unit $unit): void {
            if (empty($unit->short_code)) {
                $unit->short_code = strtolower(Str::slug($unit->name));
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
     * Products using this unit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}
