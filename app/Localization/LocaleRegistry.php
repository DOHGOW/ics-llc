<?php

namespace App\Localization;

/**
 * Reads the config-driven locale registry (config/locales.php) — R-2 / D-037.
 * No locale is hardcoded in application logic; everything resolves through here.
 */
final class LocaleRegistry
{
    public static function default(): string
    {
        return (string) config('locales.default', 'en');
    }

    public static function fallback(): string
    {
        return (string) config('locales.fallback', 'en');
    }

    /** @return array<string,array{name:string,native:string,dir:string}> */
    public static function available(): array
    {
        return (array) config('locales.available', []);
    }

    /** @return array<int,string> Active locales, intersected with available, never empty. */
    public static function active(): array
    {
        $active = (array) config('locales.active', ['en']);
        $available = array_keys(self::available());
        $resolved = array_values(array_intersect($active, $available));

        return $resolved !== [] ? $resolved : [self::default()];
    }

    public static function isActive(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::active(), true);
    }

    public static function isAvailable(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::available());
    }

    /** Text direction for a locale ('ltr'|'rtl'). Defaults to ltr. */
    public static function direction(string $locale): string
    {
        return (string) config("locales.available.{$locale}.dir", 'ltr');
    }

    public static function nativeName(string $locale): string
    {
        return (string) config("locales.available.{$locale}.native", $locale);
    }
}
