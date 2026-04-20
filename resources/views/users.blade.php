@extends('layouts.app')
@php
    $activeNav = 'users';
@endphp

@section('page_title', 'Utilisateurs')
@section('page_subtitle', 'Gérer les accès et les permissions des utilisateurs')

@push('scripts')
    @vite(['resources/js/pages/utilisateurs.js'])
@endpush

@section('content')

<div class="mx-auto w-full max-w-full px-4 pb-10 sm:px-6" data-users-api-base="{{ url('/users') }}">
    <script type="application/json" id="users-roles-bootstrap">
        @json($rolesForUi)
    </script>

    @can('user.manage')
        @can('user.assign.permissions')
            <div class="mb-6 flex justify-end">
                <button
                    type="button"
                    data-open-create-user
                    class="inline-flex h-11 min-w-[10rem] items-center justify-center gap-2 rounded-[10px] bg-[#1E3A8A] px-6 font-inter text-sm font-semibold text-white shadow-[0px_4px_6px_-4px_rgba(0,0,0,0.10),0px_10px_15px_-3px_rgba(0,0,0,0.10)] transition hover:bg-[#163171] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A]"
                >
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5 shrink-0">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    Ajouter un utilisateur
                </button>
            </div>
        @endcan
    @endcan

    {{-- Container A — Statistics --}}
    <section aria-label="Statistiques utilisateurs" class="mx-auto mb-6 w-full max-w-full">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <article class="flex h-[89px] justify-between gap-3 rounded-[14px] border-[0.67px] border-black/10 bg-white px-[16.67px] py-[16.67px] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]">
                <div class="flex min-w-0 flex-col justify-center">
                    <p class="font-inter text-sm font-normal text-[#717182]">Total Utilisateurs</p>
                    <p class="font-inter text-2xl font-semibold text-[#0A0A0A]" data-stat="total">{{ $stats['total_users'] ?? 0 }}</p>
                </div>
                <div class="flex size-12 shrink-0 items-center justify-center rounded-[12px] bg-[#DBEAFE]">
                    <img src="{{ asset('images/user.svg') }}" alt="" class="size-7" width="28" height="28" />
                </div>
            </article>
            <article class="flex h-[89px] justify-between gap-3 rounded-[14px] border-[0.67px] border-black/10 bg-white px-[16.67px] py-[16.67px] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]">
                <div class="flex min-w-0 flex-col justify-center">
                    <p class="font-inter text-sm font-normal text-[#717182]">Utilisateurs Actifs</p>
                    <p class="font-inter text-2xl font-semibold text-[#0A0A0A]" data-stat="active">{{ $stats['active_users'] ?? 0 }}</p>
                </div>
                <div class="flex size-12 shrink-0 items-center justify-center rounded-[12px] bg-[#DCFCE7]">
                    <img src="{{ asset('images/actif.svg') }}" alt="" class="size-7" width="28" height="28" />
                </div>
            </article>
            <article class="flex h-[89px] justify-between gap-3 rounded-[14px] border-[0.67px] border-black/10 bg-white px-[16.67px] py-[16.67px] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)] md:col-span-2 lg:col-span-1">
                <div class="flex min-w-0 flex-col justify-center">
                    <p class="font-inter text-sm font-normal text-[#717182]">Utilisateurs Inactifs</p>
                    <p class="font-inter text-2xl font-semibold text-[#0A0A0A]" data-stat="inactive">{{ $stats['inactive_users'] ?? 0 }}</p>
                </div>
                <div class="flex size-12 shrink-0 items-center justify-center rounded-[12px] bg-[#FFE2E2]">
                    <img src="{{ asset('images/inactif.svg') }}" alt="" class="size-7" width="28" height="28" />
                </div>
            </article>
        </div>
    </section>

    {{-- Container B — Search & Filters --}}
