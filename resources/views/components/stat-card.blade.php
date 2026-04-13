@props(['icon', 'value', 'label', 'trend'])

<article class="sikds-stat-card">
    <div class="mb-4 flex items-start justify-between">
        <img src="{{ $icon }}" alt="" class="h-10 w-10">
        <span class="sikds-trend">
            <img src="/up.svg" alt="" class="h-4 w-4">{{ $trend }}
        </span>
    </div>
    <p class="sikds-stat-value">{{ $value }}</p>
    <p class="sikds-stat-label">{{ $label }}</p>
</article>
