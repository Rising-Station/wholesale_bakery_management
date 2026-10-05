<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PurchaseIn = 'PURCHASE_IN';
    case PurchaseReturnSupplier = 'PURCHASE_RETURN_SUPPLIER';
    case LoadToVehicle = 'LOAD_TO_VEHICLE';
    case ReturnFromVehicle = 'RETURN_FROM_VEHICLE';
    case DirectSale = 'DIRECT_SALE';
    case DamageReturn = 'DAMAGE_RETURN';
    case ScrapDisposal = 'SCRAP_DISPOSAL';
    case AuditAdjustment = 'AUDIT_ADJUSTMENT';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PurchaseIn => 'Purchase Received (Stock In)',
            self::PurchaseReturnSupplier => 'Return to Supplier',
            self::LoadToVehicle => 'Loaded to Delivery Vehicle',
            self::ReturnFromVehicle => 'Returned Unsold from Vehicle',
            self::DirectSale => 'Direct Warehouse Sale',
            self::DamageReturn => 'Damaged/Expired Return from Retailer',
            self::ScrapDisposal => 'Scrap / Disposal Write-off',
            self::AuditAdjustment => 'Physical Audit Adjustment',
        };
    }

    /**
     * Whether this movement increases central warehouse stock.
     */
    public function increasesCentralStock(): bool
    {
        return match ($this) {
            self::PurchaseIn, self::ReturnFromVehicle => true,
            default => false,
        };
    }

    /**
     * Whether this movement decreases central warehouse stock.
     */
    public function decreasesCentralStock(): bool
    {
        return match ($this) {
            self::LoadToVehicle, self::PurchaseReturnSupplier, self::DirectSale => true,
            default => false,
        };
    }
}
