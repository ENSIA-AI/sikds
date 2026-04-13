<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title . ' - SIKDS' : 'SIKDS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sikds-app-body">

{{-- Decorative background element --}}
<div class="sikds-decor-wrap" aria-hidden="true">
    <img src="/dashboard-decor.svg" alt="" class="sikds-decor-img">
</div>

{{-- Mobile sidebar overlay --}}
<button type="button" class="sikds-sidebar-overlay" id="sikds-sidebar-overlay" aria-hidden="true" tabindex="-1"></button>

{{-- ===== Sidebar ===== --}}
@include('layouts.partials.sidebar', ['activeNav' => $activeNav ?? 'dashboard'])

{{-- ===== Main content area ===== --}}
<div class="sikds-main-wrap">

    {{-- Top header --}}
    <header class="sikds-top-header">
        <div class="sikds-header-inner">

            {{-- Mobile hamburger --}}
            <button type="button" class="sikds-menu-button" id="sikds-sidebar-toggle" aria-expanded="false" aria-controls="sikds-sidebar" aria-label="Ouvrir le menu">
                <span></span><span></span><span></span>
            </button>

            {{-- Page title --}}
            <div class="sikds-header-title">
                {{ $header ?? '' }}
            </div>

            {{-- User profile --}}
            @auth
            <div class="sikds-profile">
                <div class="sikds-user-meta">
                    <p class="sikds-user-role">{{ auth()->user()->getRoleNames()->first() ?? 'Utilisateur' }}</p>
                    <p class="sikds-user-name">{{ auth()->user()->full_name ?? auth()->user()->username }}</p>
                </div>
                <div class="sikds-avatar text-white text-sm font-bold">
                    {{ auth()->user()->initials() ?? '?' }}
                </div>
            </div>
            @endauth

        </div>
    </header>

    {{-- Page content --}}
    <main class="sikds-main-inner">
        {{ $slot }}
    </main>

</div>
</body>
</html>
