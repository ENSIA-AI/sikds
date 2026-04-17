@extends('layouts.app')

@section('page_title', 'Détail du Rôle')
@section('page_subtitle', 'Consulter les permissions et les utilisateurs du rôle')

@section('content')
    <div class="max-w-6xl space-y-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('roles.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900">
                <i class="fa-solid fa-arrow-left"></i>
                Retour aux rôles
            </a>

            <div class="flex items-center gap-2">
                @if (! $role->is_system_role)
                    <a href="{{ route('roles.edit', $role) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <i class="fa-regular fa-pen-to-square mr-1"></i>
                        Modifier
                    </a>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">{{ $role->name }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $role->description ?: 'Aucune description.' }}</p>
                </div>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $role->is_system_role ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                    {{ $role->is_system_role ? 'Rôle système' : 'Rôle personnalisé' }}
                </span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Slug</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $role->slug ?: '—' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Permissions</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $role->permissions->count() }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Utilisateurs</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $role->users->count() }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ q: '', category: 'all' }">
                <h3 class="text-lg font-semibold text-slate-900">Permissions attribuées</h3>
                @if ($role->permissions->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Aucune permission attribuée à ce rôle.</p>
                @else
                    <div class="mt-4 grid grid-cols-1 gap-2 md:grid-cols-2">
                        <input
                            type="text"
                            x-model.trim="q"
                            placeholder="Filtrer par code/nom/description..."
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"
                        >
                        <select
                            x-model="category"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"
                        >
                            <option value="all">Toutes les catégories</option>
                            @foreach ($role->permissions->pluck('category')->filter()->unique()->sort()->values() as $permissionCategory)
                                <option value="{{ $permissionCategory }}">{{ ucfirst($permissionCategory) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <ul class="mt-4 space-y-2">
                        @foreach ($role->permissions as $permission)
                            <li
                                class="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-700"
                                x-show="
                                    (category === 'all' || category === @js($permission->category)) &&
                                    (
                                        q === '' ||
                                        @js(strtolower((string) ($permission->code ?? $permission->name))).includes(q.toLowerCase()) ||
                                        @js(strtolower((string) $permission->name)).includes(q.toLowerCase()) ||
                                        @js(strtolower((string) ($permission->description ?? ''))).includes(q.toLowerCase())
                                    )
                                "
                            >
                                <span class="font-medium">{{ $permission->code ?? $permission->name }}</span>
                                @if ($permission->description)
                                    <span class="block text-xs text-slate-500">{{ $permission->description }}</span>
                                @endif
                                @if ($permission->category)
                                    <span class="mt-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">
                                        {{ ucfirst($permission->category) }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Utilisateurs avec ce rôle</h3>
                @if ($role->users->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Aucun utilisateur n'utilise actuellement ce rôle.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($role->users as $user)
                            <li class="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <div class="font-medium">{{ $user->full_name ?: $user->email }}</div>
                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                <div class="text-xs text-slate-500">{{ $user->institution?->name ?? 'Institution non définie' }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
