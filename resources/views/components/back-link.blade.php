@props(['href' => '#', 'label' => 'Retour'])

{{--
    Shared back-navigation link.
    Used at the top of detail/edit/upload pages to return to the parent listing.
    Renders as a small muted-gray inline link with a FontAwesome arrow-left icon
    (no text-arrow characters), darkening on hover.
--}}
<a
    href="{{ $href }}"
    {{ $attributes->class('sikds-back-link inline-flex items-center gap-2 text-sm font-medium text-[#6b7280] transition-colors hover:text-[#1f2937]') }}
>
    <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</a>
