<?php

use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
});

test('login page route is registered', function () {
    expect(route('login', absolute: false))->toBe('/login');
});

test('sso redirect route points to oauth entry', function () {
    expect(route('sso.redirect', absolute: false))->toBe('/auth/redirect');
});

test('login page renders for guests', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('Continuer avec le SSO', false);
});

test('users can logout', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');

    $this->assertGuest();
});
