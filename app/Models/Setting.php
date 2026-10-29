<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'value',
    'group',
    'description',
])]
class Setting extends Model
{
    use HasFactory;

    /**
     * In-memory cache of all settings for current request lifecycle.
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $cachedSettings = null;

    /**
     * Retrieve all settings keyed by name with in-memory caching.
     *
     * @return array<string, mixed>
     */
    public static function allKeyed(): array
    {
        if (static::$cachedSettings === null) {
            static::$cachedSettings = static::all()->pluck('value', 'key')->all();
        }

        return static::$cachedSettings;
    }

    /**
     * Retrieve a setting value by key with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = static::allKeyed();

        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, string $group = 'general', ?string $description = null): static
    {
        static::$cachedSettings = null;

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'group' => $group,
                'description' => $description,
            ]
        );
    }
}
