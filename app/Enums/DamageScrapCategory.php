<?php

namespace App\Enums;

enum DamageScrapCategory: string
{
    case ScrapDisposal = 'SCRAP_DISPOSAL';
    case RepackHolding = 'REPACK_HOLDING';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ScrapDisposal => 'Scrap Disposal / Quarantine',
            self::RepackHolding => 'Repackaging Holding',
        };
    }
}

