<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LocalLoginController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

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
            $this->audit->record(
                eventType: 'auth.login.failed',
                result: 'failed',
                resourceType: 'user',
                metadata: [
                    'auth_type' => 'local',
                    'attempted_email' => (string) $request->email,
                    'reason' => 'invalid_credentials_or_inactive',
                ],
                request: $request,
            );

            throw ValidationException::withMessages([
                'email' => __('Ces identifiants ne correspondent pas à nos enregistrements.'),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $this->audit->record(
            eventType: 'auth.login.success',
            user: $user,
            resourceType: 'user',
            resourceId: $user->id,
            metadata: [
                'auth_type' => 'local',
            ],
            request: $request,
        );

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
