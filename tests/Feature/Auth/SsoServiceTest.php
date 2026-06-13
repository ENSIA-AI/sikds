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

test('extract roles reads array of role-code strings', function () {
    config()->set('sso.roles_path', 'roles');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'extractRoles');
    $method->setAccessible(true);

    $codes = $method->invoke($service, [
        'roles' => ['skids_user', 'OTHER_ROLE'],
    ]);

    expect($codes)->toBe(['SKIDS_USER', 'OTHER_ROLE']);
});

test('extract roles reads array of objects with code field', function () {
    config()->set('sso.roles_path', 'roles');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'extractRoles');
    $method->setAccessible(true);

    $codes = $method->invoke($service, [
        'roles' => [
            ['id' => 1623, 'code' => 'SKIDS_USER', 'name' => 'SKIDS [user]'],
            ['id' => 1624, 'code' => 'SKIDS_MANAGER', 'name' => 'SKIDS [manager]'],
        ],
    ]);

    expect($codes)->toBe(['SKIDS_USER', 'SKIDS_MANAGER']);
});

test('extract roles returns empty array when path is missing or not iterable', function () {
    config()->set('sso.roles_path', 'roles');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'extractRoles');
    $method->setAccessible(true);

    expect($method->invoke($service, []))->toBe([]);
    expect($method->invoke($service, ['roles' => 'not-an-array']))->toBe([]);
});

test('extract roles honors a custom roles_path', function () {
    config()->set('sso.roles_path', 'application_roles.skids');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'extractRoles');
    $method->setAccessible(true);

    $codes = $method->invoke($service, [
        'application_roles' => [
            'skids' => ['SKIDS_MANAGER'],
        ],
    ]);

    expect($codes)->toBe(['SKIDS_MANAGER']);
});

test('extract roles reads ministry affectation role labels via wildcard path', function () {
    config()->set('sso.roles_path', 'individu.affectation.*.role.libelle_long_fr');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'extractRoles');
    $method->setAccessible(true);

    $codes = $method->invoke($service, [
        'individu' => [
            'affectation' => [
                ['role' => ['libelle_long_fr' => 'Secure Documentation Information and Management System [manager]']],
                ['role' => ['libelle_long_fr' => 'Etudiant']],
            ],
        ],
    ]);

    expect($codes)->toBe([
        'SECURE DOCUMENTATION INFORMATION AND MANAGEMENT SYSTEM [MANAGER]',
        'ETUDIANT',
    ]);
});

test('assert has authorized role rejects a profile with no application role label', function () {
    config()->set('sso.app_role_marker', 'secure documentation information and management system');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'assertHasAuthorizedRole');
    $method->setAccessible(true);

    $method->invoke($service, ['ETUDIANT', 'SOME OTHER SYSTEM [MANAGER]']);
})->throws(SsoAuthenticationException::class, 'Your SSO account is not authorized to access this application.');

test('assert has authorized role rejects profile with empty role list', function () {
    config()->set('sso.app_role_marker', 'secure documentation information and management system');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'assertHasAuthorizedRole');
    $method->setAccessible(true);

    $method->invoke($service, []);
})->throws(SsoAuthenticationException::class);

test('assert has authorized role accepts an application role label', function () {
    config()->set('sso.app_role_marker', 'secure documentation information and management system');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'assertHasAuthorizedRole');
    $method->setAccessible(true);

    $method->invoke($service, ['SECURE DOCUMENTATION INFORMATION AND MANAGEMENT SYSTEM [USER]']);

    expect(true)->toBeTrue();
});

test('resolve system role maps the admin qualifier to the super admin role', function () {
    config()->set('sso.app_role_marker', 'secure documentation information and management system');
    config()->set('sso.admin_role_qualifiers', ['[admin]']);
    config()->set('sso.manager_role_qualifiers', ['[manager]']);
    config()->set('sso.admin_system_role', 'Super Administrateur');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'resolveSystemRoleFromSso');
    $method->setAccessible(true);

    expect($method->invoke($service, ['SECURE DOCUMENTATION INFORMATION AND MANAGEMENT SYSTEM [ADMIN]']))
        ->toBe('Super Administrateur');
});

test('resolve system role prefers admin over manager when both are present', function () {
    config()->set('sso.app_role_marker', 'secure documentation information and management system');
    config()->set('sso.admin_role_qualifiers', ['[admin]']);
    config()->set('sso.manager_role_qualifiers', ['[manager]']);
    config()->set('sso.admin_system_role', 'Super Administrateur');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'resolveSystemRoleFromSso');
    $method->setAccessible(true);

    $roles = [
        'SECURE DOCUMENTATION INFORMATION AND MANAGEMENT SYSTEM [MANAGER]',
        'SECURE DOCUMENTATION INFORMATION AND MANAGEMENT SYSTEM [ADMIN]',
    ];

    expect($method->invoke($service, $roles))->toBe('Super Administrateur');
});

test('resolve system role maps the manager qualifier to the manager role', function () {
    config()->set('sso.app_role_marker', 'secure documentation information and management system');
    config()->set('sso.admin_role_qualifiers', ['[admin]']);
    config()->set('sso.manager_role_qualifiers', ['[manager]']);
    config()->set('sso.manager_system_role', 'Manager');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'resolveSystemRoleFromSso');
    $method->setAccessible(true);

    expect($method->invoke($service, ['SECURE DOCUMENTATION INFORMATION AND MANAGEMENT SYSTEM [MANAGER]']))
        ->toBe('Manager');
});

test('resolve system role falls back to the user role for a bare application label', function () {
    config()->set('sso.app_role_marker', 'secure documentation information and management system');
    config()->set('sso.admin_role_qualifiers', ['[admin]']);
    config()->set('sso.manager_role_qualifiers', ['[manager]']);
    config()->set('sso.user_system_role', 'User');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'resolveSystemRoleFromSso');
    $method->setAccessible(true);

    expect($method->invoke($service, ['SECURE DOCUMENTATION INFORMATION AND MANAGEMENT SYSTEM [USER]']))
        ->toBe('User');
});

test('resolve system role returns null when no label belongs to the application', function () {
    config()->set('sso.app_role_marker', 'secure documentation information and management system');

    $service = new SsoService();
    $method = new ReflectionMethod(SsoService::class, 'resolveSystemRoleFromSso');
    $method->setAccessible(true);

    expect($method->invoke($service, ['ETUDIANT', 'SOME OTHER SYSTEM [ADMIN]']))->toBeNull();
});

