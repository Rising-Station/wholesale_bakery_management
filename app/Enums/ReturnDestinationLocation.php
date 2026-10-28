<?php

namespace App\Enums;

enum ReturnDestinationLocation: string
{
    case SellableStock = 'SELLABLE_STOCK';
    case RepackHolding = 'REPACK_HOLDING';
    case ScrapQuarantine = 'SCRAP_QUARANTINE';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::SellableStock => 'Sellable Inventory Stock',
            self::RepackHolding => 'Repack Bay Holding',
            self::ScrapQuarantine => 'Scrap Quarantine Disposal',
        };
    }
}

