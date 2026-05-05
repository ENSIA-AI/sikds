<?php

use App\Http\Controllers\Common\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('change-language/{lang}', [LanguageController::class, 'changeLanguage'])
    ->name('changeLanguage');

require __DIR__. '/common.php';
require __DIR__.'/auth.php';
require __DIR__.'/functionalities.php';

Route::get('/', function () {
    return view('welcome');
})->name('home');
