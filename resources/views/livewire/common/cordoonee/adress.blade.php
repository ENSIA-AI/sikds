<div>
    {{-- Address creation form for Livewire component --}}

    <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Ajouter une nouvelle adresse / إضافة عنوان جديد</h2>

        <form wire:submit.prevent="saveAddress" class="space-y-6">
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <tbody class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <tr class="border-b">
                        <td class="p-3 align-top w-1/2 align-top">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Type d'adresse / نوع العنوان</div>
                            <select wire:model.change="address_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Choisir -- / اختر --</option>
                                @if(isset($types) && is_iterable($types))
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                @else
                                    <option value="home">{{ __('Home') }}</option>
                                    <option value="work">{{ __('Work') }}</option>
                                @endif
                            </select>
                            @error('address.type') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                        </td>

                        <td class="p-3 align-top w-1/2">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Pays / الدولة</div>
                            <select wire:model="address_pays" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Choisir -- / اختر --</option>
                                @if(isset($countries) && is_iterable($countries))
                                    @foreach($countries as $key=>$c)
                                        <option value="{{ $key }}">{{ $c }}</option>
                                    @endforeach
                                @else
                                    <option value="DZ">Algérie</option>
                                @endif
                            </select>
                            @error('address.pays') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                        </td>
                    </tr>

                    <tr class="border-b">
                        <td class="p-3 align-top">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Wilaya / الولاية</div>
                            <select wire:model.change="addressWilaya" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Choisir -- / اختر --</option>
                                @if(isset($wilayas) && is_iterable($wilayas))
                                    @foreach($wilayas as $key=>$w)
                                        <option value="{{ $key }}">{{ $w }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('addressWilaya') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                        </td>

                        <td class="p-3 align-top">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Daira / دائرة</div>
                            <select wire:model.change="addressDaira" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Choisir -- / اختر --</option>
                                @if(isset($dairas) && is_iterable($dairas))
                                    @foreach($dairas as $key=>$d)
                                        <option value="{{ $key }}">{{ $d }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('addressDaira') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                        </td>
                    </tr>

                    <tr class="border-b">
                        <td class="p-3 align-top">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Commune / بلدية</div>
                            <select wire:model.change="addressCommune" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Choisir -- / اختر --</option>
                                @if(isset($communes) && is_iterable($communes))
                                    @foreach($communes as $key=>$c)
                                        <option value="{{ $key }}">{{ $c }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('addressCommune') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                        </td>

                        <td class="p-3 align-top">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Boîte postale / الصندوق البريدي</div>
                            <input type="text" wire:model.defer="address_boite_postale" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: BP 123" />
                            @error('address_boite_postale') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                        </td>
                    </tr>

                    <tr class="border-b">
                        <td class="p-3 align-top">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Adresse complète (Français)</div>
                            <textarea wire:model.defer="address_full_address_fr" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Rue, numéro, bâtiment..."></textarea>
                            @error('address_full_address_fr') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                        </td>

                        <td class="p-3 align-top">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">العنوان الكامل (بالعربية)</div>
                            <textarea wire:model.defer="address_full_address_ar" rows="3" dir="rtl" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-right text-gray-700 dark:text-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="شارع، رقم، بناية..."></textarea>
                            @error('address_full_address_ar') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" class="p-3 text-right">
                            <button type="button" wire:click="resetForm" class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 mr-3">Annuler / إلغاء</button>
                            <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Enregistrer / حفظ
                                <span wire:loading wire:target="saveAddress" class="ml-2">...</span>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

</div>
