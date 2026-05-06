<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    public function changeLanguage(Request $request, string $lang): RedirectResponse
    {
        $languages = config('languages.lang', []);
        if (! array_key_exists($lang, $languages)) {
            abort(404);
        }

        App::setLocale($lang);
        $request->session()->put('locale', $lang);

        $cookie = cookie(SetLocale::COOKIE_NAME, $lang, 60 * 24 * 365);

        return redirect()->back()->withCookie($cookie);
    }
}
