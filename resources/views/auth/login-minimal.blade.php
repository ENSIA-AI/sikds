@php
    $appLocale = app()->getLocale();
    $isRtl = in_array($appLocale, (array) config('languages.rtl', ['ar']), true);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $appLocale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('SIKDS — Connexion') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@600;700&family=Inter:wght@400;500;600;700&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased sikds-login-body">

<div class="sikds-login-shell">

    {{-- Decorative aurora blobs (purely decorative) --}}
    <div class="sikds-login-decor" aria-hidden="true">
        <span class="sikds-login-blob sikds-login-blob--1"></span>
        <span class="sikds-login-blob sikds-login-blob--2"></span>
        <span class="sikds-login-blob sikds-login-blob--3"></span>
    </div>

    {{-- Language switcher --}}
    <nav class="sikds-login-lang" aria-label="{{ __('Choix de la langue') }}">
        @foreach (['fr' => 'FR', 'ar' => 'AR', 'en' => 'EN'] as $lang => $label)
            <a href="{{ route('changeLanguage', ['lang' => $lang]) }}"
               class="sikds-login-lang-link {{ $appLocale === $lang ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <main class="sikds-login-card" role="main">

        {{-- Brand --}}
        <div class="sikds-login-brand">
            <div class="sikds-login-badge">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            </div>
            <h1 class="sikds-login-title">SIKDS</h1>
            <p class="sikds-login-subtitle">
                {{ __('Système Institutionnel de Gestion Documentaire') }}
            </p>
        </div>

        @if(session('success'))
            <div class="mb-4">
                <x-alert-item type="success" :message="session('success')" :timestamp="now()->format('H:i')" />
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4">
                <x-alert-item type="danger" :message="session('error')" :timestamp="now()->format('H:i')" />
            </div>
        @endif

        {{-- SSO — primary CTA --}}
        <p class="sikds-login-eyebrow">{{ __('Authentification institutionnelle') }}</p>
        <a href="{{ route('sso.redirect') }}" class="sikds-login-sso">
            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
            {{ __('Continuer avec le SSO MESRS') }}
        </a>

        @if(app()->environment('local'))
            <div class="sikds-login-divider">
                <span>{{ __('Développement local uniquement') }}</span>
            </div>

            <form class="sikds-login-form" method="POST" action="{{ route('login.local.post') }}">
                @csrf

                <div class="sikds-login-field">
                    <label for="email">{{ __('Email') }}</label>
                    <div class="sikds-login-input-wrap">
                        <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                        <input id="email" name="email" type="email" autocomplete="username"
                               value="{{ old('email', 'admin@mesrs.dz') }}" required
                               placeholder="nom@mesrs.dz" />
                    </div>
                    @error('email')
                        <p class="sikds-login-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sikds-login-field">
                    <label for="password">{{ __('Mot de passe') }}</label>
                    <div class="sikds-login-input-wrap">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input id="password" name="password" type="password"
                               autocomplete="current-password" required placeholder="••••••••" />
                    </div>
                </div>

                <button type="submit" class="sikds-login-local-btn">
                    {{ __('Connexion locale (développement)') }}
                </button>
            </form>
        @endif

    </main>

    <p class="sikds-login-footer">
        {{ __('© 2026 MESRS — Usage strictement institutionnel') }}
    </p>

</div>

</body>
</html>
