<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('Erreur')).' - SIKDS'</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sikds-app-body">
    <div class="sikds-decor-wrap" aria-hidden="true">
        <img src="/dashboard-decor.svg" alt="" class="sikds-decor-img">
    </div>

    <main class="relative z-10 flex min-h-screen items-center px-4 py-10 md:px-10">
        <div class="mx-auto w-full max-w-4xl">
            <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                <section class="rounded-[14px] border bg-white p-8 shadow-[var(--sikds-shadow-panel)]" style="border-color: var(--sikds-border-solid);">
                    <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.08em] text-[color:var(--sikds-primary)]" style="border-color: color-mix(in srgb, var(--sikds-primary) 35%, white);">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>SIKDS</span>
                    </div>

                    <div class="mt-5 flex items-start gap-4">
                        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-[14px] bg-[color:var(--sikds-primary)] text-white shadow-[0_8px_20px_rgba(28,57,142,0.25)]">
                            <i class="@yield('icon', 'fa-solid fa-circle-exclamation') text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-wide text-[color:var(--sikds-primary)]">{{ __('Erreur') }} @yield('code')</p>
                            <h1 class="mt-1 text-3xl font-bold leading-tight text-[color:var(--sikds-ink)]">@yield('title')</h1>
                            <p class="mt-3 text-sm leading-6 text-[color:var(--sikds-muted)]">@yield('message')</p>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-[10px] border px-4 py-2 text-sm font-medium text-[color:var(--sikds-ink)] hover:bg-gray-50" style="border-color: var(--sikds-border-solid);">
                            <i class="fa-solid fa-house"></i>
                            {{ __('Retour a l\'accueil') }}
                        </a>
                        <button type="button" onclick="window.history.back()" class="inline-flex items-center gap-2 rounded-[10px] border px-4 py-2 text-sm font-medium text-[color:var(--sikds-ink)] hover:bg-gray-50" style="border-color: var(--sikds-border-solid);">
                            <i class="fa-solid fa-arrow-left"></i>
                            {{ __('Page precedente') }}
                        </button>
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-[10px] bg-[color:var(--sikds-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-95">
                                <i class="fa-solid fa-gauge-high"></i>
                                {{ __('Aller au tableau de bord') }}
                            </a>
                        @endauth
                    </div>
                </section>

                <aside class="rounded-[14px] border bg-white p-6 shadow-[var(--sikds-shadow-panel)]" style="border-color: var(--sikds-border-solid);">
                    <h2 class="text-base font-semibold text-[color:var(--sikds-ink)]">{{ __('Que faire maintenant ?') }}</h2>
                    <ul class="mt-4 space-y-3 text-sm text-[color:var(--sikds-muted)]">
                        <li class="flex gap-2">
                            <i class="fa-regular fa-circle-check mt-0.5 text-[color:var(--sikds-primary)]"></i>
                            <span>{{ __('Verifiez l\'URL et les permissions de votre compte.') }}</span>
                        </li>
                        <li class="flex gap-2">
                            <i class="fa-regular fa-circle-check mt-0.5 text-[color:var(--sikds-primary)]"></i>
                            <span>{{ __('Si le probleme persiste, reconnectez-vous puis reessayez.') }}</span>
                        </li>
                        <li class="flex gap-2">
                            <i class="fa-regular fa-circle-check mt-0.5 text-[color:var(--sikds-primary)]"></i>
                            <span>{{ __('Contactez l\'administrateur si l\'erreur bloque votre travail.') }}</span>
                        </li>
                    </ul>

                    <div class="mt-6 rounded-[10px] border px-4 py-3 text-xs text-[color:var(--sikds-muted)]" style="border-color: var(--sikds-border-solid);">
                        <p class="font-semibold text-[color:var(--sikds-ink)]">{{ __('Reference') }}</p>
                        <p class="mt-1">{{ __('Code') }}: @yield('code') · {{ __('Systeme') }}: SIKDS</p>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</body>
</html>

