{{-- resources/views/components/alert.blade.php --}}

@props(['type' => 'info', 'title' => 'Alert!', 'message' => 'This is an alert message.'])

@php
    $class = '';
    $svgPath = '';
    $srOnlyText = '';

    switch ($type) {
        case 'danger':
            $class = 'text-red-800 border-red-300 bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800';
            $svgPath = 'M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z';
            $srOnlyText = 'Danger';
            break;
        case 'success':
            $class = 'text-green-800 border-green-300 bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800';
            $svgPath = 'M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm7 3.5H3a.5.5 0 0 0 0 1h14a.5.5 0 0 0 0-1ZM9 12h2a1 1 0 1 0 0-2H9a1 1 0 0 0 0 2ZM10 7a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z'; // Example success icon
            $srOnlyText = 'Success';
            break;
        case 'warning':
            $class = 'text-yellow-800 border-yellow-300 bg-yellow-50 dark:bg-gray-800 dark:text-yellow-400 dark:border-yellow-800';
            $svgPath = 'M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4.5H9a1 1 0 0 1 0-2h2a1 1 0 0 1 0 2Z'; // Example warning icon
            $srOnlyText = 'Warning';
            break;
        case 'dark':
            $class = 'text-gray-800 border-gray-300 bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-800';
            $svgPath = 'M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z'; // Reusing danger icon for dark as example
            $srOnlyText = 'Dark';
            break;
        default: // info
            $class = 'text-blue-800 border-blue-300 bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:border-blue-800';
            $svgPath = 'M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z'; // Reusing danger icon for info as example (can be changed)
            $srOnlyText = 'Info';
            break;
    }
@endphp

{{-- <div {{ $attributes->merge(['class' => 'flex items-center p-4 mb-4 text-sm rounded-lg ' . $class]) }} role="alert">
    <svg class="shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="{{ $svgPath }}"/>
    </svg>
    <span class="sr-only">{{ $srOnlyText }}</span>
    <div>
        <span class="font-medium">{{ $title }}</span> {{ $message }}
    </div>
</div> --}}

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition
    {{ $attributes->merge(['class' => 'flex items-start justify-between p-4 mb-4 text-sm rounded-lg ' . $class]) }}
    role="alert"
>
    <div class="flex items-center">
        <svg class="shrink-0 w-4 h-4 me-3 mt-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="{{ $svgPath }}" />
        </svg>
        <span class="sr-only">{{ $srOnlyText }}</span>
        <div>
            <span class="font-medium">{{ $title }}</span> {{ $message }}
        </div>
    </div>

    <button @click="show = false" type="button" class="ms-4 text-xl text-gray-500 hover:text-gray-800 dark:hover:text-white focus:outline-none">
        &times;
    </button>
</div>
