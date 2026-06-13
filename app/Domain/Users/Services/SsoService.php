<?php

declare(strict_types=1);

namespace App\Domain\Users\Services;

use App\Domain\Users\Enums\SsoFailureReason;
use App\Domain\Users\Exceptions\SsoAuthenticationException;
use App\Domain\Users\Models\User;
use App\Domain\Users\Models\Role;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SsoService
{
    public function redirectToProvider(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('sso_state', $state);

        $queryParams = [
            'client_id' => config('sso.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('sso.redirect_uri'),
            'state' => $state,
        ];
        $scope = trim((string) config('sso.scope', ''));
        if ($scope !== '') {
            $queryParams['scope'] = $scope;
        }
        $query = http_build_query($queryParams);

        return redirect($this->buildUrl(config('sso.authorize_path')).'?'.$query);
    }

    public function handleCallback(Request $request): User
    {
        $state = (string) $request->session()->pull('sso_state');
        $incomingState = (string) $request->input('state', '');
        $code = (string) $request->input('code', '');

        if ($state === '' || ! hash_equals($state, $incomingState) || $code === '') {
            throw SsoAuthenticationException::forReason(
                SsoFailureReason::InvalidCallback,
                'Invalid SSO callback state or authorization code.'
            );
        }
        $tokenResponse = $this->exchangeAuthorizationCode($code);
        $accessToken = (string) data_get($tokenResponse->json(), 'access_token', '');

        if ($accessToken === '') {
            throw new SsoAuthenticationException('Missing access token from SSO token response.');
        }

        $request->session()->put('sso_access_token', $accessToken);

        $profileResponse = $this->fetchUserProfile($accessToken);
        if (! $profileResponse->successful()) {
            throw new SsoAuthenticationException('Failed to fetch SSO user profile.');
        }

        $normalized = $this->normalizeProfile($profileResponse->json());
        $this->assertHasAuthorizedRole($normalized['sso_roles']);

        return DB::transaction(function () use ($normalized, $profileResponse): User {
            $user = User::query()
                ->when($normalized['sso_user_id'] !== null, function ($query) use ($normalized) {
                    $query->where('sso_user_id', $normalized['sso_user_id']);
                }, function ($query) use ($normalized) {
                    $query->where('email', $normalized['email']);
                })
                ->first();

            if (! $user) {
                $institutionId = DB::table('institutions')->where('code', 'MESRS')->value('id');
                if (! $institutionId) {
                    throw new SsoAuthenticationException('No default institution found for auto-provisioning.');
                }

                $user = User::query()->create([
                    'sso_user_id' => $normalized['sso_user_id'],
                    'username' => $normalized['username'],
                    'email' => $normalized['email'],
                    'full_name' => $normalized['full_name'],
                    'institution_id' => $institutionId,
                    'auth_type' => 'sso',
                    'auth_domain' => $normalized['auth_domain'],
                    'sso_profile' => $profileResponse->json(),
                    'password' => null,
                    'is_active' => true,
                    'last_login_at' => now(),
                ]);
            } else {
                $user->fill([
                    'sso_user_id' => $normalized['sso_user_id'] ?? $user->sso_user_id,
                    'username' => $normalized['username'],
                    'email' => $normalized['email'],
                    'full_name' => $normalized['full_name'],
                    'auth_domain' => $normalized['auth_domain'],
                    'sso_profile' => $profileResponse->json(),
                    'last_login_at' => now(),
                ])->save();
            }

            if (! $user->is_active) {
                throw SsoAuthenticationException::forReason(
                    SsoFailureReason::DeactivatedAccount,
                    'Your account is deactivated. Contact an administrator.'
                );
            }

            $this->assignRoleFromSso($user, $normalized['sso_roles']);
            Auth::guard('web')->login($user);

            return $user;
        });
    }

    public function fetchCurrentUserProfile(Request $request): Response
    {
        $accessToken = (string) $request->session()->get('sso_access_token', '');
        if ($accessToken === '') {
            throw new SsoAuthenticationException('No SSO access token found in session.');
        }

        return $this->fetchUserProfile($accessToken);
    }

    private function exchangeAuthorizationCode(string $code): Response
    {
        $tokenUrl = $this->buildUrl(config('sso.token_path'));
        $payload = [
            'grant_type' => 'authorization_code',
            'client_id' => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'redirect_uri' => config('sso.redirect_uri'),
            'code' => $code,
        ];

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post($tokenUrl, $payload);
        } catch (Throwable $e) {
            throw new SsoAuthenticationException('SSO token exchange failed.', previous: $e);
        }

        // Some OAuth servers require client auth via HTTP Basic instead of body params.
        if (! $response->successful() && data_get($response->json(), 'error') === 'invalid_client') {
            try {
                $response = Http::asForm()
                    ->withBasicAuth((string) config('sso.client_id'), (string) config('sso.client_secret'))
                    ->timeout(10)
                    ->post($tokenUrl, [
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => config('sso.redirect_uri'),
                        'code' => $code,
                    ]);
            } catch (Throwable $e) {
                throw new SsoAuthenticationException('SSO token exchange failed.', previous: $e);
            }
        }

        if (! $response->successful()) {
            throw new SsoAuthenticationException('SSO token exchange failed.');
        }

        return $response;
    }

    private function fetchUserProfile(string $accessToken): Response
    {
        try {
            return Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(10)
                ->get($this->buildUrl(config('sso.userinfo_path')));
        } catch (Throwable $e) {
            throw new SsoAuthenticationException('Failed to fetch SSO user profile.', previous: $e);
        }
    }

    private function normalizeProfile(array $profile): array
    {
        $nomUtilisateur = trim((string) data_get($profile, 'nom_utilisateur', ''));
        $ssoUserId = $nomUtilisateur !== '' ? $nomUtilisateur : null;
        $username = $nomUtilisateur;

        $email = trim((string) data_get($profile, 'email', ''));
        if ($email === '' && $nomUtilisateur !== '') {
            $email = Str::lower($nomUtilisateur).'@mesrs.dz';
        }

        $fullName = trim((string) (
            data_get($profile, 'individu.prenom_latin', '').' '.data_get($profile, 'individu.nom_latin', '')
        ));

        if (trim($fullName) === '') {
            $fullName = $username;
        }

        if ($username === '' || $email === '') {
            throw new SsoAuthenticationException('SSO profile is missing mandatory username or email.');
        }

        $this->assertDomainIsAllowed($email);

        return [
            'sso_user_id' => $ssoUserId !== null ? (string) $ssoUserId : null,
            'username' => $username,
            'email' => Str::lower($email),
            'full_name' => $fullName,
            'auth_domain' => ($domain = Str::lower(Str::after($email, '@'))) !== '' ? $domain : null,
            'sso_roles' => $this->extractRoles($profile),
        ];
    }

    /**
     * Pull role codes from the SSO profile, accepting both arrays of strings and
     * arrays of objects exposing a `code` (or `name`) field.
     *
     * @return array<int, string> uppercase, deduplicated role codes
     */
    private function extractRoles(array $profile): array
    {
        $path = (string) config('sso.roles_path', 'roles');
        $raw = data_get($profile, $path, []);
        if (! is_array($raw)) {
            return [];
        }

        $codes = [];
        foreach ($raw as $entry) {
            if (is_string($entry)) {
                $code = trim($entry);
            } elseif (is_array($entry)) {
                $code = trim((string) ($entry['code'] ?? $entry['name'] ?? ''));
            } else {
                continue;
            }
            if ($code !== '') {
                $codes[] = Str::upper($code);
            }
        }

        return array_values(array_unique($codes));
    }

    private function buildUrl(?string $path): string
    {
        $server = rtrim((string) config('sso.server'), '/');
        $path = '/'.ltrim((string) $path, '/');

        return $server.$path;
    }

    private function assertDomainIsAllowed(string $email): void
    {
        $allowedDomains = config('sso.allowed_domains', []);
        if (! is_array($allowedDomains) || $allowedDomains === []) {
            return;
        }

        $emailDomain = strtolower((string) Str::after($email, '@'));
        if ($emailDomain === '' || ! in_array($emailDomain, $allowedDomains, true)) {
            throw SsoAuthenticationException::forReason(
                SsoFailureReason::UnauthorizedEmailDomain,
                'Your email domain is not authorized for SSO access.'
            );
        }

    }

    /**
     * Reject SSO logins whose profile carries no role label for this application
     * (i.e. none of the labels contains the configured `sso.app_role_marker`).
     *
     * @param  array<int, string>  $roleCodes  uppercase role labels from the SSO profile
     */
    private function assertHasAuthorizedRole(array $roleCodes): void
    {
        if ($this->resolveSystemRoleFromSso($roleCodes) === null) {
            throw SsoAuthenticationException::forReason(
                SsoFailureReason::UnauthorizedSsoRole,
                'Your SSO account is not authorized to access this application.'
            );
        }
    }

    /**
     * Assign a system role on first login based on the SSO role label. Users who
     * already have a role (e.g. the system admin seeded directly into the DB)
     * keep their existing role.
     *
     * @param  array<int, string>  $roleCodes  uppercase role labels from the SSO profile
     */
    private function assignRoleFromSso(User $user, array $roleCodes): void
    {
        if ($user->roles()->exists()) {
            return;
        }

        $systemRoleName = $this->resolveSystemRoleFromSso($roleCodes);
        if ($systemRoleName === null) {
            return;
        }

        $role = Role::query()
            ->where('name', $systemRoleName)
            ->where('guard_name', 'web')
            ->first();

        if ($role) {
            $user->assignRole($role);
        }
    }

    /**
     * Resolve the system role name for this application from the SSO role labels.
     *
     * A label grants access when it contains `sso.app_role_marker`. Among the
     * matching labels, bracketed qualifiers select the system role in priority
     * order: admin qualifiers win over manager qualifiers; a label with neither
     * maps to the user system role. Returns null when no label belongs to this
     * application.
     *
     * @param  array<int, string>  $roleCodes  uppercase role labels from the SSO profile
     */
    private function resolveSystemRoleFromSso(array $roleCodes): ?string
    {
        $marker = Str::upper(trim((string) config('sso.app_role_marker', '')));
        if ($marker === '') {
            return null;
        }

        $appLabels = array_filter(
            $roleCodes,
            static fn (string $label): bool => str_contains($label, $marker)
        );

        if ($appLabels === []) {
            return null;
        }

        if ($this->labelsMatchQualifier($appLabels, (array) config('sso.admin_role_qualifiers', []))) {
            return (string) config('sso.admin_system_role', 'Super Administrateur');
        }

        if ($this->labelsMatchQualifier($appLabels, (array) config('sso.manager_role_qualifiers', []))) {
            return (string) config('sso.manager_system_role', 'Manager');
        }

        return (string) config('sso.user_system_role', 'User');
    }

    /**
     * Whether any of the given (uppercase) labels contains any of the configured
     * qualifiers.
     *
     * @param  array<int, string>  $labels      uppercase role labels
     * @param  array<int, string>  $qualifiers  qualifiers to match (any case)
     */
    private function labelsMatchQualifier(array $labels, array $qualifiers): bool
    {
        foreach ($qualifiers as $qualifier) {
            $needle = Str::upper(trim((string) $qualifier));
            if ($needle === '') {
                continue;
            }
            foreach ($labels as $label) {
                if (str_contains($label, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }
}
