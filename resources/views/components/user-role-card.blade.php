@props([
    'roleName',
    'inactifusers' => '',
    'description' => '',
    'icon' => 'shield',
    'roleId' => null,
])

@php
    $iconSrc = $icon;
    $isAssetIcon = is_string($icon) && (str_starts_with($icon, 'http') || str_starts_with($icon, '/') || str_contains($icon, 'images/'));
@endphp

<button
    type="button"
    data-role-card
    @if ($roleId !== null) data-role-id="{{ $roleId }}" @endif
    {{ $attributes->class([
        'group flex w-full flex-col rounded-[10px] border border-transparent bg-[#ECECF04D] p-3 text-start transition',
        'min-h-[101px] max-w-full',
        'hover:bg-[#E8EEF9]/80',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2B7FFF]',
        'data-[selected=true]:border-t-[0.67px] data-[selected=true]:border-[#2B7FFF] data-[selected=true]:bg-[#EFF6FF]',
    ]) }}
>
    <div class="flex flex-1 items-start gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-white/80 text-[#1C398E] shadow-sm">
            @if ($isAssetIcon)
                <img src="{{ $iconSrc }}" alt="" class="size-6 object-contain" loading="lazy" />
            @elseif ($icon === 'user')
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="currentColor" stroke-width="1.6" />
                    <path d="M5 20a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
            @elseif ($icon === 'key')
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                    <circle cx="8" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6" />
                    <path d="M10.5 10.5 20 20M17 14l2 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
            @else
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                    <path
                        d="M12 2.5 19 5.7v6.4c0 5-3 9.2-7 10.4C8 21.3 5 17.1 5 12.1V5.7L12 2.5Z"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linejoin="round"
                    />
                </svg>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate font-inter text-sm font-semibold leading-5 text-[#0A0A0A]">{{ $roleName }}</p>
            @if ($inactifusers !== '' && $inactifusers !== null)
                <p class="mt-0.5 font-inter text-xs font-normal leading-4 text-[#717182]">{{ $inactifusers }}</p>
            @endif
        </div>
    </div>
    @if ($description)
        <p class="mt-2 line-clamp-2 font-inter text-xs leading-4 text-[#717182]">{{ $description }}</p>
    @endif
</button>
