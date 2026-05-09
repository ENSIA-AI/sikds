<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const COOKIE_NAME = 'sikds_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('languages.lang', ['fr' => 'Français']));
        $default = (string) config('app.locale');

        $locale = $request->session()->get('locale')
            ?? $request->cookie(self::COOKIE_NAME)
            ?? $this->fromAcceptLanguage($request, $supported)
            ?? $default;

        if (! in_array($locale, $supported, true)) {
            $locale = $default;
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }

    private function fromAcceptLanguage(Request $request, array $supported): ?string
    {
        foreach ($request->getLanguages() as $lang) {
            $primary = strtolower(substr((string) $lang, 0, 2));
            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return null;
    }
}
