<div>
<div class="max-w-xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Téléphone / الهاتف</h3>

    <form wire:submit.prevent="savePhone" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Phone number -->
            <div>
                <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Numéro de téléphone / رقم الهاتف</label>
                <input id="phone_number" type="text" wire:model.defer="phone_number"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="+213 6X XX XX XX" />
                @error('phone_number') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Phone type -->
            <div>
                <label for="phone_number_type" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Type / النوع</label>
                <select id="phone_number_type" wire:model.defer="phone_number_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Sélectionner --</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('phone_number_type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Phone nature -->
            <div class="md:col-span-2">
                <label for="phone_nature" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nature / الطابع</label>
                <select id="phone_nature" wire:model.defer="phone_nature" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Sélectionner --</option>
                    @foreach($natures as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('phone_nature') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-2">
            <button type="button" wire:click="resetForm" class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100">Annuler / إلغاء</button>
            <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 flex items-center">
                <span>Enregistrer / حفظ</span>
                <svg wire:loading wire:target="savePhone" class="animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </button>
        </div>
    </form>
</div>
</div>
