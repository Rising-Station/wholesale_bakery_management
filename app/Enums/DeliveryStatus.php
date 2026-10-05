<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'PENDING';
    case Loaded = 'LOADED';
    case Delivered = 'DELIVERED';
    case Returned = 'RETURNED';

    /**
     * Get human-readable badge label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Loaded => 'Loaded on Vehicle',
            self::Delivered => 'Delivered to Shop',
            self::Returned => 'Returned',
        };
    }

    /**
     * Get badge color severity for UI.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Loaded => 'info',
            self::Delivered => 'success',
            self::Returned => 'secondary',
        };
    }
}
