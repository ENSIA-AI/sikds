@props(['title'])

<article class="sikds-bottom-card">
    <h3>{{ $title }}</h3>
    {{ $slot }}
</article>
