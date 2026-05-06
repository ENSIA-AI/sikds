<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported languages
    |--------------------------------------------------------------------------
    | Map of locale code => display label. Used by the language switcher and
    | by App\Http\Middleware\SetLocale to validate incoming locale values.
    */
    'lang' => [
        'fr' => 'Français',
        'en' => 'English',
        'ar' => 'العربية',
    ],

    /*
    |--------------------------------------------------------------------------
    | Right-to-left locales
    |--------------------------------------------------------------------------
    | Locale codes whose UI must render with dir="rtl". The layout reads this
    | list to set the <html dir> attribute and to enable RTL CSS overrides.
    */
    'rtl' => ['ar'],
];
