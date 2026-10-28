<?php

namespace App\Enums;

enum DamageType: string
{
    case Expired = 'EXPIRED';
    case Damaged = 'DAMAGED';
    case TransitLoss = 'TRANSIT_LOSS';
    case BrokenPacket = 'BROKEN_PACKET';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Expired => 'Expired Date',
            self::Damaged => 'Damaged / Spoiled',
            self::TransitLoss => 'Transit Loss',
            self::BrokenPacket => 'Opened / Broken Packaging (Loose)',
        };
    }

    /**
     * Get badge severity.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Expired => 'danger',
            self::Damaged => 'danger',
            self::TransitLoss => 'secondary',
            self::BrokenPacket => 'warn',
        };
    }
}
