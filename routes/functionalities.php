<?php

use App\Http\Controllers\Common\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
    });
