<?php

use App\Domain\Users\Exceptions\SsoAuthenticationException;
use App\Domain\Users\Services\SsoService;
use App\Http\Controllers\Auth\LocalLoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::post('logout', function () {
    Auth::guard('web')->logout();
    Session::invalidate();
    Session::regenerateToken();

    return redirect('/');
})->name('logout');

Route::get('/login', function () {
    return view('auth.login-minimal');
})->name('login');

Route::get('/auth/redirect', function (SsoService $ssoService) {
    return $ssoService->redirectToProvider(request());
})->name('sso.redirect');

Route::get('/auth/callback', function (SsoService $ssoService) {
    try {
        $ssoService->handleCallback(request());

        return redirect('/dashboard');
    } catch (SsoAuthenticationException $e) {
        report($e);

        return redirect()->route('login')->with('error', 'SSO login failed. Please try again or contact support.');
    } catch (\Throwable $e) {
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
