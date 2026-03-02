<div class="w-full rounded-lg shadow-sm p-4 md:p-6 {{ $bgColor ?? 'bg-white dark:bg-gray-800' }}">
    <div class="flex justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center me-3">
                {!! $icon !!}
            </div>
            <div>
                <h5 class="leading-none text-2xl font-bold text-gray-900 dark:text-white pb-1">{{ $stat }}</h5>
                <p class="font-normal text-gray-500 dark:text-gray-400">{{ $description }}</p>
            </div>
        </div>
        <div>
            <span class="{{ $class }}">
                {!! $badgeIcon !!}
                {{ $badgeText }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-2">
        {{ $slot }}
    </div>
</div>
