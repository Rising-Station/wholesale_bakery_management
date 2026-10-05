<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Ordered = 'ORDERED';
    case Received = 'RECEIVED';
    case Partial = 'PARTIAL';
    case Cancelled = 'CANCELLED';

    /**
     * Get human-readable badge label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ordered => 'Ordered',
            self::Received => 'Received & Stocked',
            self::Partial => 'Partially Received',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get badge color severity for UI.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Ordered => 'info',
            self::Received => 'success',
            self::Partial => 'warn',
            self::Cancelled => 'secondary',
        };
    }
}
