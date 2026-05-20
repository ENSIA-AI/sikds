<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Users\Enums\SsoFailureReason;
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
    $response->assertSessionHas('error', 'La connexion SSO a échoué. Veuillez réessayer ou contacter le support.');

    $log = AuditLog::query()->firstOrFail();
    expect($log->metadata['reason'])->toBe('sso_authentication_failed')
        ->and($log->metadata['message'])->toBe('internal detail');
});

test('callback returns unauthorized message when sso roles are not authorized', function () {
    $this->mock(SsoService::class, function ($mock): void {
        $mock->shouldReceive('handleCallback')
            ->once()
            ->andThrow(SsoAuthenticationException::forReason(
                SsoFailureReason::UnauthorizedSsoRole,
                'Your SSO account is not authorized to access this application.'
            ));
    });

    $response = $this->get('/callback?code=abc&state=123');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas(
        'error',
        'Accès non autorisé. Votre compte SSO ne possède pas l’un des rôles requis pour ce système.'
    );

    $log = AuditLog::query()->firstOrFail();
    expect($log->metadata['reason'])->toBe('unauthorized_sso_role')
        ->and($log->metadata['message'])->toBe('Your SSO account is not authorized to access this application.');
});

test('callback returns expected safe messages for typed sso failures', function (
    SsoFailureReason $reason,
    string $expectedMessage
) {
    $this->mock(SsoService::class, function ($mock) use ($reason): void {
        $mock->shouldReceive('handleCallback')
            ->once()
            ->andThrow(SsoAuthenticationException::forReason($reason, $reason->value));
    });

    $response = $this->get('/callback?code=abc&state=123');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', $expectedMessage);

    $log = AuditLog::query()->firstOrFail();
    expect($log->metadata['reason'])->toBe($reason->value);
})->with([
    'invalid callback' => [
        SsoFailureReason::InvalidCallback,
        'La réponse SSO est invalide ou a expiré. Veuillez réessayer.',
    ],
    'unauthorized email domain' => [
        SsoFailureReason::UnauthorizedEmailDomain,
        'Votre domaine email n’est pas autorisé pour accéder à ce système.',
    ],
    'deactivated account' => [
        SsoFailureReason::DeactivatedAccount,
        'Votre compte est désactivé. Veuillez contacter un administrateur.',
    ],
]);

test('callback returns generic safe message on unexpected exception', function () {
    $this->mock(SsoService::class, function ($mock): void {
        $mock->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new RuntimeException('sensitive database error'));
    });

    $response = $this->get('/callback?code=abc&state=123');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Erreur d’authentification inattendue. Veuillez réessayer.');
});

