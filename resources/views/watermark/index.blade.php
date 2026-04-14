<x-app-layout :activeNav="'traceability'">
    <x-slot name="header">
        <h1 class="sikds-page-title">Tra&ccedil;abilit&eacute; des T&eacute;l&eacute;chargements</h1>
        <p class="sikds-page-subtitle">Historique complet des t&eacute;l&eacute;chargements</p>
    </x-slot>

    {{-- ===== Stats Row ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <x-stat-card
            icon="/upload-blue.svg"
            value="{{ number_format($stats['total']) }}"
            label="Total T&eacute;l&eacute;chargements"
            trend="{{ $stats['total'] }}"
        />
        <x-stat-card
            icon="/time-blue.svg"
            value="{{ number_format($stats['today']) }}"
            label="Aujourd&apos;hui"
            trend="{{ $stats['today'] }}"
        />
        <x-stat-card
            icon="/users-blue.svg"
            value="{{ number_format($stats['unique_users']) }}"
            label="Utilisateurs Uniques"
            trend="{{ $stats['unique_users'] }}"
        />
        <x-stat-card
            icon="/building-blue.svg"
            value="{{ number_format($stats['institutions']) }}"
            label="Institutions"
            trend="{{ $stats['institutions'] }}"
        />
    </div>

    {{-- ===== Search & Filters ===== --}}
    <div class="bg-white rounded-[14px] border shadow-sm mb-5" style="border-color:rgba(0,0,0,.1);" x-data="{ filtersOpen: false }">
        <form method="GET" action="{{ route('watermark.index') }}">
            <div class="flex flex-wrap items-center gap-3 px-5 py-4">

                {{-- Text search --}}
                <div class="flex-1 min-w-48 relative">
                    <img src="/search.svg" alt="" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 opacity-40">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Rechercher par UUID, document, utilisateur..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-black/10 rounded-[10px] focus:outline-none focus:ring-2 focus:ring-[color:var(--sikds-primary)]/30">
                </div>

                {{-- Date range --}}
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <img src="/time-blue.svg" alt="" class="h-4 w-4 opacity-60">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[color:var(--sikds-primary)]/30">
                    <span class="text-sm" style="color:var(--sikds-muted)">&ndash;</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[color:var(--sikds-primary)]/30">
                </div>

                {{-- Filtres toggle --}}
                <button type="button" @click="filtersOpen = !filtersOpen"
                        :class="filtersOpen ? 'bg-[color:var(--sikds-primary)] text-white' : 'bg-white border border-black/10 hover:border-black/20'"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-[10px] transition-colors flex-shrink-0"
                        :style="filtersOpen ? '' : 'color:var(--sikds-ink)'">
                    <img src="/parameters.svg" alt="" class="h-4 w-4" :class="filtersOpen ? 'brightness-0 invert' : ''">
                    Filtres
                </button>
            </div>

            {{-- Expandable filter panel --}}
            <div x-show="filtersOpen" x-transition class="border-t px-5 py-4 grid grid-cols-3 gap-4" style="border-color:rgba(0,0,0,.1);display:none;">
                <div>
                    <label class="block text-xs font-medium mb-1" style="color:var(--sikds-muted)">Document</label>
                    <input type="text" name="document" value="{{ request('document') }}"
                           class="w-full text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none"
                           placeholder="Titre ou r&eacute;f&eacute;rence...">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color:var(--sikds-muted)">Utilisateur</label>
                    <input type="text" name="user" value="{{ request('user') }}"
                           class="w-full text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none"
                           placeholder="Nom ou email...">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color:var(--sikds-muted)">Institution</label>
                    <input type="text" name="institution" value="{{ request('institution') }}"
                           class="w-full text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none"
                           placeholder="Nom de l&apos;institution...">
                </div>
                <div class="col-span-3 flex justify-end pt-1">
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-[10px]"
                            style="background-color:var(--sikds-primary);">
                        Rechercher
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ===== Table ===== --}}
    <div class="bg-white rounded-[14px] border overflow-hidden" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:rgba(0,0,0,.1);background:#f9f9fb;">
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">UUID Filigrane</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Document</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Utilisateur</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Institution</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Date &amp; Heure</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Adresse IP</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $year      = $log->downloaded_at?->format('Y') ?? date('Y');
                            $shortUuid = strtoupper(substr(str_replace('-', '', $log->watermark_uuid), 0, 8));
                            $wmCode    = 'WM-' . $year . '-' . $shortUuid;
                        @endphp
                        <tr class="border-b hover:bg-[#f9f9fb] transition-colors" style="border-color:rgba(0,0,0,.06);">

                            {{-- UUID --}}
                            <td class="px-5 py-4">
                                <a href="{{ route('watermark.show', $log->watermark_uuid) }}" class="flex items-center gap-1.5 group">
                                    <span class="text-base leading-none" style="color:#a855f7">&#9733;</span>
                                    <span class="font-mono text-xs font-semibold group-hover:underline" style="color:#a855f7">{{ $wmCode }}</span>
                                </a>
                            </td>

                            {{-- Document --}}
                            <td class="px-5 py-4">
                                <p class="font-semibold text-sm truncate max-w-[180px]" style="color:var(--sikds-ink)">{{ $log->document?->title ?? '&mdash;' }}</p>
                                <p class="text-xs mt-0.5" style="color:var(--sikds-muted)">{{ $log->document?->reference_number }}</p>
                            </td>

                            {{-- User --}}
                            <td class="px-5 py-4">
                                <p class="font-semibold text-sm" style="color:var(--sikds-ink)">{{ $log->user?->full_name ?? $log->user?->username }}</p>
                                <p class="text-xs mt-0.5" style="color:var(--sikds-muted)">{{ $log->user?->email }}</p>
                            </td>

                            {{-- Institution --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1.5">
                                    <img src="/lock-blue.svg" alt="" class="h-3.5 w-3.5 opacity-50 flex-shrink-0">
                                    <span class="text-sm" style="color:var(--sikds-ink)">{{ $log->user?->institution?->name ?? '&mdash;' }}</span>
                                </div>
                            </td>

                            {{-- Date & Heure --}}
                            <td class="px-5 py-4">
                                <p class="text-sm" style="color:var(--sikds-ink)">{{ $log->downloaded_at?->format('Y-m-d') }}</p>
                                <p class="text-xs mt-0.5" style="color:var(--sikds-muted)">{{ $log->downloaded_at?->format('H:i:s') }}</p>
                            </td>

                            {{-- IP --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1.5">
                                    <img src="/traceability.svg" alt="" class="h-3.5 w-3.5 opacity-40 flex-shrink-0">
                                    <span class="font-mono text-xs" style="color:var(--sikds-ink)">{{ $log->ip_address }}</span>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('watermark.show', $log->watermark_uuid) }}"
                                       class="h-8 w-8 rounded-[8px] flex items-center justify-center transition-colors hover:bg-gray-100"
                                       title="Voir le d&eacute;tail"
                                       style="border:1px solid rgba(0,0,0,.1);">
                                        <img src="/audit-blue.svg" alt="Voir" class="h-4 w-4">
                                    </a>
                                    <a href="{{ route('watermark.show', $log->watermark_uuid) }}"
                                       class="h-8 w-8 rounded-[8px] flex items-center justify-center transition-colors hover:bg-gray-100"
                                       title="Ouvrir"
                                       target="_blank"
                                       style="border:1px solid rgba(0,0,0,.1);">
                                        <img src="/distribution.svg" alt="Ouvrir" class="h-4 w-4 opacity-60">
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center text-sm" style="color:var(--sikds-muted)">
                                Aucun t&eacute;l&eacute;chargement trouv&eacute;.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-4 border-t flex items-center justify-between" style="border-color:rgba(0,0,0,.1);">
            <p class="text-sm" style="color:var(--sikds-muted)">
                Affichage de {{ $logs->firstItem() ?? 0 }}&ndash;{{ $logs->lastItem() ?? 0 }} sur {{ number_format($logs->total()) }} t&eacute;l&eacute;chargements
            </p>
            <div class="flex items-center gap-2">
                @if ($logs->onFirstPage())
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Pr&eacute;c&eacute;dent</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Pr&eacute;c&eacute;dent</a>
                @endif
                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Suivant</a>
                @else
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Suivant</span>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
