@props([
    'permissionsByCategory',
    'modalKey' => 'create',
])

@php
    /** @var \Illuminate\Support\Collection $permissionsByCategory */
    $categoryOrder = ['documents', 'distributions', 'tags', 'indexing', 'utilisateurs', 'users', 'roles', 'institutions', 'institution', 'chatbot', 'audit'];
    $categoryLabels = [
        'documents' => __('Documents'),
        'distributions' => __('Distributions'),
        'tags' => __('Tags'),
        'indexing' => __('Indexation'),
        'utilisateurs' => __('Utilisateurs'),
        'users' => __('Utilisateurs'),
        'roles' => __('Rôles'),
        'institutions' => __('Institutions'),
        'institution' => __('Institutions'),
        'chatbot' => __('Chatbot'),
        'audit' => __('Audit'),
    ];
    $normalized = $permissionsByCategory->mapWithKeys(fn ($items, $key) => [strtolower((string) $key) => $items]);
    $orderedKeys = collect($categoryOrder)->filter(fn ($k) => $normalized->has($k));
    $restKeys = $normalized->keys()->diff($orderedKeys)->values();
    $finalKeys = $orderedKeys->merge($restKeys);
@endphp

<div class="flex flex-col gap-2" data-perm-root="{{ $modalKey }}">
    @foreach ($finalKeys as $catKey)
        @php
            $perms = $normalized->get($catKey, collect());
        @endphp
        @continue($perms->isEmpty())
        <div data-perm-category class="overflow-hidden rounded-[10px] border border-black/10 bg-white">
            <button
                type="button"
                data-perm-category-toggle
                class="flex w-full items-center gap-3 px-3 py-2.5 text-start font-inter text-sm font-medium text-[#0A0A0A] transition hover:bg-black/[0.02]"
                aria-expanded="false"
            >
                <span class="min-w-0 flex-1 truncate">{{ $categoryLabels[$catKey] ?? ucfirst((string) $catKey) }}</span>
                <span class="shrink-0 rounded-full bg-[#ECECF04D] px-2 py-0.5 font-inter text-xs font-medium text-[#717182]" data-perm-count>
                    {{ $perms->count() }} {{ __('permissions') }}
                </span>
                <svg data-perm-chevron class="size-4 shrink-0 text-[#717182] transition-transform" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div data-perm-category-panel class="hidden border-t border-black/5 px-3 py-2">
                <ul class="flex max-h-48 flex-col gap-2 overflow-y-auto">
                    @foreach ($perms as $perm)
                        <li>
                            <label class="flex cursor-pointer items-start gap-2 rounded-[8px] px-1 py-1 hover:bg-[#F8FAFC]">
                                <input
                                    type="checkbox"
                                    name="permission_ids[]"
                                    value="{{ $perm->id }}"
                                    data-perm-checkbox
                                    data-perm-id="{{ $perm->id }}"
                                    class="mt-0.5 size-4 shrink-0 rounded border-black/20 text-[#1E3A8A] focus:ring-[#1E3A8A]"
                                />
                                <span class="font-inter text-sm leading-5 text-[#193CB8]">{{ $perm->name }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endforeach
</div>
