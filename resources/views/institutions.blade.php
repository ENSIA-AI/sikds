@extends('layouts.app')
@php
    $activeNav = 'institutions';
@endphp

@section('page_title', __('Gérer les Institutions'))
@section('page_subtitle', __('Catalogue complet des institutions'))

@push('scripts')
    @vite(['resources/js/pages/institutions.js'])
@endpush

@section('content')

<div
    data-institutions-api-base="{{ url('/institutions') }}"
    data-institutions-modal-create-title="{{ __('Nouvelle institution') }}"
    data-institutions-modal-create-subtitle="{{ __('Renseigner les informations de l\'institution') }}"
    data-institutions-modal-edit-title="{{ __('Modifier l\'Institution') }}"
    data-institutions-modal-edit-subtitle="{{ __('Modifier les informations de l\'institution') }}"
    data-institutions-modal-unexpected-error="{{ __('Une erreur est survenue.') }}"
    data-institutions-modal-server-unreachable="{{ __('Impossible de contacter le serveur.') }}"
    data-institutions-modal-delete-confirm="{{ __('Supprimer l\'institution « :name » ?') }}"
    data-institutions-modal-delete-failed="{{ __('Suppression impossible.') }}"
    data-institutions-modal-empty-state="{{ __('Aucune institution pour le moment.') }}"
