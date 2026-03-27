<?php

declare(strict_types=1);

namespace App\Domain\Users\Services;

use App\Domain\Users\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SsoService
{
    public function redirectToProvider(Request $request): \Illuminate\Http\RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('sso_state', $state);

        $query = http_build_query([
            'client_id' => config('sso.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('sso.redirect_uri'),
            'scope' => config('sso.scope', 'openid profile email'),
            'state' => $state,
        ]);

        return redirect($this->buildUrl(config('sso.authorize_path')).'?'.$query);
    }

    public function handleCallback(Request $request): User
    {
        $state = (string) $request->session()->pull('sso_state');
        $incomingState = (string) $request->input('state', '');
        $code = (string) $request->input('code', '');

        if ($state === '' || ! hash_equals($state, $incomingState) || $code === '') {
            throw new RuntimeException('Invalid SSO callback state or authorization code.');
        }

        $tokenResponse = $this->exchangeAuthorizationCode($code);
        $accessToken = (string) data_get($tokenResponse->json(), 'access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('Missing access token from SSO token response.');
        }

        $request->session()->put('sso_access_token', $accessToken);

        $profileResponse = $this->fetchUserProfile($accessToken);
        if (! $profileResponse->successful()) {
            throw new RuntimeException('Failed to fetch SSO user profile.');
        }

        $normalized = $this->normalizeProfile($profileResponse->json());

        return DB::transaction(function () use ($normalized): User {
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
                    throw new RuntimeException('No default institution found for auto-provisioning.');
                }

                $user = User::query()->create([
                    'sso_user_id' => $normalized['sso_user_id'],
                    'username' => $normalized['username'],
                    'email' => $normalized['email'],
                    'full_name' => $normalized['full_name'],
                    'institution_id' => $institutionId,
                    'auth_type' => 'sso',
                    'auth_domain' => $normalized['auth_domain'],
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
                    'last_login_at' => now(),
                ])->save();
            }

            if (! $user->is_active) {
                throw new RuntimeException('Your account is deactivated. Contact an administrator.');
            }

            Auth::guard('web')->login($user);

            return $user;
        });
    }

    public function fetchCurrentUserProfile(Request $request): Response
    {
        $accessToken = (string) $request->session()->get('sso_access_token', '');
        if ($accessToken === '') {
            throw new RuntimeException('No SSO access token found in session.');
        }

        return $this->fetchUserProfile($accessToken);
    }

    private function exchangeAuthorizationCode(string $code): Response
    {
        $response = Http::asForm()->post($this->buildUrl(config('sso.token_path')), [
            'grant_type' => 'authorization_code',
            'client_id' => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'redirect_uri' => config('sso.redirect_uri'),
            'code' => $code,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('SSO token exchange failed.');
        }

        return $response;
    }

    private function fetchUserProfile(string $accessToken): Response
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->get($this->buildUrl(config('sso.userinfo_path')));
    }

    private function normalizeProfile(array $profile): array
    {
        $ssoUserId = data_get($profile, 'sub')
            ?? data_get($profile, 'id')
            ?? data_get($profile, 'user_id')
            ?? data_get($profile, 'unique_id');

        $username = (string) (data_get($profile, 'username')
            ?? data_get($profile, 'preferred_username')
            ?? data_get($profile, 'nom_utilisateur')
            ?? data_get($profile, 'login')
            ?? '');

        $email = (string) (data_get($profile, 'email') ?? '');

        $fullName = (string) (data_get($profile, 'full_name')
            ?? data_get($profile, 'name')
            ?? data_get($profile, 'display_name')
            ?? $username);

        if ($username === '' || $email === '') {
            throw new RuntimeException('SSO profile is missing mandatory username or email.');
        }

        return [
            'sso_user_id' => $ssoUserId !== null ? (string) $ssoUserId : null,
            'username' => $username,
            'email' => Str::lower($email),
            'full_name' => $fullName,
            'auth_domain' => Str::after($email, '@') ?: null,
        ];
    }

    private function buildUrl(?string $path): string
    {
        $server = rtrim((string) config('sso.server'), '/');
        $path = '/'.ltrim((string) $path, '/');

        return $server.$path;
    }
}

