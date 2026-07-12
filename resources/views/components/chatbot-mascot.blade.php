@props([
    'variant' => 'idle',   // idle | thinking | success | glyph (face-only icon)
    'uid' => 'm',          // unique suffix so gradient ids never collide across instances
    'width' => 120,
    'height' => 120,
    'color' => '#ffffff',  // kept for backwards-compat (glyph is self-coloured)
])

@php
    $h = 'rh-'.$uid;   // head gradient
    $b = 'rb-'.$uid;   // body gradient
    $g = 'rg-'.$uid;   // eye glow
    $o = 'ro-'.$uid;   // antenna orb glow
@endphp

@if ($variant === 'glyph')
    {{-- Round-face avatar (no antenna/pods/body) — a full circle that fills the
         floating button; screen face with glowing eyes + smile. --}}
    <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }} aria-hidden="true">
        <defs>
            <linearGradient id="{{ $h }}" x1="12" y1="12" x2="140" y2="150" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#3865FF"></stop>
                <stop offset="1" stop-color="#1c398e"></stop>
            </linearGradient>
            <radialGradient id="{{ $g }}" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#eaf0ff" stop-opacity="0.55"></stop>
                <stop offset="1" stop-color="#eaf0ff" stop-opacity="0"></stop>
            </radialGradient>
        </defs>
        <circle cx="80" cy="80" r="80" fill="url(#{{ $h }})"></circle>
        <ellipse cx="52" cy="34" rx="38" ry="22" fill="#e9edfb" opacity="0.16"></ellipse>
        <rect x="21.5" y="37" width="117" height="90" rx="38" fill="#0e1c50"></rect>
        <rect x="24" y="39.5" width="112" height="85" rx="35.5" fill="none" stroke="#4a6ce8" stroke-width="2.5" opacity="0.35"></rect>
        <ellipse cx="52.4" cy="80" rx="19" ry="17" fill="url(#{{ $g }})"></ellipse>
        <ellipse cx="107.6" cy="80" rx="19" ry="17" fill="url(#{{ $g }})"></ellipse>
        <ellipse cx="52.4" cy="80" rx="8.5" ry="16.5" fill="#eaf0ff"></ellipse>
        <ellipse cx="107.6" cy="80" rx="8.5" ry="16.5" fill="#eaf0ff"></ellipse>
        <path d="M65.8 103.5 Q80 118 94.2 103.5" stroke="#eaf0ff" stroke-width="9" stroke-linecap="round" fill="none"></path>
    </svg>
@elseif ($variant === 'thinking')
    <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }} aria-hidden="true">
        <defs>
            <linearGradient id="{{ $h }}" x1="82" y1="56" x2="158" y2="134" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#3865FF"></stop>
                <stop offset="1" stop-color="#1c398e"></stop>
            </linearGradient>
            <linearGradient id="{{ $b }}" x1="98" y1="142" x2="142" y2="186" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#3865FF"></stop>
                <stop offset="1" stop-color="#1c398e"></stop>
            </linearGradient>
            <radialGradient id="{{ $g }}" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#eaf0ff" stop-opacity="0.55"></stop>
                <stop offset="1" stop-color="#eaf0ff" stop-opacity="0"></stop>
            </radialGradient>
            <radialGradient id="{{ $o }}" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#3865FF" stop-opacity="0.45"></stop>
                <stop offset="1" stop-color="#3865FF" stop-opacity="0"></stop>
            </radialGradient>
        </defs>
        <ellipse cx="120" cy="206" rx="46" ry="7" fill="#16296b" opacity="0.12"></ellipse>
        <rect x="74.5" y="144" width="15" height="32" rx="7.5" fill="#2f52c9" transform="rotate(14 82 160)"></rect>
        <rect x="150.5" y="144" width="15" height="32" rx="7.5" fill="#2f52c9" transform="rotate(-14 158 160)"></rect>
        <rect x="96" y="142" width="48" height="44" rx="21" fill="url(#{{ $b }})"></rect>
        <ellipse cx="112" cy="150" rx="16" ry="7" fill="#e9edfb" opacity="0.14"></ellipse>
        <g transform="rotate(-4 120 110)">
            <path d="M120 50 V38" stroke="#16296b" stroke-width="3.5" stroke-linecap="round"></path>
            <ellipse cx="120" cy="31" rx="13" ry="12" fill="url(#{{ $o }})"></ellipse>
            <circle cx="120" cy="31" r="6.5" fill="#3865FF"></circle>
            <circle cx="120" cy="31" r="11" fill="none" stroke="#3865FF" stroke-width="2" opacity="0.35"></circle>
            <circle cx="117.8" cy="28.8" r="1.8" fill="#ffffff" opacity="0.8"></circle>
            <circle cx="73" cy="94" r="8" fill="#16296b"></circle>
            <circle cx="167" cy="94" r="8" fill="#16296b"></circle>
            <ellipse cx="120" cy="94" rx="45" ry="41" fill="url(#{{ $h }})"></ellipse>
            <ellipse cx="98" cy="66" rx="26" ry="14" fill="#e9edfb" opacity="0.16"></ellipse>
            <rect x="87" y="72" width="66" height="46" rx="20" fill="#0e1c50"></rect>
            <rect x="88" y="73" width="64" height="44" rx="19" fill="none" stroke="#4a6ce8" stroke-width="1.5" opacity="0.35"></rect>
            <ellipse cx="100" cy="89" rx="10" ry="9" fill="url(#{{ $g }})"></ellipse>
            <ellipse cx="131" cy="87.5" rx="10" ry="9" fill="url(#{{ $g }})"></ellipse>
            <circle cx="100" cy="89" r="5.5" fill="#eaf0ff"></circle>
            <circle cx="131" cy="87.5" r="5.5" fill="#eaf0ff"></circle>
            <path d="M111 107 Q117.5 109.5 124 107" stroke="#eaf0ff" stroke-width="4.5" stroke-linecap="round" fill="none"></path>
        </g>
        <circle cx="152" cy="48" r="3" fill="#c7d3f6"></circle>
        <circle cx="161" cy="38" r="4.5" fill="#6f8ceb"></circle>
        <rect x="166" y="10" width="56" height="24" rx="12" fill="#ffffff" stroke="#dbe3f8" stroke-width="1.5"></rect>
        <circle cx="180" cy="22" r="3.6" fill="#c7d3f6"></circle>
        <circle cx="193" cy="22" r="3.6" fill="#6f8ceb"></circle>
        <circle cx="206" cy="22" r="3.6" fill="#3865FF"></circle>
    </svg>
