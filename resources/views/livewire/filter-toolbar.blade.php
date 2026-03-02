<div>
    <div class="w-full p-4 bg-white border border-gray-200 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Filter by Award Type -->
            <div class="flex items-center gap-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('filter.award_type') }}:</h3>
                <div class="flex items-center gap-2">
                    <ul class="flex items-center gap-2">
                        <li>
                            <input type="radio" id="filter-all" name="filter" value="" wire:model.live="filter" class="hidden peer" required />
                            <label for="filter-all" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer dark:text-gray-400 dark:bg-gray-800 dark:border-gray-700 peer-checked:bg-blue-600 peer-checked:text-white">
                                {{ __('filter.all') }}
                            </label>
                        </li>
                        <li>
                            <input type="radio" id="filter-student" name="filter" value="1" wire:model.live="filter" class="hidden peer" required />
                            <label for="filter-student" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer dark:text-gray-400 dark:bg-gray-800 dark:border-gray-700 peer-checked:bg-blue-600 peer-checked:text-white">
                                {{ __('filter.students') }}
                            </label>
                        </li>
                        <li>
                            <input type="radio" id="filter-researchers" name="filter" value="2" wire:model.live="filter" class="hidden peer" />
                            <label for="filter-researchers" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer dark:text-gray-400 dark:bg-gray-800 dark:border-gray-700 peer-checked:bg-blue-600 peer-checked:text-white">
                                {{ __('filter.researchers') }}
                            </label>
                        </li>
                    </ul>


                </div>
            </div>

            <!-- Filter by Domain -->
            <div class="flex items-center gap-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('filter.domain') }}:</h3>
                <div class="flex items-center gap-2">
                    <ul class="flex items-center gap-2">
                    @foreach($domains as $key => $domain)
                        <li>
                            <input type="radio" id="domain-{{$key}}" name="domain" value="{{$key}}" wire:model.live="domain" class="hidden peer" required />
                            <label for="domain-{{$key}}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg cursor-pointer dark:text-gray-400 dark:bg-gray-800 dark:border-gray-700 peer-checked:bg-blue-600 peer-checked:text-white">
                                {{$domain}}
                            </label>
                        </li>
                    @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
