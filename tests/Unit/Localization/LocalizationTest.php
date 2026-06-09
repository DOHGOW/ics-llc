<?php

namespace Tests\Unit\Localization;

use App\Localization\CurrencyFormatter;
use App\Localization\LocaleRegistry;
use Tests\TestCase;

/** Localization registry, RTL, fallback, formatters, and WCAG lang/dir. */
class LocalizationTest extends TestCase
{
    public function test_direction_is_rtl_for_arabic(): void
    {
        $this->assertSame('rtl', LocaleRegistry::direction('ar'));
        $this->assertSame('ltr', LocaleRegistry::direction('en'));
        $this->assertSame('ltr', LocaleRegistry::direction('fr'));
    }

    public function test_arabic_is_not_active_by_default(): void
    {
        config(['locales.active' => ['en']]);
        $this->assertTrue(LocaleRegistry::isActive('en'));
        $this->assertFalse(LocaleRegistry::isActive('ar'));
        $this->assertFalse(LocaleRegistry::isActive('fr'));
    }

    public function test_active_is_never_empty(): void
    {
        config(['locales.active' => []]);
        $this->assertSame([LocaleRegistry::default()], LocaleRegistry::active());
    }

    public function test_inactive_codes_are_dropped(): void
    {
        config(['locales.active' => ['en', 'zz']]); // zz not available
        $this->assertSame(['en'], LocaleRegistry::active());
    }

    public function test_currency_formats_non_empty(): void
    {
        $out = (new CurrencyFormatter)->format(1234.5, 'NGN', 'en');
        $this->assertNotEmpty($out);
        $this->assertStringContainsString('1', $out);
    }

    public function test_layout_emits_html_lang_and_dir(): void
    {
        $this->withoutVite();
        app()->setLocale('en');

        $html = view('layouts.app')->render();

        $this->assertStringContainsString('lang="en"', $html); // WCAG 3.1.1
        $this->assertStringContainsString('dir="ltr"', $html);
    }
}
