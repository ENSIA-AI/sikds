@props(['icon', 'value', 'label', 'trend' => null, 'iconBg' => null])

<article class="sikds-stat-card">
    <div class="mb-4 flex items-start justify-between">
        @if($iconBg)
            <div class="h-10 w-10 rounded-[10px] grid place-items-center flex-shrink-0" style="background:{{ $iconBg }}">
                <img src="{{ $icon }}" alt="" class="h-5 w-5">
            </div>
        @else
            <img src="{{ $icon }}" alt="" class="h-10 w-10">
        @endif
    </div>
    <p class="sikds-stat-value">{{ $value }}</p>
    <p class="sikds-stat-label">{{ $label }}</p>
</article>
