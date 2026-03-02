@props(
[
    'id', 'size', 'title', 'view'=>null, 'slot'=>null, 'withFooter' => null, 'padding' => '36' ,
    'isOpen' => false
]
)
@php
    $isArabic = app()->getLocale() === 'ar';
@endphp
<div>
    <div id="{{ $id }}" tabindex="-1"
         aria-hidden="true"
         class="modal fixed top-0 left-0 right-0 z-50
         @if(!$isOpen) hidden @endif
    p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] ">
        <!-- Grey background overlay -->
        <div class="fixed inset-0 bg-gray-400 opacity-70"></div>
        <div class="relative p-{{ $padding }} w-{{ $size }} max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-start justify-between p-4 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $title }}
                    </h3>
                   <button type="button"
    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5
    {{ $isArabic ? 'mr-auto' : 'ml-auto' }} inline-flex items-center
    dark:hover:bg-gray-600 dark:hover:text-white"
    data-modal-hide="{{ $id }}">

    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
         xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd"
              d="M10 9.293l3.293-3.293a1 1 0 011.414 1.414L11.414 10l3.293 3.293a1 1 0 01-1.414 1.414L10 11.414l-3.293 3.293a1 1 0 01-1.414-1.414L8.586 10 5.293 6.707a1 1 0 011.414-1.414L10 8.586z"
              clip-rule="evenodd"></path>
    </svg>
    <span class="sr-only">Close modal</span>
</button>
                </div>
                <div class="p-6 space-y-6">
                    @if(isset($view) )
                        @include($view)
                    @endif
                    {{ $slot }}
                </div>
                @if (isset($withFooter))
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600">
                    <button data-modal-hide="{{ $id }}" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Close
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

