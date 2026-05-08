<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('SIKDS — Connexion') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">

<div class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-blue-900 flex items-center justify-center relative overflow-hidden">

    <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full opacity-5 -translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 right-0 w-[32rem] h-[32rem] bg-white rounded-full opacity-5 translate-x-1/3 translate-y-1/3"></div>
    <div class="absolute top-1/2 left-1/4 w-64 h-64 bg-white rounded-full opacity-5"></div>

    <div class="absolute top-4 right-6 flex items-center space-x-3">
        <a href="{{ route('changeLanguage', ['lang' => 'fr']) }}" class="text-white text-xs opacity-70 hover:opacity-100 transition-opacity">FR</a>
        <span class="text-white opacity-30 text-xs">|</span>
        <a href="{{ route('changeLanguage', ['lang' => 'ar']) }}" class="text-white text-xs opacity-70 hover:opacity-100 transition-opacity">AR</a>
        <span class="text-white opacity-30 text-xs">|</span>
        <a href="{{ route('changeLanguage', ['lang' => 'en']) }}" class="text-white text-xs opacity-70 hover:opacity-100 transition-opacity">EN</a>
    </div>

    <div class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8">

        <div class="flex flex-col items-center mb-6">
            <div class="w-16 h-16 rounded-full bg-blue-900 flex items-center justify-center mb-4">
                <span class="text-white font-bold text-sm tracking-wide">MESRS</span>
            </div>
            <h1 class="text-2xl font-bold text-blue-900">SIKDS</h1>
            <p class="text-gray-400 text-sm text-center mt-1">
                {{ __('Système Institutionnel de Gestion Documentaire') }}
            </p>
        </div>

        <div class="border-t border-gray-100 mb-6"></div>

        @if(session('success'))
            <div class="mb-4">
                <x-alert-item
                    type="success"
                    :message="session('success')"
                    :timestamp="now()->format('H:i')"
                />
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4">
                <x-alert-item
                    type="danger"
                    :message="session('error')"
                    :timestamp="now()->format('H:i')"
                />
            </div>
        @endif

        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider text-center mb-3">
                {{ __('Authentification institutionnelle') }}
            </p>
            <a
                href="{{ route('sso.redirect') }}"
                class="w-full bg-blue-900 hover:bg-blue-800 text-white rounded-xl py-3 font-medium flex items-center justify-center gap-2 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                {{ __('Continuer avec le SSO MESRS') }}
            </a>
        </div>

        @if(app()->environment('local'))
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-3 bg-white text-gray-400 uppercase tracking-wider">
                        {{ __('Développement local uniquement') }}
                    </span>
                </div>
            </div>

            <form class="mt-5 space-y-4" method="POST" action="{{ route('login.local.post') }}">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600 mb-1">{{ __('Email') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', 'admin@mesrs.dz') }}"
                        required
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    />
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600 mb-1">{{ __('Mot de passe') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full bg-gray-800 hover:bg-gray-700 text-white rounded-xl py-3 text-sm font-medium transition-colors"
                >
                    {{ __('Connexion locale (développement)') }}
                </button>
            </form>
        </div>
        @endif

    </div>

    <p class="absolute bottom-4 text-white text-xs opacity-40">
        {{ __('© 2026 MESRS — Usage strictement institutionnel') }}
    </p>

</div>

</body>
</html>
