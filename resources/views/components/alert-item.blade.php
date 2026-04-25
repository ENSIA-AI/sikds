@props(['type', 'message', 'timestamp'])

@php
    $map = [
        'danger'  => ['class' => 'sikds-alert--danger',  'icon' => '/danger.svg'],
        'warning' => ['class' => 'sikds-alert--warn',    'icon' => '/danger-orange.svg'],
        'info'    => ['class' => 'sikds-alert--info',    'icon' => '/time-blue.svg'],
        'success' => ['class' => 'sikds-alert--success', 'icon' => '/up.svg'],
    ];
    $style = $map[$type] ?? $map['info'];
@endphp

<div class="sikds-alert {{ $style['class'] }}">
    <p class="sikds-alert-message">
        <img src="{{ $style['icon'] }}" alt="" class="mt-0.5 h-4 w-4 shrink-0">
        {{ $message }}
    </p>
    <p class="sikds-alert-time">{{ $timestamp }}</p>
</div>
