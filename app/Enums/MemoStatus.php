<?php

namespace App\Enums;

enum MemoStatus: string
{
    case Paid = 'PAID';
    case Partial = 'PARTIAL';
    case Due = 'DUE';
    case Cancelled = 'CANCELLED';

    /**
     * Get human-readable badge label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Partial => 'Partial Due',
            self::Due => 'Unpaid / Due',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get badge color severity for UI.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Partial => 'warn',
            self::Due => 'danger',
            self::Cancelled => 'secondary',
        };
    }
}
