@extends('layouts.app')
@php
    $activeNav = 'permissions';
@endphp

@section('page_title', __('Gérer les Permissions'))
@section('page_subtitle', __('Catalogue complet des permissions système organisé par catégorie'))

@push('scripts')
    @vite(['resources/js/pages/permissions.js'])
@endpush

@section('content')

<div data-permissions-page class="space-y-6">

    {{-- Stats bar --}}
    <section class="rounded-[14px] border border-black/10 bg-white px-[17.67px] pb-[12.67px] pt-[16.67px] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="flex flex-col gap-1">
                <div class="text-sm font-normal leading-5 text-[#717182]">{{ __('Total Permissions') }}</div>
                <div class="text-2xl font-semibold leading-8 text-[#0A0A0A]">{{ $stats['total_permissions'] ?? 0 }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-sm font-normal leading-5 text-[#717182]">{{ __('Catégories') }}</div>
                <div class="text-2xl font-semibold leading-8 text-[#0A0A0A]">{{ $stats['categories'] ?? 0 }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-sm font-normal leading-5 text-[#717182]">{{ __('Permissions Critiques') }}</div>
                <div class="text-2xl font-semibold leading-8 text-[#0A0A0A]">{{ $stats['critical'] ?? 0 }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-sm font-normal leading-5 text-[#717182]">{{ __('Assignées aux rôles') }}</div>
                <div class="text-2xl font-semibold leading-8 text-[#0A0A0A]">{{ $stats['assigned'] ?? 0 }}</div>
            </div>
        </div>
    </section>

    {{-- Search bar intended --}}
    <section>
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-black/50">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                    <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M16 16l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
            </div>
            <input
                data-permissions-search
                type="text"
                placeholder="{{ __('Rechercher par nom, code ou description...') }}"
                class="h-[41.33px] w-full rounded-[10px] border border-black/10 bg-white ps-10 pe-3 text-sm text-[#0A0A0A] placeholder:text-[#0A0A0A80] outline-none focus:border-black/20 focus:ring-2 focus:ring-black/10"
            />
        </div>
    </section>

    {{-- Category filter pills --}}
    <section>
        <div class="flex flex-wrap gap-2">
            <x-permissions.filter-button
                filterKey="all"
                :label="__('Toutes')"
                :count="$totalCount ?? 0"
                :selected="true"
                activeBg="#030213"
                activeText="#FFFFFF"
                inactiveBg="#FFFFFF"
                inactiveText="#000000"
            />
            @foreach (($filters ?? []) as $f)
                <x-permissions.filter-button
                    :filterKey="$f['key']"
                    :label="$f['label']"
                    :count="$f['count']"
                    activeBg="#030213"
                    activeText="#FFFFFF"
                    inactiveBg="#FFFFFF"
                    inactiveText="#000000"
                />
            @endforeach
        </div>
    </section>

    {{-- Permissions grid --}}
    <section>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @php
                $categoryIconStyles = [
                    'Documents'    => ['src' => asset('images/document.png'), 'bg' => '#DBEAFE'],
                    'Distribution' => ['src' => asset('images/distribution.png'), 'bg' => '#DCFCE7'],
                    'Tags'         => ['src' => asset('images/tags.png'), 'bg' => '#F3E8FF'],
                    'Indexation'   => ['src' => asset('indexing-blue.svg'), 'bg' => '#BAD4E6'],
                    'Utilisateurs' => ['src' => asset('images/utilisateurs.png'), 'bg' => '#FFEDD4'],
                    'Roles'        => ['src' => asset('images/roles.png'), 'bg' => '#FFE2E2'],
                    'Institutions' => ['src' => asset('images/institutions.png'), 'bg' => '#CBFBF1'],
                    'Chatbot'      => ['src' => asset('images/chatbot.png'), 'bg' => '#E0E7FF'],
                    'Audit'        => ['src' => asset('images/audit.png'), 'bg' => '#FCE7F3'],
                ];
            @endphp

            @foreach (($permissionsByCategory ?? []) as $category => $perms)
                @php
                    $catKey     = (string) $category;
                    $iconStyle  = $categoryIconStyles[$catKey] ?? null;
                    $permCount  = is_countable($perms) ? count($perms) : 0;
                @endphp

                <article
                    data-permissions-category-card
                    data-category-key="{{ $catKey }}"
                    class="overflow-hidden rounded-[14px] border border-black/10 bg-white shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]"
                >
                    <div class="border-b border-black/10 px-4 pb-[8.67px] pt-4">
                        <div class="flex items-start gap-3">
                            @if ($iconStyle)
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] px-[10px]"
                                    style="background-color: {{ $iconStyle['bg'] }}"
                                >
                                    <img src="{{ $iconStyle['src'] }}" alt="" class="max-h-5 w-auto max-w-full object-contain" />
                                </div>
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-black/[0.03] px-[10px] text-black">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5"><path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold leading-[27px] text-black">{{ __($catKey) }}</h3>
                                <p class="text-sm leading-5 text-[#717182]">{{ $permCount }} {{ __('permission(s)') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 flex flex-col gap-3">
                        @forelse ($perms as $perm)
                            @php
                                $name = (string) ($perm['name'] ?? '');
                                $code = (string) ($perm['code'] ?? '');
                                $desc = (string) ($perm['description'] ?? '');
                                $hay  = trim($name.' '.$code.' '.$desc);
                            @endphp
                            <div
                                data-permissions-item
                                data-search-haystack="{{ $hay }}"
                                class="rounded-[10px] border border-black/10 bg-white p-3 shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 shrink-0 text-[#0A0A0A]">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-4"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-base font-medium leading-6 text-[#0A0A0A]">{{ $name }}</p>
                                        @if ($desc !== '')
                                            <p class="mt-1 text-sm leading-5 text-[#717182]">{{ $desc }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-[10px] border border-black/10 bg-[#ECECF04D] p-3 text-sm text-[#717182]">
                                {{ __('Aucune permission dans cette catégorie.') }}
                            </div>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>

        <div
            data-permissions-empty
            class="hidden mt-6 rounded-[14px] border border-black/10 bg-white p-6 text-sm text-[#717182] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]"
        >
            {{ __('Aucune permission ne correspond à votre recherche.') }}
        </div>
    </section>

</div>

@endsection
