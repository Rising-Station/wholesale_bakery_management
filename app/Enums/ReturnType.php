<?php

namespace App\Enums;

enum ReturnType: string
{
    case Damage = 'DAMAGE';
    case Exchange = 'EXCHANGE';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Damage => 'Damage / Expiry Return',
            self::Exchange => 'Product Exchange (Good Condition)',
        };
    }

    /**
     * Whether this return increases sellable stock.
     */
    public function increasesSellableStock(): bool
    {
        return match ($this) {
            self::Exchange => true,
            self::Damage => false,
        };
    }

    /**
     * Get badge severity.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Damage => 'danger',
            self::Exchange => 'info',
        };
    }
}