<section aria-label="Recherche et filtres" class="relative mx-auto mb-6 w-full max-w-full">
    <div class="flex w-full flex-col gap-3 sm:h-[41.33px] sm:flex-row sm:gap-3">
        {{-- Search Input --}}
        <div class="relative w-full sm:flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-black/50">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                    <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M16 16l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
            </div>
            <input
                type="search"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                data-users-search
                placeholder="Rechercher par nom, email, institution..."
                class="h-[41.33px] w-full rounded-[10px] border border-black/10 bg-white pl-10 pr-3 text-sm text-[#0A0A0A] placeholder:text-[#0A0A0A80] outline-none transition focus:border-black/20 focus:ring-2 focus:ring-black/10"
                autocomplete="off"
            />
        </div>

        {{-- Filter Button --}}
        <div class="relative shrink-0">
            <button
                type="button"
                data-filter-toggle
                class="inline-flex h-[41.33px] w-full min-w-[95px] items-center justify-center gap-2 rounded-[10px] border border-black/10 bg-white px-4 font-inter text-sm font-medium text-[#0A0A0A] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.08)] transition hover:bg-black/[0.02] data-[open=true]:border-black data-[open=true]:bg-black data-[open=true]:text-white"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                Filters
            </button>
            <div
                data-filter-popup
                hidden
                class="absolute right-0 z-50 mt-2 w-[min(100vw-2rem,317px)] rounded-[14px] border border-black/10 bg-white p-4 shadow-[0px_25px_50px_-12px_rgba(0,0,0,0.25)]"
            >
                <p class="mb-3 font-inter text-lg font-semibold text-[#0A0A0A]">Filtrer par rôle</p>
                <ul class="flex max-h-[min(60vh,220px)] flex-col gap-1 overflow-y-auto" role="listbox">
                    <li>
                        
                            <a    href="{{ route('utilisateurs', array_filter(['search' => $filters['search'] ?? null])) }}"
                            class="flex h-9 w-full items-center rounded-[10px] px-3 font-inter text-sm text-[#0A0A0A] hover:bg-[#F1F5F9] {{ empty($filters['role_id']) ? 'bg-[#EFF6FF] font-medium text-[#1C398E]' : '' }}"
                        >
                            Tous les rôles
                        </a>
                    </li>
                    @foreach ($roles as $role)
                        <li>
                            
                            <a    href="{{ route('utilisateurs', array_filter(['search' => $filters['search'] ?? null, 'role_id' => $role->id])) }}"
                                class="flex h-9 w-full items-center rounded-[10px] px-3 font-inter text-sm text-[#0A0A0A] hover:bg-[#F1F5F9] {{ (int) ($filters['role_id'] ?? 0) === (int) $role->id ? 'bg-[#EFF6FF] font-medium text-[#1C398E]' : '' }}"
                            >
                                {{ $role->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Alphabetic Sort Button --}}
        <div class="relative shrink-0">
            
                <a    href="{{ route('utilisateurs', array_filter([
                    'search' => $filters['search'] ?? null,
                    'role_id' => $filters['role_id'] ?? null,
                    'sort' => 'name',
                    'direction' => ($filters['sort'] ?? '') === 'name' && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc'
                ])) }}"
                class="inline-flex h-[41.33px] w-full min-w-[60px] items-center justify-center gap-2 rounded-[10px] border border-black/10 bg-white px-4 font-inter text-sm font-medium text-[#0A0A0A] shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.08)] transition hover:bg-black/[0.02] "
                title="Trier par ordre alphabétique"
            >
                <span class="font-semibold">A-Z</span>
                @if(($filters['sort'] ?? '') === 'name')
                    <svg class="size-4 {{ ($filters['direction'] ?? 'asc') === 'desc' ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                @endif
            </a>
        </div>
    </div>