@elseif ($variant === 'success')
    <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }} aria-hidden="true">
        <defs>
            <linearGradient id="{{ $h }}" x1="82" y1="56" x2="158" y2="134" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#3865FF"></stop>
                <stop offset="1" stop-color="#1c398e"></stop>
            </linearGradient>
            <linearGradient id="{{ $b }}" x1="98" y1="142" x2="142" y2="186" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#3865FF"></stop>
                <stop offset="1" stop-color="#1c398e"></stop>
            </linearGradient>
            <radialGradient id="{{ $o }}" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#3865FF" stop-opacity="0.45"></stop>
                <stop offset="1" stop-color="#3865FF" stop-opacity="0"></stop>
            </radialGradient>
        </defs>
        <ellipse cx="120" cy="206" rx="46" ry="7" fill="#16296b" opacity="0.12"></ellipse>
        <rect x="64" y="126" width="15" height="30" rx="7.5" fill="#2f52c9" transform="rotate(-48 71.5 141)"></rect>
        <rect x="161" y="126" width="15" height="30" rx="7.5" fill="#2f52c9" transform="rotate(48 168.5 141)"></rect>
        <rect x="96" y="142" width="48" height="44" rx="21" fill="url(#{{ $b }})"></rect>
        <ellipse cx="112" cy="150" rx="16" ry="7" fill="#e9edfb" opacity="0.14"></ellipse>
        <path d="M120 50 V38" stroke="#16296b" stroke-width="3.5" stroke-linecap="round"></path>
        <ellipse cx="120" cy="31" rx="13" ry="12" fill="url(#{{ $o }})"></ellipse>
        <circle cx="120" cy="31" r="6.5" fill="#3865FF"></circle>
        <circle cx="117.8" cy="28.8" r="1.8" fill="#ffffff" opacity="0.8"></circle>
        <circle cx="73" cy="94" r="8" fill="#16296b"></circle>
        <circle cx="167" cy="94" r="8" fill="#16296b"></circle>
        <ellipse cx="120" cy="94" rx="45" ry="41" fill="url(#{{ $h }})"></ellipse>
        <ellipse cx="98" cy="66" rx="26" ry="14" fill="#e9edfb" opacity="0.16"></ellipse>
        <rect x="87" y="72" width="66" height="46" rx="20" fill="#0e1c50"></rect>
        <rect x="88" y="73" width="64" height="44" rx="19" fill="none" stroke="#4a6ce8" stroke-width="1.5" opacity="0.35"></rect>
        <path d="M95.5 96 Q104 87 112.5 96" stroke="#eaf0ff" stroke-width="5.5" stroke-linecap="round" fill="none"></path>
        <path d="M127.5 96 Q136 87 144.5 96" stroke="#eaf0ff" stroke-width="5.5" stroke-linecap="round" fill="none"></path>
        <path d="M109.5 104 Q120 114.5 130.5 104" stroke="#eaf0ff" stroke-width="5.5" stroke-linecap="round" fill="none"></path>
        <path d="M198 31 Q200.2 39.8 209 42 Q200.2 44.2 198 53 Q195.8 44.2 187 42 Q195.8 39.8 198 31 Z" fill="#6f8ceb"></path>
        <path d="M40 47 Q41.5 52.5 47 54 Q41.5 55.5 40 61 Q38.5 55.5 33 54 Q38.5 52.5 40 47 Z" fill="#9db4f0"></path>
        <circle cx="48" cy="80" r="2.5" fill="#c7d3f6"></circle>
    </svg>
