<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LocalLoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login-minimal');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)
            ->where('auth_type', 'local')
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            AuditLog::query()->create([
                'event_type' => 'auth.login.failed',
                'user_id' => null,
                'user_email' => (string) $request->email,
                'resource_type' => 'user',
                'resource_id' => null,
                'metadata' => [
                    'auth_type' => 'local',
                    'reason' => 'invalid_credentials_or_inactive',
                ],
                'result' => 'failed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::query()->create([
            'event_type' => 'auth.login.success',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'metadata' => [
                'auth_type' => 'local',
            ],
            'result' => 'success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->intended('/dashboard');
    }
    
    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['is_active' => true] // Only allow active users to login
        );
    }
}
