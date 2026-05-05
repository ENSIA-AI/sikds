@extends('layouts.app')

@section('page_title', 'Modifier un Rôle')
@section('page_subtitle', 'Mettre à jour les informations et permissions du rôle')

@section('content')
    <div class="max-w-5xl space-y-6">
        <div class="flex items-center justify-between">
            <x-back-link :href="route('roles.show', $role)" label="Retour au rôle" />
        </div>

        <form action="{{ route('roles.update', $role) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Informations du rôle</h2>
                <div class="mt-4 grid grid-cols-1 gap-4">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">
                            Nom du rôle <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            maxlength="255"
                            value="{{ old('name', $role->name) }}"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-500 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-500 focus:outline-none"
                        >{{ old('description', $role->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">
                    Permissions <span class="text-red-600">*</span>
                </h2>
                <p class="mt-1 text-sm text-slate-500">Sélectionnez au moins une permission.</p>

                @php
                    $selectedPermissionIds = collect(old('permission_ids', $role->permissions->pluck('id')->all()))
                        ->map(fn ($id) => (int) $id)
                        ->all();
                @endphp

                <div class="mt-4 space-y-4">
                    @forelse ($permissions as $category => $categoryPermissions)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">
                                {{ ucfirst((string) $category) }}
                            </h3>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                @foreach ($categoryPermissions as $permission)
                                    <label class="inline-flex items-start gap-2 rounded-md px-2 py-1 text-sm text-slate-700 hover:bg-slate-100">
                                        <input
                                            type="checkbox"
                                            name="permission_ids[]"
                                            value="{{ $permission->id }}"
                                            @checked(in_array((int) $permission->id, $selectedPermissionIds, true))
                                        >
                                        <span>
                                            <span class="font-medium">{{ $permission->code ?? $permission->name }}</span>
                                            @if ($permission->description)
                                                <span class="block text-xs text-slate-500">{{ $permission->description }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucune permission disponible.</p>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('roles.show', $role) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Annuler
                </a>
                <button type="submit" class="rounded-lg bg-[#1E3A8A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#163171]">
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
@endsection
