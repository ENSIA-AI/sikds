@props(['icon', 'user', 'action', 'document', 'time'])

<div class="flex items-center gap-4 px-4 py-4">
    <div class="sikds-activity-icon-wrap">
        <img src="{{ $icon }}" alt="" class="h-4 w-4">
    </div>
    <div>
        <p class="text-sm sikds-ink">
            <span class="font-medium">{{ $user }}</span>
            <span class="sikds-muted-text"> {{ $action }} </span>
            <span class="font-medium">{{ $document }}</span>
        </p>
        <p class="text-xs sikds-muted-text">{{ $time }}</p>
    </div>
</div>
