@props([
    'tabs' => [],
    'initialTabId' => null,
])

@php
    $activeId = $initialTabId ?? ($tabs[0]['id'] ?? null);
@endphp

<div x-data="{ active: '{{ $activeId }}' }" class="w-full">
    <!-- Tabs header -->
    <ul class="flex flex-wrap text-sm font-medium text-center text-gray-500 dark:text-gray-400 border-b" role="tablist">
        @foreach($tabs as $tab)
            <li class="mr-2">
                <button type="button"
                        :class="active === '{{ $tab['id'] }}' ? 'text-blue-600 border-blue-600' : 'hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'"
                        class="inline-block p-4 border-b-2 border-transparent rounded-t-lg"
                        @click="active='{{ $tab['id'] }}'"
                        role="tab"
                        aria-controls="tab-{{ $tab['id'] }}"
                        :aria-selected="(active === '{{ $tab['id'] }}').toString()">
                    {{ $tab['title'] }}
                </button>
            </li>
        @endforeach
    </ul>

    <!-- Tabs content -->
    <div class="mt-4">
        @foreach($tabs as $tab)
            <div id="tab-{{ $tab['id'] }}" role="tabpanel" aria-labelledby="tab-{{ $tab['id'] }}"
                 x-show="active === '{{ $tab['id'] }}'" x-cloak>
                @foreach($tab['content'] as $block)
                    @include($block['view'], $block['data'] ?? [])
                @endforeach
            </div>
        @endforeach
    </div>
</div>

{{-- Requires Alpine.js for x-data/x-show. If you prefer Flowbite JS, you can swap this logic accordingly. --}}
