<?php

use App\Domain\Users\Services\SsoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::post('logout', function () {
    Auth::guard('web')->logout();
    Session::invalidate();
    Session::regenerateToken();

    return redirect('/');
})->name('logout');

Route::get('/login_sso', function (SsoService $ssoService) {
    return $ssoService->redirectToProvider(request());
})->name('login');

Route::get('/callback', function (SsoService $ssoService) {
    try {
        $ssoService->handleCallback(request());

        return redirect('/dashboard');
    } catch (\Throwable $e) {
        report($e);

        return redirect('/')->with('error', $e->getMessage());
    }
});

Route::get('/user', function () {
    if (! Auth::check()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    return response()->json([
        'id' => Auth::id(),
        'username' => Auth::user()->username,
        'email' => Auth::user()->email,
        'full_name' => Auth::user()->full_name,
    ]);
})->name('user');
