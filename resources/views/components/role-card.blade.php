@props([
    'roleName',
    'permissionCount' => 0,
    'description' => '',
    'icon' => 'shield',
    'href' => '#',
])

<article
    {{ $attributes->class([
        'rounded-[14px] border border-black/10 bg-white shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]',
        'p-6',
        'flex h-full flex-col',
    ]) }}
>
    <header class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-[#ECECF04D] text-[#1C398E]">
                @if ($icon === 'shield')
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                        <path
                            d="M12 2.5l7 3.2v6.4c0 5-3 9.2-7 10.4C8 21.3 5 17.1 5 12.1V5.7l7-3.2Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M9.3 12.2l1.9 1.9 3.8-4.1"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                        <path
                            d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <path
                            d="M4 21a8 8 0 0 1 16 0"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                @endif
            </div>

            <div class="min-w-0">
                <h3 class="truncate text-base font-bold leading-5 text-black" style="font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;">
                    {{ $roleName }}
                </h3>
                <p class="text-sm leading-5 text-[#717182]" style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;">
                    {{ $permissionCount }} permissions
                </p>
            </div>
        </div>
    </header>

    <div class="mt-4 flex-1">
        <p
            class="text-sm leading-5 text-[#717182] line-clamp-3"
            style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;"
        >
            {{ $description }}
        </p>
    </div>

    <div class="mt-5">
        <a
            href="{{ $href }}"
            class="inline-flex h-10 w-full items-center justify-center rounded-[10px] border border-black/10 bg-[#ECECF04D] px-4 text-sm font-medium text-black transition hover:bg-[#ECECF04D]/70 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1C398E]"
            style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;"
        >
            Voir Les Permissions
        </a>
    </div>
</article>

