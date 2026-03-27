<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::post('logout', function () {
    Auth::guard('web')->logout();
    Session::invalidate();
    Session::regenerateToken();

    return redirect('/');
})->name('logout');

Route::get('/login_sso', function () {
    abort(501, 'SSO not implemented yet.');
})->name('login');

Route::get('/callback', function () {
    abort(501, 'SSO callback not implemented yet.');
});

Route::get('/user', function () {
    abort(501, 'SSO user endpoint not implemented yet.');
})->name('user');
