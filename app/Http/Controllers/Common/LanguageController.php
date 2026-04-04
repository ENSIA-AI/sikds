<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    public function changeLanguage(string $lang): RedirectResponse
    {
        $languages = config('languages.lang', []);
        if (! array_key_exists($lang, $languages)) {
            abort(404);
        }

        App::setLocale($lang);
        session(['locale' => $lang]);

        return redirect()->back();
    }
}
