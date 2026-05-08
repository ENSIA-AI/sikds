@php
    $languages = config('languages.lang', []);
    $currentLocale = app()->getLocale();
@endphp

<details class="relative" data-language-switcher>
    <summary
        id="sikds-language-toggle"
        class="sikds-language-toggle inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/35"
        aria-controls="sikds-language-menu"
        aria-label="{{ __('Changer la langue') }}"
        style="list-style:none;cursor:pointer;"
    >
        <i class="fa-solid fa-globe text-[11px]" aria-hidden="true"></i>
        <span>{{ strtoupper($currentLocale) }}</span>
        <i class="fa-solid fa-chevron-down text-[9px]" aria-hidden="true"></i>
    </summary>

    <div
        id="sikds-language-menu"
        data-language-menu
        class="absolute end-0 mt-2 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl py-1 z-50"
        role="menu"
        aria-labelledby="sikds-language-toggle"
    >
        @foreach ($languages as $code => $label)
            <a
                href="{{ route('changeLanguage', ['lang' => $code]) }}"
                role="menuitem"
                class="flex items-center justify-between px-4 py-2 text-sm font-medium transition-colors hover:bg-slate-100 {{ $code === $currentLocale ? 'text-[#1E3A8A]' : 'text-slate-700' }}"
            >
                <span>{{ $label }}</span>
                @if ($code === $currentLocale)
                    <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
                @endif
            </a>
        @endforeach
    </div>
</details>
