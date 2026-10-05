<?php

namespace App\Enums;

enum PackagingType: string
{
    case Cartoon = 'CARTOON';
    case Packet5kg = 'PACKET_5KG';
    case Packet12pcs = 'PACKET_12PCS';
    case Pieces = 'PIECES';
    case Custom = 'CUSTOM';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cartoon => 'Cartoon (Master Box)',
            self::Packet5kg => '5 KG Bulk Packet',
            self::Packet12pcs => '12 Pcs Packet',
            self::Pieces => 'Single Piece / Pcs',
            self::Custom => 'Custom Packaging',
        };
    }

    /**
     * Get short display label.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Cartoon => 'Ctn',
            self::Packet5kg => '5kg Pkt',
            self::Packet12pcs => '12pc Pkt',
            self::Pieces => 'Pcs',
            self::Custom => 'Custom',
        };
    }
}
