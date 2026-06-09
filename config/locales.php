<?php

/*
| Locale registry (R-2 / D-014 / D-037).
|
| `available` defines every locale the ARCHITECTURE supports (English, French,
| Arabic) with its display name and text direction. `active` is the config-driven
| list of SELECTABLE locales — English only in Phase 1. Enabling French or Arabic is
| a configuration change (APP_ACTIVE_LOCALES) with NO code or schema change (D-037).
|
| RTL readiness is present for Arabic (dir=rtl) WITHOUT Arabic being active.
*/

return [

    'default' => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    // All architecture-supported locales (D-014). 'dir' drives <html dir>.
    'available' => [
        'en' => ['name' => 'English', 'native' => 'English',  'dir' => 'ltr'],
        'fr' => ['name' => 'French',  'native' => 'Français', 'dir' => 'ltr'],
        'ar' => ['name' => 'Arabic',  'native' => 'العربية',  'dir' => 'rtl'],
    ],

    // Active (selectable) locales — config-driven activation (D-037).
    // Phase 1: 'en'. Phase 2: 'en,fr'. Phase 3: 'en,fr,ar'. No code change.
    'active' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('APP_ACTIVE_LOCALES', 'en'))
    ))),

];
