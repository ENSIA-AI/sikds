<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SIKDS — Accueil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }

        .hero {
            position: relative;
            height: 100vh;
            height: 100svh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            pointer-events: none;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(30, 62, 164, 0.15)  20%,
                rgba(30, 62, 164, 0.7)   60%,
                rgba(30, 62, 164, 0.96)  100%
            );
        }

        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 0 clamp(1.25rem, 5vw, 4rem);
            width: 100%;
            max-width: 1050px;
            transform: translateY(clamp(6.5rem, 24vh, 14rem));
        }

        .hero-headline {
            margin: 0;
            font-family: 'Instrument Sans', sans-serif;
            font-weight: 700;
            font-size: clamp(1.625rem, 3.5vw + 0.5rem, 3rem);
            color: #ffffff;
            line-height: 1.15;
        }

        .hero-sub {
            margin: clamp(1rem, 2vw, 1.75rem) 0 0;
            font-family: 'Instrument Sans', sans-serif;
            font-weight: 400;
            font-size: clamp(0.8125rem, 0.8vw + 0.5rem, 0.9375rem);
            color: #c0c0c0;
            line-height: 1.6;
            max-width: 620px;
        }

        .hero-cta {
            margin-top: clamp(1.75rem, 3vw, 3rem);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: clamp(0.75rem, 1.2vw, 1.25rem);
        }

        .btn-primary,
        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: clamp(2.75rem, 4.2vw, 3.8125rem);
            min-width: clamp(9rem, 12.7vw, 11.4375rem);
            padding: 0 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-family: 'Instrument Sans', sans-serif;
            font-size: clamp(0.9375rem, 1.3vw + 0.1rem, 1.25rem);
            white-space: nowrap;
            transition: opacity 0.15s ease;
        }

        .btn-primary:hover,
        .btn-outline:hover { opacity: 0.85; }

        .btn-primary {
            background: #ffffff;
            border: 1px solid #ffffff;
            font-weight: 700;
            color: #1c398e;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #ffffff;
            font-weight: 500;
            color: #ffffff;
        }

        .btn-primary > img,
        .btn-outline > img {
            width:  clamp(16px, 1.4vw, 22px);
            height: clamp(16px, 1.4vw, 22px);
            flex-shrink: 0;
        }

        @media (min-width: 640px) and (max-width: 1023px) {
            .hero-content {
                max-width: 820px;
            }
        }

        @media (max-width: 639px) {
            .hero-content {
                transform: translateY(clamp(5rem, 15vh, 7.5rem));
            }
            .hero-cta {
                flex-direction: column;
                align-items: center;
            }
            .btn-primary,
            .btn-outline {
                width: 100%;
                max-width: 17rem;
            }
            .hero-logo {
                display: none;
            }
        }
    </style>
</head>
<body>

<section class="hero">

    <img class="hero-bg" src="{{ asset('hero-bg.png') }}" alt="" aria-hidden="true" />

    <div class="hero-overlay" aria-hidden="true"></div>

    <div class="hero-content">

        <p class="hero-headline">
            Système d'Information et de Gestion de la Documentation Scientifique
        </p>

        <p class="hero-sub">
            Distribution de documents centralisée, contrôlée et traçable pour les ministères et les universités
        </p>

        <div class="hero-cta">
            <a href="{{ route('login') }}" class="btn-primary">
                <img alt="" src="{{ asset('lock-blue.svg') }}" />
                <span>Accéder</span>
            </a>

            <a href="#" class="btn-outline">
                <span>Savoir Plus</span>
                <img alt="" src="{{ asset('arrow-right-white.svg') }}" />
            </a>
        </div>

    </div>

</section>

</body>
</html>
