{{--
    resources/views/partials/data-table.blade.php

    This partial displays individual data attributes in a simple table.
    It's designed to be included by the flowbite-tabs component.

    Props:
    - $data: The associative array of data to display.
--}}
@props(['data'])

<div class="overflow-x-auto relative shadow-md sm:rounded-lg mb-4">
    @if(is_array($data))
    <table class="w-full text-sm  text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        </thead>
        <tbody>
        @php
            // Get all keys from the data array
            $keys = array_keys($data);
            // Chunk the keys into groups of 2 to display two key-value pairs per row
            $chunked_keys = array_chunk($keys, 2);
        @endphp

        @foreach ($chunked_keys as $pair_keys)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                @foreach ($pair_keys as $key)
                    {{-- Display the first key-value pair --}}
                    <th scope="row" class="py-2 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white capitalize">
                        {{ str_replace('_', ' ', $key) }} {{-- Format key for display --}}
                    </th>
                    <td class="py-1 px-6">
                        @php $value = $data[$key]; @endphp
                        @if (is_bool($value) && $value === true)
                            <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-green-900 dark:text-green-300">
                                <i class="fa fa-check-circle"> </i>

                        @elseif (is_bool($value) && $value === false)
                            <span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-red-900 dark:text-red-300">
                                <i class="fa fa-times-circle"> </i>

                        @elseif ($value === null)
                                    <span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-red-900 dark:text-red-300">
                                <i class="fa fa-times-circle"> </i>
                        @else
                            {{ $value }}
                        @endif
                    </td>
                @endforeach

                {{-- If there's only one item in the pair (odd number of total data items),
                     add empty cells for the second key-value pair to maintain 4 columns --}}
                @if (count($pair_keys) < 2)
                    <th class="py-4 px-6"></th> {{-- Empty attribute cell --}}
                    <td class="py-4 px-6"></td>   {{-- Empty value cell --}}
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
