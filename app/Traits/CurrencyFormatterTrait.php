<?php

namespace App\Traits;

trait CurrencyFormatterTrait
{
    /**
     * Format an amount into standard BDT currency string (৳ 0,000.00).
     */
    public function formatCurrency(float|int|string|null $amount, int $decimals = 2, string $symbol = '৳ '): string
    {
        $num = (float) ($amount ?? 0);

        return $symbol.number_format($num, $decimals, '.', ',');
    }

    /**
     * Normalize decimal representation for financial operations.
     */
    public function roundMoney(float|int|string|null $amount, int $decimals = 2): float
    {
        return round((float) ($amount ?? 0), $decimals);
    }
}