>

    @can('institution.create')
        <div class="flex items-center justify-end mb-4">
            <button
                type="button"
                data-open-create-institution
                class="inline-flex h-11 min-w-[10rem] items-center justify-center gap-2 rounded-[10px] bg-[#1E3A8A] px-6 text-sm font-semibold text-white shadow-[0px_4px_6px_-4px_rgba(0,0,0,0.10),0px_10px_15px_-3px_rgba(0,0,0,0.10)] transition hover:bg-[#163171] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A]"
            >
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5 shrink-0">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                {{ __('Nouvelle institution') }}
            </button>
        </div>
    @endcan

    {{-- Stats bar --}}
    <section class="rounded-[14px] border border-black/10 bg-white px-[17.67px] pb-[12.67px] pt-[16.67px] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)] mb-6">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="flex flex-col gap-1">
                <div class="text-sm font-normal leading-5 text-[#717182]">{{ __('Total Institutions') }}</div>
                <div class="text-2xl font-semibold leading-8 text-[#0A0A0A]">{{ $stats['total_institutions'] ?? 0 }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-sm font-normal leading-5 text-[#717182]">{{ __('Institutions actives') }}</div>
                <div class="text-2xl font-semibold leading-8 text-[#0A0A0A]">{{ $stats['active_institutions'] ?? 0 }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-sm font-normal leading-5 text-[#717182]">{{ __('Total Utilisateurs') }}</div>
                <div class="text-2xl font-semibold leading-8 text-[#0A0A0A]">{{ $stats['total_users'] ?? 0 }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-sm font-normal leading-5 text-[#717182]">{{ __('Total Documents') }}</div>
                <div class="text-2xl font-semibold leading-8 text-[#0A0A0A]">{{ $stats['total_documents'] ?? 0 }}</div>
            </div>
        </div>
    </section>

    {{-- Institutions grid --}}
    <section>
        <div
            class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-2"
            data-institutions-grid
        >
            @forelse ($institutions as $institution)
                <x-institution-card :institution="$institution" />
            @empty
                <p
                    data-institutions-empty
                    class="col-span-full w-full rounded-[14px] border border-black/10 bg-white p-6 text-sm text-[#717182] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]"
                >
                    {{ __('Aucune institution pour le moment.') }}
                    @can('institution.create')
                        {{ __('Utilisez « Nouvelle institution » pour en ajouter une.') }}
                    @endcan
                </p>
            @endforelse
        </div>
    </section>

    {{-- Create / Edit modal --}}
    @canany(['institution.create', 'institution.edit'])
        
        <div
            id="institution-modal-root"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
            hidden
            role="dialog"
            aria-modal="true"
            aria-labelledby="institution-modal-title"
        >
            <div data-modal-overlay class="absolute inset-0 bg-black/40 transition-opacity"></div>

            <div
                data-modal-panel
                tabindex="-1"
                class="relative z-10 flex w-full max-w-[672px] flex-col rounded-[14px] border border-black/10 bg-white shadow-[0px_25px_50px_-12px_rgba(0,0,0,0.25)] max-h-[min(100vh-2rem,640px)] overflow-hidden"
            >
                <div class="shrink-0 border-b border-black/10 bg-[#ECECF04D] px-6 pb-px pt-6">
                    <h2 id="institution-modal-title" data-modal-title class="text-2xl font-semibold leading-8 text-black">
                        {{ __('Nouvelle institution') }}
                    </h2>
                    <p data-modal-subtitle class="mt-1 text-sm leading-5 text-[#717182]">
                        {{ __('Renseigner les informations de l\'institution') }}
                    </p>
                </div>

                <form
                    data-institution-form
                    method="post"
                    enctype="multipart/form-data"
                    class="flex flex-col overflow-hidden"
                >
                    @csrf
                    <div data-method-spoof></div>

                    <div class="max-h-[min(520px,calc(100dvh-12rem))] overflow-y-auto px-6 pb-6 pt-6">
                        <div
                            data-form-errors
                            class="mb-4 hidden rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"
                            role="alert"
                        ></div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-institutions.text-input :label="__('Nom complet')"          name="name"          value="" autocomplete="organization" />
                            <x-institutions.text-input :label="__('Acronyme')"             name="code"          value="" autocomplete="off" />
                            <x-institutions.text-input :label="__('Email')"                name="contact_email" type="email" value="" autocomplete="email" />
                            <x-institutions.text-input :label="__('Téléphone (optionnel)')" name="contact_phone" type="tel" value="" autocomplete="tel" inputmode="tel" pattern="[\d\s+().-]{8,32}" :required="false" />
                        </div>

                        <div class="mt-4 flex w-full flex-col gap-2">
                            <label for="institution-address" class="text-sm font-medium leading-5 text-[#0A0A0A]">
                                {{ __('Adresse') }} <span class="font-normal text-[#717182]">{{ __('(optionnel)') }}</span>
                            </label>
                            <textarea
                                id="institution-address"
                                name="address"
                                rows="2"
                                class="min-h-[44px] w-full rounded-[10px] border border-black/10 bg-white px-3 py-2 text-sm text-[#0A0A0A] shadow-sm outline-none transition focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A]/20"
                            ></textarea>
                        </div>

                        <div class="mt-4 flex w-full flex-col gap-2">
                            <label for="institution-logo" class="text-sm font-medium leading-5 text-[#0A0A0A]">
                                {{ __('Logo') }} <span class="font-normal text-[#717182]">{{ __('(optionnel)') }}</span>
                            </label>
                            <input
                                id="institution-logo"
                                name="logo"
                                type="file"
                                accept="image/*"
                                class="block w-full cursor-pointer rounded-[10px] border border-dashed border-black/15 bg-white px-3 py-2 text-sm text-[#0A0A0A] file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#ECECF04D] file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-[#0A0A0A] hover:file:bg-[#ECECF04D]/80"
                            />
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center justify-between gap-3 border-t border-black/10 px-4 py-4">
                        <button
                            type="button"
                            data-close-modal
                            class="inline-flex h-[45px] min-w-[106px] items-center justify-center rounded-[10px] border border-black/10 bg-white px-4 text-sm font-medium text-black transition hover:bg-black/[0.03] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A]"
                        >
                            {{ __('Annuler') }}
                        </button>
                        <button
                            type="submit"
                            data-submit-institution
                            class="inline-flex h-11 min-w-[126px] items-center justify-center gap-2 rounded-[10px] bg-[#1E3A8A] px-6 text-sm font-semibold text-white transition hover:bg-[#163171] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A] disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ __('Enregistrer') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcanany

    {{-- Toast notification --}}
    <div
        data-app-toast
        class="pointer-events-none fixed bottom-6 left-1/2 z-[60] hidden w-[min(100vw-2rem,28rem)] -translate-x-1/2 rounded-[10px] border px-4 py-3 text-sm font-medium shadow-lg transition"
        role="status"
        aria-live="polite"
    ></div>

</div>

@endsection
