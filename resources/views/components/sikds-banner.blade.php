@props([
    'type'        => 'success',
    'title'       => null,
    'message'     => null,
    'dismissible' => true,
    'autoHide'    => null,
])

@php
    $type = in_array($type, ['success', 'danger', 'error', 'warn', 'warning', 'info'], true) ? $type : 'info';
    $alias = [
        'error'   => 'danger',
        'warning' => 'warn',
    ];
    $type = $alias[$type] ?? $type;

    $class = 'sikds-toast--' . $type;

    $icon = [
        'success' => 'fa-circle-check',
        'danger'  => 'fa-circle-exclamation',
        'warn'    => 'fa-triangle-exclamation',
        'info'    => 'fa-circle-info',
    ][$type];

    $defaultTitle = [
        'success' => 'Opération réussie',
        'danger'  => 'Action impossible',
        'warn'    => 'Attention',
        'info'    => 'Information',
    ][$type];

    if ($autoHide === null) {
        $autoHide = $type === 'success' ? 4500 : 0;
    }

    $role = $type === 'success' ? 'status' : 'alert';
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-cloak
     @if($autoHide > 0) x-init="setTimeout(() => show = false, {{ (int) $autoHide }})" @endif
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-1"
     class="sikds-toast {{ $class }}"
     role="{{ $role }}">
    <span class="sikds-toast-icon">
        <i class="fa-solid {{ $icon }}"></i>
    </span>
    <div class="sikds-toast-body">
        <p class="sikds-toast-title">{{ $title ?? $defaultTitle }}</p>
        <p class="sikds-toast-message">{{ $slot->isEmpty() ? $message : $slot }}</p>
    </div>
    @if ($dismissible)
        <button type="button" @click="show = false" class="sikds-toast-dismiss" aria-label="Fermer">
            <i class="fa-solid fa-xmark"></i>
        </button>
    @endif
</div>
