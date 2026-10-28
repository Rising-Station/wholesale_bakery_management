<?php

namespace App\Enums;

enum StockLocationType: string
{
    case CentralWarehouse = 'CENTRAL_WAREHOUSE';
    case VehicleTransit = 'VEHICLE_TRANSIT';
    case DamageQuarantine = 'DAMAGE_QUARANTINE';
    case RepackHolding = 'REPACK_HOLDING';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CentralWarehouse => 'Central Warehouse',
            self::VehicleTransit => 'Delivery Vehicle (In-Transit)',
            self::DamageQuarantine => 'Damage & Scrap Quarantine',
            self::RepackHolding => 'Repackaging & Loose Goods Holding',
        };
    }
}
