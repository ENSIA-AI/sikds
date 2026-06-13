<?php

use App\Domain\Users\Exceptions\SsoAuthenticationException;
use App\Domain\Users\Services\SsoService;
use App\Http\Controllers\Auth\LocalLoginController;
use App\Domain\Audit\Services\AuditService;
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
})->middleware('throttle:sso')->name('sso.redirect');

Route::middleware('throttle:sso')->get('/callback', function (SsoService $ssoService, AuditService $audit) {
    try {
        $user = $ssoService->handleCallback(request());

        $audit->record(
            eventType: 'auth.login.success',
            user: $user,
            resourceType: 'user',
            resourceId: $user->id,
            metadata: [
                'auth_type' => 'sso',
            ],
            request: request(),
        );

        return redirect('/dashboard');
    } catch (SsoAuthenticationException $e) {
        $audit->record(
            eventType: 'auth.login.failed',
            result: 'failed',
            resourceType: 'user',
            metadata: [
                'auth_type' => 'sso',
                'reason' => $e->auditReason(),
                'message' => $e->getMessage(),
                'exception' => class_basename($e),
            ],
            request: request(),
        );

        if ($e->shouldReport()) {
            report($e);
        }

        return redirect()->route('login')->with('error', __($e->publicMessageKey()));
    } catch (\Throwable $e) {
        $audit->record(
            eventType: 'auth.login.failed',
            result: 'failed',
            resourceType: 'user',
            metadata: [
                'auth_type' => 'sso',
                'reason' => 'unexpected_error',
                'message' => $e->getMessage(),
                'exception' => class_basename($e),
            ],
            request: request(),
        );

        report($e);

        return redirect()->route('login')->with('error', __('Erreur d’authentification inattendue. Veuillez réessayer.'));
    }
})->name('sso.callback');

Route::middleware('auth')->get('/user', function () {
    $user = Auth::user();

    return response()->json([
        'id' => $user->id,
        'username' => $user->username,
        'email' => $user->email,
        'full_name' => $user->full_name,
    ]);
})->name('user');

// Local development login — not available in production
if (app()->environment('local')) {
    Route::get('/login/local', [LocalLoginController::class, 'showLoginForm'])->name('login.local');
    Route::post('/login/local', [LocalLoginController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.local.post');
}
