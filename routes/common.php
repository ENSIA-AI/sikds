<?php

use App\Http\Controllers\Common\LanguageController;
use Illuminate\Support\Facades\Route;

// Make language change route publicly accessible so guests can switch locale
Route::get('change-language/{lang}', [LanguageController::class, 'changeLanguage'])
    ->name('changeLanguage');

Route::group(['middleware' => 'auth'], function () {})->middleware(['auth']);
