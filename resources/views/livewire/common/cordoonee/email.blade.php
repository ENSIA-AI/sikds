<div>
    <form wire:submit.prevent="saveEmail" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2" for="email">Adresse Email / البريد الإلكتروني</label>
        <input type="email" wire:model="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500"
               placeholder="Email" />
        @error('email') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2" for="email_type">Type d'adresse / نوع العنوان</label>
        <select wire:model="email_type" id="email_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Choisir -- / اختر --</option>
            @if(isset($types) && is_iterable($types))
                @foreach($types as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            @else
                <option value="personal">{{ __('Personal') }}</option>
                <option value="work">{{ __('Work') }}</option>
            @endif
        </select>
        @error('email_type') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
    </div>
            <div class="flex justify-end gap-3 mt-2">
                <button type="button" wire:click="resetForm" class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100">Annuler / إلغاء</button>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 flex items-center">
                    <span>Enregistrer / حفظ</span>
                    <svg wire:loading wire:target="saveEmail" class="animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </form>
</div>
