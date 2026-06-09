<?php

namespace App\Localization;

/**
 * Locale aware currency formatting (R-6 / D-017 multi-currency readiness).
 *
 * Uses the PHP `intl` NumberFormatter (symbol, grouping, decimals per locale +
 * currency). Falls back to a plain "CODE 0.00" format if `intl` is unavailable
 * (verified by the Hostinger capability spike, CHECK 02). Amounts are stored as
 * decimals and formatted only at display time. No locale/currency is hardcoded.
 */
class CurrencyFormatter
{
    public function format(float|int $amount, string $currency = 'NGN', ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            $result = $formatter->formatCurrency((float) $amount, $currency);

            if ($result !== false) {
                return $result;
            }
        }

        // Graceful fallback when intl is unavailable.
        return $currency.' '.number_format((float) $amount, 2);
    }
}
