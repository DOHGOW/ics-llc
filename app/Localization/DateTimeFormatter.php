<?php

namespace App\Localization;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Locale + timezone aware date/time formatting (R-5 / D-014).
 *
 * Timestamps are stored in UTC (APP_TIMEZONE=UTC); this converts to the viewer's
 * timezone and formats using the active (or given) locale. No locale/timezone is
 * hardcoded — defaults come from config and the app locale.
 */
class DateTimeFormatter
{
    public function date(CarbonInterface|string $value, ?string $timezone = null, ?string $locale = null): string
    {
        return $this->toCarbon($value, $timezone, $locale)->isoFormat('LL');
    }

    public function dateTime(CarbonInterface|string $value, ?string $timezone = null, ?string $locale = null): string
    {
        return $this->toCarbon($value, $timezone, $locale)->isoFormat('LLL');
    }

    public function time(CarbonInterface|string $value, ?string $timezone = null, ?string $locale = null): string
    {
        return $this->toCarbon($value, $timezone, $locale)->isoFormat('LT');
    }

    private function toCarbon(CarbonInterface|string $value, ?string $timezone, ?string $locale): CarbonInterface
    {
        $carbon = $value instanceof CarbonInterface
            ? $value->copy()
            : Carbon::parse($value, 'UTC');

        return $carbon
            ->timezone($timezone ?? (string) config('app.timezone', 'UTC'))
            ->locale($locale ?? app()->getLocale());
    }
}
