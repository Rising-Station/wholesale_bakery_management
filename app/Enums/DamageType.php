<?php

namespace App\Enums;

enum DamageType: string
{
    case Expired = 'EXPIRED';
    case Damaged = 'DAMAGED';
    case TransitLoss = 'TRANSIT_LOSS';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Expired => 'Expired Date',
            self::Damaged => 'Damaged / Broken Pack',
            self::TransitLoss => 'Transit Loss',
        };
    }

    /**
     * Get badge severity.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Expired => 'danger',
            self::Damaged => 'warn',
            self::TransitLoss => 'secondary',
        };
    }
}
