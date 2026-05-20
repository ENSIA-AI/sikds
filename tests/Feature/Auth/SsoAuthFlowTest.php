<?php

use App\Domain\Users\Exceptions\SsoAuthenticationException;
use App\Domain\Users\Services\SsoService;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutMiddleware();
});

test('callback returns safe message on known sso authentication exception', function () {
    $this->mock(SsoService::class, function ($mock): void {
        $mock->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new SsoAuthenticationException('internal detail'));
    });

    $response = $this->get('/callback?code=abc&state=123');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'SSO login failed. Please try again or contact support.');
});

test('callback returns unauthorized message when sso roles are not authorized', function () {
    $this->mock(SsoService::class, function ($mock): void {
        $mock->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new SsoAuthenticationException(
                'Your SSO account is not authorized to access this application.',
                SsoAuthenticationException::UNAUTHORIZED_SSO_ROLE
            ));
    });

    $response = $this->get('/callback?code=abc&state=123');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas(
        'error',
        'Unauthorized access. Your SSO account does not have one of the required roles for this system.'
    );
});

test('callback returns generic safe message on unexpected exception', function () {
    $this->mock(SsoService::class, function ($mock): void {
        $mock->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new RuntimeException('sensitive database error'));
    });

    $response = $this->get('/callback?code=abc&state=123');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Unexpected authentication error. Please try again.');
});

