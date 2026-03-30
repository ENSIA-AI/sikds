@props(['title' => null])

<x-layouts.auth.simple :title="$title">
    {{ $slot }}
</x-layouts.auth.simple>
