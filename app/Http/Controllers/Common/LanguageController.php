<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function changeLanguage($lang): RedirectResponse
    {
        if (! array_key_exists($lang, config('languages.lang'))) {
            return redirect()->back();
        }

        App::setLocale($lang);

        Session::put('applocale', $lang);

        Session::regenerate();

        return redirect()->back();
    }
}
