<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'CASH';
    case Bkash = 'BKASH';
    case Nagad = 'NAGAD';
    case BankTransfer = 'BANK_TRANSFER';
    case Cheque = 'CHEQUE';

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
        };
    }
}
