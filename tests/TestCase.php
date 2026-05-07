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

        if (class_exists(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        }
    }
}
