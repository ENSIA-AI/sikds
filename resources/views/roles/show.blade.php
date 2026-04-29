@extends('layouts.app')
@php
    $activeNav = 'roles';
    $editMode = request()->has('edit');
    $permissionsToShow = $editMode
        ? $allPermissions
        : $role->permissions->groupBy('category');
@endphp
 
@section('page_title', $role->name)
@section('page_subtitle', 'Détails du rôle et ses permissions')
 
@section('content')
 
{{-- Header Actions --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <a
        href="{{ route('roles.index') }}"
        class="inline-flex h-11 items-center justify-center gap-2 rounded-[10px] border border-black/10 bg-white px-6 text-sm font-medium text-[#0A0A0A] transition hover:bg-black/[0.02] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A]"
    >
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
            <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Retour aux rôles
    </a>
 
    <div class="flex flex-wrap items-center gap-3">
        @can('role.edit')
            <a
                href="{{ route('roles.show', ['role' => $role, 'edit' => 1]) }}"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-[10px] border border-black/10 bg-white px-6 text-sm font-medium text-[#0A0A0A] transition hover:bg-black/[0.02] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1E3A8A]"
            >
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Modifier
            </a>
        @endcan
 
        @can('role.delete')
            <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?');">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-[10px] border border-red-200 bg-red-50 px-6 text-sm font-medium text-red-700 transition hover:bg-red-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                >
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-5">
                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Supprimer
                </button>
            </form>
        @endcan
    </div>
</div>
 

@if($editMode)
<form method="POST" action="{{ route('roles.update', $role) }}">
    @csrf
    @method('PUT')
@endif
{{-- Role Info Card --}}
<div class="mb-6 rounded-[14px] border border-black/10 bg-white p-6 shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]">
    <div class="flex items-start gap-4">
        <div class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-[#DBEAFE] text-[#1E3A8A]">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="size-7">
                <path
                    d="M12 2.5l7 3.2v6.4c0 5-3 9.2-7 10.4C8 21.3 5 17.1 5 12.1V5.7l7-3.2Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linejoin="round"
                />
                <path
                    d="M9.3 12.2l1.9 1.9 3.8-4.1"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </div>
        <div class="flex-1">
            <div class="mb-2 flex items-start justify-between gap-4">
                <div>
                @if($editMode)
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $role->name) }}"
                        class="text-2xl font-bold text-[#0A0A0A] border border-black/10 rounded px-2 py-1 w-full"
                    />
                @else
                    <h1 class="text-2xl font-bold text-[#0A0A0A]">{{ $role->name }}</h1>
                @endif
                    @if($role->is_system_role)
                        <span class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                            <svg viewBox="0 0 8 8" fill="currentColor" class="size-2">
                                <circle cx="4" cy="4" r="4"/>
                            </svg>
                            Rôle système
                        </span>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-sm text-[#717182]">Permissions</p>
                    <p class="text-2xl font-semibold text-[#0A0A0A]">{{ $role->permissions->count() }}</p>
                </div>
            </div>
            @if($editMode)
                <textarea
                    name="description"
                    class="mt-3 text-sm leading-6 text-[#717182] border border-black/10 rounded px-2 py-1 w-full"
                >{{ old('description', $role->description) }}</textarea>
            @else
                @if($role->description)
                    <p class="mt-3 text-sm leading-6 text-[#717182]">{{ $role->description }}</p>
                @endif
            @endif
        </div>
    </div>
</div>
 
{{-- Permissions Section --}}
<div class="mb-6 rounded-[14px] border border-black/10 bg-white shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]">
    <div class="border-b border-black/10 px-6 py-4">
        <h2 class="text-lg font-semibold text-[#0A0A0A]">Permissions Assignées</h2>
        <p class="mt-1 text-sm text-[#717182]">Liste complète des permissions accordées à ce rôle</p>
    </div>
 
    <div class="p-6">
        @if($role->permissions->isEmpty())
            <p class="text-sm text-[#717182]">Aucune permission assignée à ce rôle.</p>
        @else
            <div class="flex flex-col gap-4">
            @foreach($permissionsToShow as $category => $permissions)
                    <div class="rounded-[10px] border border-black/10 bg-[#ECECF04D] p-4">
                        <h3 class="mb-3 text-base font-semibold text-[#0A0A0A]">{{ ucfirst($category) }}</h3>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($permissions as $permission)
                            <div class="flex items-start gap-2 rounded-lg bg-white px-3 py-2">
                                @if($editMode)
                                    <input
                                        type="checkbox"
                                        name="permission_ids[]"
                                        value="{{ $permission->id }}"
                                        class="mt-1"
                                        {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}
                                    />
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" class="mt-0.5 size-5 shrink-0 text-green-600">
                                        <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                @endif

                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-[#0A0A0A]">{{ $permission->name }}</p>
                                    <p class="text-xs text-[#717182]">{{ $permission->code }}</p>
                                </div>
                            </div>
                        @endforeach 
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
 
@if(!$editMode)
{{-- Users with this Role --}}
<div class="rounded-[14px] border border-black/10 bg-white shadow-[0px_1px_2px_-1px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10)]">
    <div class="border-b border-black/10 px-6 py-4">
        <h2 class="text-lg font-semibold text-[#0A0A0A]">Utilisateurs avec ce Rôle</h2>
        <p class="mt-1 text-sm text-[#717182]">{{ $role->users->count() }} utilisateur(s) assigné(s) à ce rôle</p>
    </div>
 
    <div class="p-6">
        @if($role->users->isEmpty())
            <p class="text-sm text-[#717182]">Aucun utilisateur n'a ce rôle pour le moment.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-black/10">
                            <th class="pb-3 text-left text-sm font-semibold text-[#0A0A0A]">Nom</th>
                            <th class="pb-3 text-left text-sm font-semibold text-[#0A0A0A]">Email</th>
                            <th class="pb-3 text-left text-sm font-semibold text-[#0A0A0A]">Institution</th>
                            <!-- <th class="pb-3 text-right text-sm font-semibold text-[#0A0A0A]">Actions</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($role->users as $user)
                            <tr class="border-b border-black/5 last:border-0">
                                <td class="py-3 text-sm text-[#0A0A0A]">{{ $user->full_name }}</td>
                                <td class="py-3 text-sm text-[#717182]">{{ $user->email }}</td>
                                <td class="py-3 text-sm text-[#717182]">{{ $user->institution->name ?? 'N/A' }}</td>
                                <td class="py-3 text-right">
                                    <!-- @can('user.view.all')
                                        <a
                                            href="{{ route('users.show', $user) }}"
                                            class="text-sm font-medium text-[#1E3A8A] hover:underline"
                                        >
                                            Voir
                                        </a>
                                    @endcan -->
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endif
@if($editMode)
    <div class="mt-6 flex justify-end">
        <button
            type="submit"
            class="inline-flex h-11 items-center justify-center gap-2 rounded-[10px] bg-[#1E3A8A] px-6 text-sm font-medium text-white hover:bg-[#1E40AF]"
        >
            Enregistrer les modifications
        </button>
    </div>
</form>
@endif 
@endsection
