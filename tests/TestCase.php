<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Feature tests often POST JSON to `web` routes without a session CSRF token.
     *
     * Laravel 13+ registers {@see \Illuminate\Foundation\Http\Middleware\PreventRequestForgery}
     * in the `web` stack instead of ValidateCsrfToken; disabling the old class has no effect, which
     * produced HTTP 419 on JSON POSTs to routes like `documents/{id}/forward`.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Views (including error pages) reference @vite; CI jobs without a
        // front-end build have no public/build/manifest.json, which turns any
        // rendered page — e.g. a 403 — into a 500.
        $this->withoutVite();

        // Keep feature tests deterministic: many assertions expect French UI copy.
        config()->set('app.locale', 'fr');
        config()->set('app.fallback_locale', 'fr');
        app()->setLocale('fr');
        $this->defaultHeaders['Accept-Language'] = 'fr';

        if (class_exists(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        }
    }
}
