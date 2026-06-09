{{--
    Base layout — resolves WCAG 3.1.1 (Language of Page) and text direction (R-3 /
    LOC-6 / D-028). `htmlLang` and `htmlDir` are shared by the SetLocale middleware.
    Accessibility: lang + dir attributes, a skip link, and a labelled language nav.
--}}
<!DOCTYPE html>
<html lang="{{ $htmlLang ?? app()->getLocale() }}" dir="{{ $htmlDir ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only">{{ __('Skip to main content') }}</a>

    <header>
        <x-language-switcher />
    </header>

    <main id="main-content">
        {{ $slot ?? '' }}
    </main>
</body>
</html>
