<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'CASH';
    case Bkash = 'BKASH';
    case Nagad = 'NAGAD';
    case BankTransfer = 'BANK_TRANSFER';
    case Cheque = 'CHEQUE';
    case ReturnAdjustment = 'RETURN_ADJUSTMENT';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash in Hand',
            self::Bkash => 'bKash Merchant/Personal',
            self::Nagad => 'Nagad',
            self::BankTransfer => 'Bank Transfer',
            self::Cheque => 'Cheque',
            self::ReturnAdjustment => 'Damage Return Credit Note',
        };
    }
}
