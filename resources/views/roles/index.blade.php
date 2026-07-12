@extends('layouts.app')
@php
    $activeNav = 'roles';
@endphp

@section('page_title', __('Rôles'))
@section('page_subtitle', __('Gestion des rôles et leurs permissions'))

@push('scripts')
    @vite(['resources/js/pages/roles.js'])
@endpush

@section('content')

@can('role.create')
    <div class="flex items-center justify-end mb-6">
        <button
            type="button"
            data-open-create-role
            class="inline-flex h-11 min-w-[10rem] items-center justify-center gap-2 rounded-[10px] bg-[#1c398e] px-6 text-sm font-semibold text-white shadow-[0px_4px_6px_-4px_rgba(0,0,0,0.10),0px_10px_15px_-3px_rgba(0,0,0,0.10)] transition hover:bg-[#163171] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1c398e]"
        >
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5 shrink-0">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            {{ __('Créer un Rôle') }}
        </button>
    </div>
@endcan

<section>
    <div
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        data-role-cards-grid
    >
        @forelse ($roles as $role)
            <x-role-card
                :roleName="$role->name"
                :permissionCount="$role->permissions_count"
                :description="$role->description ?? ''"
                icon="shield"
                :href="route('roles.show', $role)"
            />
        @empty
            <x-empty-state
                data-empty-state
                class="col-span-full"
                icon="fa-solid fa-shield-halved"
                :title="__('Aucun rôle pour le moment.')"
                :description="__('Créez un rôle pour commencer à organiser les permissions des utilisateurs.')"
            >
                @can('role.create')
                    <x-slot:action>
                        <button
                            type="button"
                            data-open-create-role
                            class="inline-flex items-center gap-2 rounded-[10px] bg-[#1c398e] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#163171]"
                        >
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('Créer un Rôle') }}
                        </button>
                    </x-slot:action>
                @endcan
            </x-empty-state>
        @endforelse
    </div>

    @if (method_exists($roles, 'hasPages') && $roles->hasPages())
        <div class="mt-8">{{ $roles->links() }}</div>
    @endif
</section>

{{-- Create role modal --}}
@can('role.create')
    <div
        id="create-role-modal-root"
        data-modal-portal class="sikds-modal-backdrop"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-role-title"
    >
        <div data-modal-overlay class="absolute inset-0"></div>

        <div
            data-modal-panel
            tabindex="-1"
            class="relative z-10 flex max-h-[min(100vh-2rem,900px)] w-full max-w-[768px] flex-col overflow-hidden rounded-[14px] border border-black/10 bg-white shadow-[0px_25px_50px_-12px_rgba(0,0,0,0.25)]"
        >
            <div class="shrink-0 border-b border-black/10 px-6 py-6">
                <h2 id="create-role-title" class="text-2xl font-semibold leading-8 text-[#0A0A0A]">
                    {{ __('Créer un Rôle') }}
                </h2>
                <p class="mt-1 text-sm leading-5 text-[#717182]">
                    {{ __('Définir un nouveau rôle avec des permissions spécifiques') }}
                </p>
            </div>

            <form
                data-create-role-form
                action="{{ route('roles.store') }}"
                method="post"
                class="flex min-h-0 flex-1 flex-col overflow-hidden"
            >
                @csrf
                <div class="min-h-0 flex-1 overflow-y-auto px-6 pt-6 pb-2">
                    <div
                        data-form-errors
                        class="mb-4 hidden rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                        role="alert"
                    ></div>

                    <div class="flex flex-col gap-6">
                        {{-- Name + description --}}
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="role-name" class="text-sm font-medium text-black">
                                    {{ __('Nom du Rôle') }} <span class="text-red-600">*</span>
                                </label>
                                <input
                                    id="role-name"
                                    name="name"
                                    type="text"
                                    required
                                    maxlength="255"
                                    placeholder="{{ __('Ex: Gestionnaire de Documents') }}"
                                    class="h-[37px] w-full rounded-[10px] border border-black/10 px-3 py-2 text-sm text-[#0A0A0A] placeholder:text-[#717182] outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20"
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="role-description" class="text-sm font-medium text-black">{{ __('Description') }}</label>
                                <textarea
                                    id="role-description"
                                    name="description"
                                    rows="3"
                                    placeholder="{{ __('Description du rôle...') }}"
                                    class="min-h-[96px] w-full resize-y rounded-[10px] border border-black/10 px-3 py-2 text-sm text-[#0A0A0A] placeholder:text-[#717182] outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20"
                                ></textarea>
                            </div>
                        </div>

                        {{-- Permissions picker --}}
                        <div class="flex flex-col gap-4">
                            <div class="flex items-start justify-between gap-4">
                                <h3 class="text-lg font-semibold leading-[27px] text-[#0A0A0A]">{{ __('Permissions') }}</h3>
                                <span data-selected-count class="text-sm text-[#717182]">{{ __('0 sélectionnées') }}</span>
                            </div>

                            @if (empty($permissionsGrouped))
                                <p class="text-sm text-[#717182]">{{ __('Aucune permission disponible.') }}</p>
                            @else
                                <div class="flex flex-col gap-4 pb-4">
                                    @foreach ($permissionsGrouped as $section)
                                        <div
                                            class="flex flex-col gap-3 rounded-[10px] border border-black/10 bg-[#ECECF04D] p-4"
                                            data-section="{{ $section['key'] }}"
                                        >
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <h4 class="text-base font-semibold text-[#0A0A0A]">{{ $section['label'] }}</h4>
                                                <button
                                                    type="button"
                                                    data-select-all-section="{{ $section['key'] }}"
                                                    class="text-sm font-medium text-[#1c398e] underline-offset-2 hover:underline"
                                                >{{ __('Tout sélectionner') }}</button>
                                            </div>
                                            <div class="flex flex-col gap-2">
                                                @foreach ($section['permissions'] as $perm)
                                                    <label
                                                        class="flex cursor-pointer items-start gap-3 rounded-lg px-1 py-1 hover:bg-black/[0.03]"
                                                        for="perm-{{ $perm['id'] }}"
                                                    >
                                                        <input
                                                            id="perm-{{ $perm['id'] }}"
                                                            name="permission_ids[]"
                                                            value="{{ $perm['id'] }}"
                                                            type="checkbox"
                                                            data-perm-section="{{ $section['key'] }}"
                                                            class="perm-checkbox mt-0.5 size-4 shrink-0 rounded-full border border-black/20 accent-[#1c398e] focus:ring-[#1c398e]"
                                                        />
                                                        <span class="text-sm leading-5 text-[#0A0A0A]">{{ $perm['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-black/10 bg-[#ECECF04D] px-4 py-4">
                    <button
                        type="button"
                        data-close-modal
                        class="inline-flex h-11 w-[106px] items-center justify-center rounded-[10px] border border-black/10 bg-white text-sm font-medium text-[#0A0A0A] transition hover:bg-black/[0.03] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1c398e]"
                    >{{ __('Annuler') }}</button>
                    <button
                        type="submit"
                        data-submit-role
                        class="inline-flex h-11 items-center justify-center rounded-[10px] bg-[#1c398e] px-6 text-sm font-semibold text-white transition hover:bg-[#163171] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1c398e] disabled:opacity-60"
                    >{{ __('Créer le Rôle') }}</button>
                </div>
            </form>
        </div>
    </div>
@endcan

@endsection
