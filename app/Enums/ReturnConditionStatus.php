<?php

namespace App\Enums;

enum ReturnConditionStatus: string
{
    case GoodSealed = 'GOOD_SEALED';
    case BrokenPacket = 'BROKEN_PACKET';
    case DamagedExpired = 'DAMAGED_EXPIRED';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::GoodSealed => 'Good Condition / Sealed Packet',
            self::BrokenPacket => 'Broken / Opened Packaging (Loose)',
            self::DamagedExpired => 'Damaged / Expired Product',
        };
    }
}

