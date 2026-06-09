{{--
    Accessible language switcher (R-4 / D-028).
    - Renders ONLY active locales (English in Phase 1) — so it is hidden while a
      single locale is active, but the markup/behaviour is ready for FR/AR.
    - Accessibility: labelled <nav>, aria-current on the active locale, lang +
      hreflang on each option, keyboard-navigable links (no custom JS required).
    - Switching is a GET with ?locale=xx; the SetLocale middleware validates it is
      active and persists it to the session. No hardcoded locale assumptions.
--}}
@php
    $active = \App\Localization\LocaleRegistry::active();
    $current = app()->getLocale();
@endphp

@if (count($active) > 1)
    <nav aria-label="{{ __('Language selection') }}">
        <ul>
            @foreach ($active as $locale)
                <li>
                    <a
                        href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}"
                        lang="{{ $locale }}"
                        hreflang="{{ $locale }}"
                        dir="{{ \App\Localization\LocaleRegistry::direction($locale) }}"
                        @if ($locale === $current) aria-current="true" @endif
                    >
                        {{ \App\Localization\LocaleRegistry::nativeName($locale) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
