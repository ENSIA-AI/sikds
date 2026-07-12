@props([
    'icon' => 'fa-regular fa-folder-open',
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-[14px] border border-black/10 bg-white px-6 py-8 text-center shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#EEF2FF] text-[#1c398e]">
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </div>
    <h3 class="mt-4 text-base font-semibold text-[#0A0A0A]">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[#717182]">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
            {{ $action }}
        </div>
    @endisset
</div>
