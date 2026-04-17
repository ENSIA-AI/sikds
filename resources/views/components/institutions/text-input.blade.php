@props([
    'label',
    'name',
    'type' => 'text',
    'value' => '',
    'autocomplete' => null,
    'inputmode' => null,
    'pattern' => null,
    'wrapperClass' => '',
    'required' => true,
])

<div class="flex w-full flex-col gap-2 md:max-w-[303.33px] {{ $wrapperClass }}">
    <label for="{{ $name }}" class="text-sm font-medium leading-5 tracking-normal text-[#0A0A0A]">
        {{ $label }}
    </label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        @if ($autocomplete !== null) autocomplete="{{ $autocomplete }}" @endif
        @if ($inputmode !== null) inputmode="{{ $inputmode }}" @endif
        @if ($pattern !== null) pattern="{{ $pattern }}" @endif
        @if ($required) required @endif
        class="h-11 w-full rounded-[10px] border border-black/10 bg-white px-3 text-sm text-[#0A0A0A] shadow-sm outline-none transition placeholder:text-[#717182]/70 focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A]/20"
    />
</div>
