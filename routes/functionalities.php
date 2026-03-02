<?php


use App\Http\Controllers\Common\DashboardController;
use Illuminate\Support\Facades\Route;

/* display all residances */



Route::middleware(['auth'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
    });
