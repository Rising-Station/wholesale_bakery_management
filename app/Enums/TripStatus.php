<?php

namespace App\Enums;

enum TripStatus: string
{
    case Draft = 'DRAFT';
    case Loaded = 'LOADED';
    case Dispatched = 'DISPATCHED';
    case Completed = 'COMPLETED';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft Trip Plan',
            self::Loaded => 'Stock Loaded & Verified',
            self::Dispatched => 'On Route (Dispatched)',
            self::Completed => 'Settled & Closed',
        };
    }

    /**
     * Get badge severity for UI.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Loaded => 'info',
            self::Dispatched => 'warn',
            self::Completed => 'success',
        };
    }
}
