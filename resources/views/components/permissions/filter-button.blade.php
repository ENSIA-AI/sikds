@props([
    'filterKey',
    'label',
    'count' => 0,
    'activeBg' => '#030213',
    'activeText' => '#FFFFFF',
    'inactiveBg' => '#FFFFFF',
    'inactiveText' => '#000000',
    'selected' => false,
])

@php
    $base = 'inline-flex h-[37.33px] items-center justify-center rounded-[10px] border border-black/10 px-3 text-sm font-medium leading-5 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black/20';
    // Keep Tailwind classes static so toggling works reliably at runtime.
    $active = 'bg-[#030213] text-white';
    $inactive = 'bg-white text-black hover:bg-black/[0.03]';
@endphp

<button
    type="button"
    data-permissions-filter-button
    data-filter-key="{{ $filterKey }}"
    class="{{ $base }} {{ $selected ? $active : $inactive }}"
>
    <span class="whitespace-nowrap">
        {{ $label }} ({{ $count }})
    </span>
</button>