@else
    {{-- idle / friendly (default) --}}
    <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }} aria-hidden="true">
        <defs>
            <linearGradient id="{{ $h }}" x1="82" y1="56" x2="158" y2="134" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#3865FF"></stop>
                <stop offset="1" stop-color="#1c398e"></stop>
            </linearGradient>
            <linearGradient id="{{ $b }}" x1="98" y1="142" x2="142" y2="186" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#3865FF"></stop>
                <stop offset="1" stop-color="#1c398e"></stop>
            </linearGradient>
            <radialGradient id="{{ $g }}" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#eaf0ff" stop-opacity="0.55"></stop>
                <stop offset="1" stop-color="#eaf0ff" stop-opacity="0"></stop>
            </radialGradient>
            <radialGradient id="{{ $o }}" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#3865FF" stop-opacity="0.45"></stop>
                <stop offset="1" stop-color="#3865FF" stop-opacity="0"></stop>
            </radialGradient>
        </defs>
        <ellipse cx="120" cy="206" rx="46" ry="7" fill="#16296b" opacity="0.12"></ellipse>
        <rect x="74.5" y="144" width="15" height="32" rx="7.5" fill="#2f52c9" transform="rotate(14 82 160)"></rect>
        <rect x="150.5" y="144" width="15" height="32" rx="7.5" fill="#2f52c9" transform="rotate(-14 158 160)"></rect>
        <rect x="96" y="142" width="48" height="44" rx="21" fill="url(#{{ $b }})"></rect>
        <ellipse cx="112" cy="150" rx="16" ry="7" fill="#e9edfb" opacity="0.14"></ellipse>
        <path d="M120 50 V38" stroke="#16296b" stroke-width="3.5" stroke-linecap="round"></path>
        <ellipse cx="120" cy="31" rx="13" ry="12" fill="url(#{{ $o }})"></ellipse>
        <circle cx="120" cy="31" r="6.5" fill="#3865FF"></circle>
        <circle cx="117.8" cy="28.8" r="1.8" fill="#ffffff" opacity="0.8"></circle>
        <circle cx="73" cy="94" r="8" fill="#16296b"></circle>
        <circle cx="167" cy="94" r="8" fill="#16296b"></circle>
        <ellipse cx="120" cy="94" rx="45" ry="41" fill="url(#{{ $h }})"></ellipse>
        <ellipse cx="98" cy="66" rx="26" ry="14" fill="#e9edfb" opacity="0.16"></ellipse>
        <rect x="87" y="72" width="66" height="46" rx="20" fill="#0e1c50"></rect>
        <rect x="88" y="73" width="64" height="44" rx="19" fill="none" stroke="#4a6ce8" stroke-width="1.5" opacity="0.35"></rect>
        <ellipse cx="104.5" cy="94" rx="11" ry="10" fill="url(#{{ $g }})"></ellipse>
        <ellipse cx="135.5" cy="94" rx="11" ry="10" fill="url(#{{ $g }})"></ellipse>
        <ellipse cx="104.5" cy="94" rx="4.8" ry="8.5" fill="#eaf0ff"></ellipse>
        <ellipse cx="135.5" cy="94" rx="4.8" ry="8.5" fill="#eaf0ff"></ellipse>
        <circle cx="95" cy="105" r="4" fill="#3865FF" opacity="0.45"></circle>
        <circle cx="145" cy="105" r="4" fill="#3865FF" opacity="0.45"></circle>
        <path d="M112 106 Q120 113.5 128 106" stroke="#eaf0ff" stroke-width="5" stroke-linecap="round" fill="none"></path>
        <path d="M198 33 Q200.2 41.8 209 44 Q200.2 46.2 198 55 Q195.8 46.2 187 44 Q195.8 41.8 198 33 Z" fill="#6f8ceb"></path>
        <circle cx="206" cy="70" r="2.5" fill="#9db4f0" opacity="0.9"></circle>
    </svg>
@endif
