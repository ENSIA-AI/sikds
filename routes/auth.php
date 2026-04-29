<?php

use App\Domain\Users\Exceptions\SsoAuthenticationException;
use App\Domain\Users\Services\SsoService;
use App\Http\Controllers\Auth\LocalLoginController;
use App\Domain\Audit\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::post('logout', function () {
    Auth::guard('web')->logout();
    Session::invalidate();
    Session::regenerateToken();

    return redirect()->route('login')->with('success', 'Déconnexion réussie.');
})->name('logout');

Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login-minimal');
})->name('login');

Route::get('/auth/redirect', function (SsoService $ssoService) {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return $ssoService->redirectToProvider(request());
})->name('sso.redirect');

Route::get('/callback', function (SsoService $ssoService) {
    try {
        $user = $ssoService->handleCallback(request());

        AuditLog::query()->create([
            'event_type' => 'auth.login.success',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'metadata' => [
                'auth_type' => 'sso',
            ],
            'result' => 'success',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return redirect('/dashboard');
    } catch (SsoAuthenticationException $e) {
        AuditLog::query()->create([
            'event_type' => 'auth.login.failed',
            'user_id' => null,
            'user_email' => null,
            'resource_type' => 'user',
            'resource_id' => null,
            'metadata' => [
                'auth_type' => 'sso',
                'reason' => $e->getMessage(),
                'exception' => class_basename($e),
            ],
            'result' => 'failed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        report($e);

        return redirect()->route('login')->with('error', 'SSO login failed. Please try again or contact support.');
    } catch (\Throwable $e) {
        AuditLog::query()->create([
            'event_type' => 'auth.login.failed',
            'user_id' => null,
            'user_email' => null,
            'resource_type' => 'user',
            'resource_id' => null,
            'metadata' => [
                'auth_type' => 'sso',
                'reason' => 'unexpected_error',
                'exception' => class_basename($e),
            ],
            'result' => 'failed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        report($e);

        return redirect()->route('login')->with('error', 'Unexpected authentication error. Please try again.');
    }
})->name('sso.callback');

Route::get('/user', function () {
    if (!Auth::check()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    return response()->json([
        'id' => Auth::id(),
        'username' => Auth::user()->username,
        'email' => Auth::user()->email,
        'full_name' => Auth::user()->full_name,
    ]);
})->name('user');

// Local development login — not available in production
if (app()->environment('local')) {
    Route::get('/login/local', [LocalLoginController::class, 'showLoginForm'])->name('login.local');
    Route::post('/login/local', [LocalLoginController::class, 'login'])->name('login.local.post');
}
