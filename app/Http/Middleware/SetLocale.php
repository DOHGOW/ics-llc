<?php

namespace App\Http\Middleware;

use App\Localization\LocaleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale detection (R-1 / D-014). Resolution order (first ACTIVE match wins):
 *   1. explicit ?locale= switch (persisted to session)
 *   2. session preference
 *   3. authenticated user's locale
 *   4. Accept-Language header
 *   5. configured default
 *
 * Only ACTIVE locales are honoured (English in Phase 1); anything else falls
 * through to the default. The chosen locale + its direction are shared with views
 * for the <html lang/dir> wiring (R-3 / WCAG 3.1.1).
 *
 * Register in bootstrap/app.php (web group; harmless in api group — session-guarded).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detect($request);

        app()->setLocale($locale);

        view()->share('htmlLang', $locale);
        view()->share('htmlDir', LocaleRegistry::direction($locale));

        return $next($request);
    }

    private function detect(Request $request): string
    {
        $hasSession = $request->hasSession();

        // 1. Explicit switch — persist to session if active.
        $query = $request->query('locale');
        if (is_string($query) && LocaleRegistry::isActive($query)) {
            if ($hasSession) {
                $request->session()->put('locale', $query);
            }

            return $query;
        }

        // 2. Session preference.
        if ($hasSession) {
            $session = $request->session()->get('locale');
            if (LocaleRegistry::isActive($session)) {
                return $session;
            }
        }

        // 3. Authenticated user preference.
        $user = $request->user();
        if ($user !== null && LocaleRegistry::isActive($user->locale ?? null)) {
            return $user->locale;
        }

        // 4. Accept-Language header (first active match).
        foreach ($this->headerLocales($request) as $candidate) {
            if (LocaleRegistry::isActive($candidate)) {
                return $candidate;
            }
        }

        // 5. Default.
        return LocaleRegistry::default();
    }

    /** @return array<int,string> primary subtags from Accept-Language, in order */
    private function headerLocales(Request $request): array
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header === '') {
            return [];
        }

        preg_match_all('/([a-z]{2})(?:-[A-Za-z]{2})?/', strtolower($header), $matches);

        return $matches[1] ?? [];
    }
}
