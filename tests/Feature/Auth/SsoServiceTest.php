<?php

use App\Domain\Users\Exceptions\SsoAuthenticationException;
use App\Domain\Users\Services\SsoService;
use Illuminate\Http\Request;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutMiddleware();
});

test('redirect to provider excludes scope when scope is empty', function () {
    config()->set('sso.server', 'https://accounts.mesrs.dz');
    config()->set('sso.authorize_path', '/oauth/authorize');
    config()->set('sso.client_id', 'test-client-id');
    config()->set('sso.redirect_uri', 'http://sikds.test/callback');
    config()->set('sso.scope', '');

    $request = Request::create('/auth/redirect', 'GET');
    $request->setLaravelSession(app('session')->driver());

    $response = (new SsoService())->redirectToProvider($request);
    $location = $response->headers->get('Location');

    expect($location)->toStartWith('https://accounts.mesrs.dz/oauth/authorize?');
    expect($location)->toContain('client_id=test-client-id');
    expect($location)->toContain('redirect_uri=http%3A%2F%2Fsikds.test%2Fcallback');
    expect($location)->toContain('response_type=code');
    expect($location)->toContain('state=');
    expect($location)->not->toContain('scope=');
});

test('normalize profile maps nom_utilisateur and latin names', function () {
    config()->set('sso.allowed_domains', ['mesrs.dz']);

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'normalizeProfile');
    $method->setAccessible(true);

    $result = $method->invoke($service, [
        'nom_utilisateur' => 'u-12345',
        'email' => 'User@Mesrs.dz',
        'individu' => [
            'prenom_latin' => 'YASSIR',
            'nom_latin' => 'CHERDOUH',
        ],
    ]);

    expect($result['sso_user_id'])->toBe('u-12345');
    expect($result['username'])->toBe('u-12345');
    expect($result['email'])->toBe('user@mesrs.dz');
    expect($result['full_name'])->toBe('YASSIR CHERDOUH');
    expect($result['auth_domain'])->toBe('mesrs.dz');
});

test('normalize profile uses nom_utilisateur mesrs fallback when email is missing', function () {
    config()->set('sso.allowed_domains', ['mesrs.dz']);

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'normalizeProfile');
    $method->setAccessible(true);

    $result = $method->invoke($service, [
        'nom_utilisateur' => '202333087808',
        'individu' => [
            'prenom_latin' => 'YASSIR',
            'nom_latin' => 'CHERDOUH',
        ],
    ]);

    expect($result['email'])->toBe('202333087808@mesrs.dz');
    expect($result['auth_domain'])->toBe('mesrs.dz');
});

test('normalize profile throws on missing nom_utilisateur', function () {
    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'normalizeProfile');
    $method->setAccessible(true);

    $method->invoke($service, [
        'email' => 'user@mesrs.dz',
    ]);
})->throws(SsoAuthenticationException::class, 'SSO profile is missing mandatory username or email.');

test('normalize profile allows configured email domain', function () {
    config()->set('sso.allowed_domains', ['mesrs.dz', 'univ.dz']);

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'normalizeProfile');
    $method->setAccessible(true);

    $result = $method->invoke($service, [
        'nom_utilisateur' => 'u-allowed',
        'email' => 'allowed@univ.dz',
        'individu' => [
            'prenom_latin' => 'Allowed',
            'nom_latin' => 'User',
        ],
    ]);

    expect($result['email'])->toBe('allowed@univ.dz');
    expect($result['auth_domain'])->toBe('univ.dz');
});

test('normalize profile rejects email domain outside allowlist', function () {
    config()->set('sso.allowed_domains', ['mesrs.dz']);

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'normalizeProfile');
    $method->setAccessible(true);

    $method->invoke($service, [
        'nom_utilisateur' => 'u-denied',
        'email' => 'user@other.dz',
        'individu' => [
            'prenom_latin' => 'Denied',
            'nom_latin' => 'User',
        ],
    ]);
})->throws(SsoAuthenticationException::class, 'Your email domain is not authorized for SSO access.');

test('normalize profile allows all domains when allowlist is empty', function () {
    config()->set('sso.allowed_domains', []);

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'normalizeProfile');
    $method->setAccessible(true);

    $result = $method->invoke($service, [
        'nom_utilisateur' => 'u-open',
        'email' => 'user@future-domain.dz',
        'individu' => [
            'prenom_latin' => 'Open',
            'nom_latin' => 'User',
        ],
    ]);

    expect($result['email'])->toBe('user@future-domain.dz');
    expect($result['auth_domain'])->toBe('future-domain.dz');
});

