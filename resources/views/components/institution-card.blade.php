@props([
    'institution',
])

@php
    /** @var \App\Domain\Users\Models\Institution $institution */
    $usersCount = (int) ($institution->users_count ?? $institution->users()->count());
    $documentsCount = (int) ($institution->documents_count ?? $institution->documentInstitutionTargets()->count());
    $logoUrl = $institution->logo_public_url;
    $iconSrc = $logoUrl ?: asset('images/Container.png');
    $editPayload = base64_encode(json_encode([
        'id' => $institution->id,
        'name' => $institution->name,
        'code' => $institution->code,
        'contact_email' => $institution->contact_email,
        'contact_phone' => (string) ($institution->contact_phone ?? ''),
        'address' => (string) ($institution->address ?? ''),
    ]));
@endphp

<article
    id="institution-card-{{ $institution->id }}"
    data-institution-card
    class="flex w-full min-w-0 pb-2 flex-col overflow-hidden rounded-[14px] border border-black/10 bg-white shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]"
>
    <div class=" px-4 pb-[10px] pt-4">
        <div class="flex w-full min-w-0 items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <img
                    src="{{ $iconSrc }}"
                    alt=""
                    class="size-12 shrink-0 rounded-[10px] object-cover sm:size-14"
                    loading="lazy"
                />
                <p class="min-w-0 text-lg font-semibold leading-[27px] tracking-normal text-[#0A0A0A]">
                    {{ $institution->code }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
            @can('institution.edit')
                <button
                    type="button"
                    data-open-institution-edit
                    data-edit-payload="{{ $editPayload }}"
                    class="rounded-lg p-2 text-[#717182] transition hover:bg-black/5 hover:text-[#0A0A0A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A]"
                    aria-label="Modifier l'institution"
                >
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                        <path
                            d="M4 20.5h4.5L19.4 9.6a2.12 2.12 0 0 0-3-3L5.5 17.5V20.5H4Z"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linejoin="round"
                        />
                        <path d="M13.5 6.5l4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </button>
            @endcan
            @can('institution.delete')
                <button
                    type="button"
                    data-delete-institution
                    data-institution-id="{{ $institution->id }}"
                    data-institution-name="{{ e($institution->name) }}"
                    class="rounded-lg p-2 text-red-600 transition hover:bg-red-50 hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                    aria-label="Supprimer l'institution"
                >
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                        <path
                            d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7h12Z"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </button>
            @endcan
            </div>
        </div>
    </div>

    <div class="flex min-h-0 flex-1 flex-col gap-4 p-4">
        <p class="min-w-0 text-sm font-normal leading-5 tracking-normal text-[#717182]">
            {{ $institution->name }}
        </p>

        <div class="flex flex-wrap items-center gap-4 text-sm font-normal leading-5 text-[#717182]">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0 text-[#717182]" aria-hidden="true">
                    <path
                        d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <span><span class="font-medium text-[#0A0A0A]">{{ $usersCount }}</span> utilisateurs</span>
            </div>
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0 text-[#717182]" aria-hidden="true">
                    <path
                        d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2Z"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linejoin="round"
                    />
                    <path d="M14 2v6h6" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                </svg>
                <span><span class="font-medium text-[#0A0A0A]">{{ $documentsCount }}</span> documents</span>
            </div>
        </div>
    </div>
</article>
