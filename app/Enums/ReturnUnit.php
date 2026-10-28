<?php

namespace App\Enums;

enum ReturnUnit: string
{
    case Package = 'PACKAGE';
    case Kg = 'KG';
    case Pcs = 'PCS';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Package => 'Full Package / Cartoon',
            self::Kg => 'Kilograms (Kg)',
            self::Pcs => 'Pieces (Pcs)',
        };
    }
}