</section>

    {{-- Container C — Table --}}
    <section aria-label="Liste des utilisateurs" class="w-full">
        <div class="overflow-hidden rounded-[14px] border border-black/10 bg-white shadow-[0px_4px_6px_-4px_rgba(0,0,0,0.10),0px_10px_15px_-3px_rgba(0,0,0,0.10)]">
            <div class="max-h-[849px] overflow-x-auto overflow-y-auto">
                <table class="w-full min-w-[720px] border-collapse table-fixed" data-users-table>
                    <thead class="sticky top-0 z-[1] bg-[#F4F4F5]">
                        <tr class="h-12">
                            <th scope="col" class="w-[22%] px-4 text-left font-inter text-xs font-semibold uppercase tracking-wide text-[#717182]">Utilisateurs</th>
                            <th scope="col" class="w-[24%] px-4 text-left font-inter text-xs font-semibold uppercase tracking-wide text-[#717182]">Emails</th>
                            <th scope="col" class="w-[14%] px-4 text-left font-inter text-xs font-semibold uppercase tracking-wide text-[#717182]">Rôle</th>
                            <th scope="col" class="w-[18%] px-4 text-left font-inter text-xs font-semibold uppercase tracking-wide text-[#717182]">Institution</th>
                            <th scope="col" class="w-[12%] px-4 text-left font-inter text-xs font-semibold uppercase tracking-wide text-[#717182]">Status</th>
                            <th scope="col" class="w-[10%] px-4 text-right font-inter text-xs font-semibold uppercase tracking-wide text-[#717182]">Actions</th>
                        </tr>
                    </thead>
                    <tbody data-users-tbody>
                        @forelse ($users as $user)
                            @php
                                $statusTs = $user->is_active
                                    ? ($user->last_login_at ?? $user->created_at)
                                    : ($user->last_login_at ?? $user->updated_at);
                                $statusLabelFr = $statusTs ? $statusTs->locale('fr')->isoFormat('D MMM YYYY, HH:mm') : '—';
                            @endphp
                            <tr
                                data-user-row
                                data-user-id="{{ $user->id }}"
                                data-user-payload='@json($userRowPayloads[$user->id] ?? [])'
                                class="h-[108px] border-b border-black/10 bg-white last:border-b-0"
                            >
                                <td class="px-4 align-middle">
                                    <p class="font-inter text-base font-medium text-[#0A0A0A]">{{ $user->full_name }}</p>
                                </td>
                                <td class="px-4 align-middle">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[#717182]" aria-hidden="true">
                                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                                <path d="M4 6.5 12 12l8-5.5M4 18V6.5M20 18V6.5M4 18h16" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="break-all font-inter text-sm text-[#0A0A0A]">{{ $user->email }}</span>
                                    </div>
                                </td>
                                <td class="px-4 align-middle">
                                    <span class="font-inter text-sm text-[#0A0A0A]">{{ $user->roles->pluck('name')->first() ?? '—' }}</span>
                                </td>
                                <td class="px-4 align-middle">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[#717182]" aria-hidden="true">
                                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                                <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="font-inter text-sm text-[#0A0A0A]">{{ $user->institution->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 align-middle">
                                    @if ($user->is_active)
                                        <p class="font-inter text-sm font-medium text-[#008236]">Actif</p>
                                        <p class="font-inter text-xs text-[#717182]">depuis {{ $statusLabelFr }}</p>
                                    @else
                                        <p class="font-inter text-sm font-medium text-[#B91C1C]">Inactif</p>
                                        <p class="font-inter text-xs text-[#717182]">dernière activité {{ $statusLabelFr }}</p>
                                    @endif
                                </td>
                                <td class="px-4 align-middle text-right">
                                    @can('user.manage')
                                        @can('user.assign.permissions')
                                            <button
                                                type="button"
                                                data-open-edit-user
                                                class="inline-flex size-10 items-center justify-center rounded-[10px] text-[#0A0A0A] transition hover:bg-[#F4F4F5] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A]"
                                                aria-label="Modifier le rôle"
                                            >
                                                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <circle cx="6" cy="12" r="1.6" />
                                                    <circle cx="12" cy="12" r="1.6" />
                                                    <circle cx="18" cy="12" r="1.6" />
                                                </svg>
                                            </button>
                                        @endcan
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center font-inter text-sm text-[#717182]">
                                    Aucun utilisateur ne correspond à ces critères.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($users->hasPages())
            <nav class="mt-6 flex justify-center" aria-label="Pagination">
                <div class="flex flex-wrap items-center justify-center gap-3">
                    @if ($users->onFirstPage())
                        <span class="inline-flex h-10 w-[97px] cursor-not-allowed items-center justify-center rounded-[10px] border border-black/10 bg-white font-inter text-sm font-medium text-[#717182] opacity-50">
                            Précédent
                        </span>
                    @else
                        <a
                            href="{{ $users->previousPageUrl() }}"
                            class="inline-flex h-10 w-[97px] items-center justify-center rounded-[10px] border border-black/10 bg-white font-inter text-sm font-medium text-[#0A0A0A] shadow-sm transition hover:bg-black/[0.02]"
                        >
                            Précédent
                        </a>
                    @endif
                    @if (! $users->hasMorePages())
                        <span class="inline-flex h-10 w-[97px] cursor-not-allowed items-center justify-center rounded-[10px] border border-black/10 bg-white font-inter text-sm font-medium text-[#717182] opacity-50">
                            Suivant
                        </span>
                    @else
                        <a
                            href="{{ $users->nextPageUrl() }}"
                            class="inline-flex h-10 w-[97px] items-center justify-center rounded-[10px] border border-black/10 bg-white font-inter text-sm font-medium text-[#0A0A0A] shadow-sm transition hover:bg-black/[0.02]"
                        >
                            Suivant
                        </a>
                    @endif
                </div>
            </nav>
        @endif
    </section>

    {{-- Create User Modal (same gates as "Ajouter un utilisateur" so the modal exists when the button does) --}}
    @can('user.manage')
        @can('user.assign.permissions')
            <div
                id="create-user-modal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
                hidden
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-user-title"
            >
                <div data-create-user-overlay class="absolute inset-0 bg-black/50"></div>
                <div
                    data-create-user-panel
                    tabindex="-1"
                    class="relative z-10 flex max-h-[min(100dvh-2rem,743px)] w-full max-w-[672px] flex-col overflow-hidden rounded-[14px] bg-white shadow-[0px_25px_50px_-12px_rgba(0,0,0,0.35)]"
                >
                    <header class="shrink-0 border-b border-black/10 px-6 pb-4 pt-6">
                        <h2 id="create-user-title" class="font-inter text-2xl font-semibold text-[#0A0A0A]">Créer un utilisateur</h2>
                        <p class="mt-1 font-inter text-sm font-normal text-[#717182]">Ajouter un nouvel utilisateur au système</p>
                    </header>
                    <form data-create-user-form class="flex min-h-0 flex-1 flex-col overflow-hidden">
                        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                            <div
                                data-create-user-errors
                                class="mb-4 hidden rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 font-inter text-sm text-red-800"
                                role="alert"
                            ></div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label for="create-full-name" class="font-inter text-sm font-medium text-[#0A0A0A]">Nom complet</label>
                                    <input
                                        id="create-full-name"
                                        name="full_name"
                                        type="text"
                                        required
                                        class="h-[37px] w-full rounded-[10px] border border-black/10 bg-white px-3 font-inter text-sm outline-none focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A]/20"
                                    />
                                </div>
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label for="create-email" class="font-inter text-sm font-medium text-[#0A0A0A]">Email</label>
                                    <input
                                        id="create-email"
                                        name="email"
                                        type="email"
                                        required
                                        class="h-[37px] w-full rounded-[10px] border border-black/10 bg-white px-3 font-inter text-sm outline-none focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A]/20"
                                    />
                                </div>
                            </div>

                            <div class="mt-4 flex w-full max-w-[574px] flex-col gap-1.5">
                                <label for="create-institution" class="font-inter text-sm font-medium text-[#0A0A0A]">Institution</label>
                                <select
                                    id="create-institution"
                                    name="institution_id"
                                    required
                                    class="h-[37px] w-full rounded-[10px] border border-black/10 bg-white px-3 font-inter text-sm outline-none focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A]/20"
                                >
                                    <option value="">Sélectionner une institution</option>
                                    @foreach ($institutions as $inst)
                                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <input type="hidden" name="auth_type" value="sso" />

                            <div class="mt-5 w-full max-w-[607px] rounded-[10px] border border-black/10 p-4">
                                <p class="font-inter text-sm font-semibold text-[#0A0A0A]">Rôle &amp; Accès</p>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-2" data-create-role-grid>
                                    @foreach ($rolesForUi as $role)
                                        <x-user-role-card
                                            :roleName="$role->name"
                                            :inactifusers="$role->permissions->count() . ' permissions'"
                                            :description="(string) ($role->description ?? '')"
                        
                                            :roleId="$role->id"
                                            data-create-role-card
                                        />
                                    @endforeach
                                </div>
                                <div data-create-role-preview hidden class="mt-4 w-full max-w-[574px] rounded-[10px] border border-[#BEDBFF] bg-[#F8FAFF] p-4">
                                    <p class="font-inter text-sm font-medium text-[#1C398E]" data-create-role-preview-title>Permissions pour le rôle</p>
                                    <ul class="mt-2 max-h-24 space-y-1 overflow-y-auto font-inter text-sm text-[#193CB8]" data-create-role-preview-list></ul>
                                </div>
                            </div>

                            <div class="mt-6">
                                <p class="font-inter text-sm font-semibold text-[#0A0A0A]">Permissions Personnalisées</p>
                                <p class="mt-1 font-inter text-sm text-[#717182]">Ajouter des permissions spécifiques en plus du rôle</p>
                                <div class="mt-3">
                                    @include('users.partials.permission-categories', ['permissionsByCategory' => $permissionsByCategory, 'modalKey' => 'create'])
                                </div>
                            </div>
                        </div>
                        <footer class="flex shrink-0 items-center justify-end gap-3 border-t border-black/10 px-6 py-4">
                            <button
                                type="button"
                                data-close-create-user
                                class="inline-flex h-11 min-w-[106px] items-center justify-center rounded-[10px] border border-black/10 bg-white px-4 font-inter text-sm font-medium text-[#0A0A0A] transition hover:bg-black/[0.03]"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                class="inline-flex h-11 min-w-[160px] items-center justify-center rounded-[10px] bg-[#1E3A8A] px-6 font-inter text-sm font-semibold text-white transition hover:bg-[#163171] disabled:opacity-60"
                            >
                                Créer l'utilisateur
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        @endcan
    @endcan

    {{-- Edit Role Modal --}}
    @can('user.manage')
        @can('user.assign.permissions')
            <div
                id="edit-user-modal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
                hidden
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-user-title"
            >
                <div data-edit-user-overlay class="absolute inset-0 bg-black/50"></div>
                <div
                    data-edit-user-panel
                    tabindex="-1"
                    class="relative z-10 flex max-h-[min(100dvh-2rem,720px)] w-full max-w-[672px] flex-col overflow-hidden rounded-[14px] bg-white shadow-[0px_25px_50px_-12px_rgba(0,0,0,0.35)]"
                >
                    <header class="shrink-0 border-b border-black/10 px-6 pb-4 pt-6">
                        <h2 id="edit-user-title" class="font-inter text-2xl font-semibold text-[#0A0A0A]">Modifier le Rôle de l'utilisateur</h2>
                        <p class="mt-1 font-inter text-sm font-normal text-[#717182]">Ajouter ou modifier le rôle d'un utilisateur</p>
                        <p class="mt-2 font-inter text-sm font-medium text-[#1C398E]" data-edit-user-name></p>
                    </header>
                    <form data-edit-user-form class="flex min-h-0 flex-1 flex-col overflow-hidden">
                        <input type="hidden" name="user_id" data-edit-user-id value="" />
                        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                            <div
                                data-edit-user-errors
                                class="mb-4 hidden rounded-[10px] border border-red-200 bg-red-50 px-3 py-2 font-inter text-sm text-red-800"
                                role="alert"
                            ></div>

                            <div class="w-full max-w-[607px] rounded-[10px] border border-black/10 p-4">
                                <p class="font-inter text-sm font-semibold text-[#0A0A0A]">Rôle &amp; Accès</p>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-2" data-edit-role-grid>
                                    @foreach ($rolesForUi as $role)
                                        <x-user-role-card
                                            :roleName="$role->name"
                                            :inactifusers="$role->permissions->count() . ' permissions'"
                                            :description="(string) ($role->description ?? '')"
                                        
                                            :roleId="$role->id"
                                            data-edit-role-card
                                        />
                                    @endforeach
                                </div>
                                <div data-edit-role-preview hidden class="mt-4 w-full max-w-[574px] rounded-[10px] border border-[#BEDBFF] bg-[#F8FAFF] p-4">
                                    <p class="font-inter text-sm font-medium text-[#1C398E]" data-edit-role-preview-title>Permissions pour le rôle</p>
                                    <ul class="mt-2 max-h-24 space-y-1 overflow-y-auto font-inter text-sm text-[#193CB8]" data-edit-role-preview-list></ul>
                                </div>
                            </div>

                            <div class="mt-6">
                                <p class="font-inter text-sm font-semibold text-[#0A0A0A]">Permissions Personnalisées</p>
                                <p class="mt-1 font-inter text-sm text-[#717182]">Ajouter des permissions spécifiques en plus du rôle</p>
                                <div class="mt-3">
                                    @include('users.partials.permission-categories', ['permissionsByCategory' => $permissionsByCategory, 'modalKey' => 'edit'])
                                </div>
                            </div>
                        </div>
                        <footer class="flex shrink-0 items-center justify-end gap-3 border-t border-black/10 px-6 py-4">
                            <button
                                type="button"
                                data-close-edit-user
                                class="inline-flex h-11 min-w-[106px] items-center justify-center rounded-[10px] border border-black/10 bg-white px-4 font-inter text-sm font-medium text-[#0A0A0A] transition hover:bg-black/[0.03]"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                class="inline-flex h-11 min-w-[140px] items-center justify-center rounded-[10px] bg-[#1E3A8A] px-6 font-inter text-sm font-semibold text-white transition hover:bg-[#163171] disabled:opacity-60"
                            >
                                Modifier
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        @endcan
    @endcan

    <div
        data-app-toast
        class="pointer-events-none fixed bottom-6 left-1/2 z-[60] hidden w-[min(100vw-2rem,28rem)] -translate-x-1/2 rounded-[10px] border px-4 py-3 font-inter text-sm font-medium shadow-lg transition"
        role="status"
        aria-live="polite"
    ></div>
</div>

@endsection
