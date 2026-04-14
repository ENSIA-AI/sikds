<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SIKDS' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sikds-app-body">
    @php
        $pageTitle = trim($__env->yieldContent('page_title')) ?: 'Tableau de Bord';
        $pageSubtitle = trim($__env->yieldContent('page_subtitle')) ?: "Aperçu de l'activité du système SIKDS";
    @endphp

    <div class="sikds-decor-wrap" aria-hidden="true">
        <img src="/dashboard-decor.svg" alt="" width="812" height="1401" class="sikds-decor-img">
    </div>

    @include('layouts.partials.sidebar', ['activeNav' => $activeNav ?? 'dashboard'])

    <div class="sikds-main-wrap">
        <header class="sikds-top-header">
            <div class="sikds-header-inner">

                <div class="sikds-header-title">
                    <h1 class="sikds-page-title">{{ $pageTitle }}</h1>
                    <p class="sikds-page-subtitle">{!! $pageSubtitle !!}</p>
                </div>

                <button
                    type="button"
                    class="sikds-menu-button"
                    id="sikds-sidebar-toggle"
                    aria-controls="sikds-sidebar"
                    aria-expanded="false"
                    aria-label="Ouvrir le menu latéral"
                >
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </button>

                <div class="sikds-profile" aria-label="Informations du profil">
                    <div class="sikds-user-meta">
                        <p class="sikds-user-role">Super Administrateur</p>
                        <p class="sikds-user-name">Nadia Benyahia</p>
                    </div>
                    <a href="#" class="sikds-header-notif" aria-label="Notifications">
                        <span class="relative inline-flex">
                            <img src="/bell.svg" alt="" class="h-5 w-5">
                            <span class="sikds-header-notif-dot" aria-hidden="true"></span>
                        </span>
                    </a>
                    <div class="sikds-avatar">
                        <img src="/person.svg" alt="Profil" class="h-5 w-5">
                    </div>
                </div>

            </div>
        </header>

        <main>
            <div class="sikds-main-inner">
                @yield('content')
            </div>
        </main>
    </div>

    <button
        type="button"
        class="sikds-sidebar-overlay"
        id="sikds-sidebar-overlay"
        aria-label="Fermer le menu latéral"
    ></button>
</body>
</html>
