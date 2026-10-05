<?php

namespace App\Enums;

enum ExpenseCategoryType: string
{
    case Salary = 'SALARY';
    case ShopRent = 'SHOP_RENT';
    case Electricity = 'ELECTRICITY';
    case Gas = 'GAS';
    case Water = 'WATER';
    case Other = 'OTHER';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Salary => 'Staff / Driver Salary',
            self::ShopRent => 'Warehouse / Shop Rent',
            self::Electricity => 'Electricity Bill',
            self::Gas => 'Gas Bill',
            self::Water => 'Water / Utility Bill',
            self::Other => 'Other Operational Expenses',
        };
    }
}
