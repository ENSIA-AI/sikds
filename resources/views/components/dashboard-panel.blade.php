@props(['title', 'subtitle'])

<article class="sikds-panel flex flex-col">
    <header class="border-b border-black/10 px-4 py-4">
        <h2 class="sikds-panel-title">{{ $title }}</h2>
        <p class="sikds-panel-muted">{{ $subtitle }}</p>
    </header>

    {{ $slot }}

    @isset($footer)
    <footer class="sikds-link-footer mt-auto shrink-0">
        {{ $footer }}
    </footer>
    @endisset
</article>
