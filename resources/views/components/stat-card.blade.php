@props(['icon', 'value', 'label', 'trend' => null, 'iconBg' => null, 'iconStyle' => null])

{{-- Icon sits in a brand-100 chip by default; callers may still tint via iconBg. --}}
<article class="sikds-stat-card">
    <div class="mb-4 flex items-start justify-between">
        <div class="sikds-stat-icon-chip" @if($iconBg) style="background:{{ $iconBg }}" @endif>
            <img src="{{ $icon }}" alt="" class="h-5 w-5" @if($iconStyle) style="{{ $iconStyle }}" @endif>
        </div>
    </div>
    <p class="sikds-stat-value">{{ $value }}</p>
    <p class="sikds-stat-label">{{ $label }}</p>
</article>
