@extends('layouts.app')
@section('page_title', __('Journaux d’Audit'))
@section('page_subtitle', __('Traçabilité complète des actions système et sécurité'))
@section('content')
    <script type="application/json" id="audit-selected-events">@json(is_array(request('event_type')) ? request('event_type') : (request('event_type') ? [request('event_type')] : []))</script>
    <script type="application/json" id="audit-selected-statuses">@json(is_array(request('result')) ? request('result') : (request('result') ? [request('result')] : []))</script>
    @php
        $resultLabels = [
            'success' => __('Succès'),
            'warning' => __('Avertissement'),
            'failed' => __('Échec'),
        ];
        $eventTypeLabels = [
            'document.uploaded' => __('Téléversement de document'),
            'document.updated' => __('Modification de document'),
            'document.published' => __('Publication de document'),
            'document.archived' => __('Archivage de document'),
            'document.soft_deleted' => __('Suppression logique de document'),
            'document.restored' => __('Restauration de document'),
            'document.download' => __('Téléchargement de document'),
            'document.forwarded' => __('Document transféré'),
            'auth.login.success' => __('Connexion réussie'),
            'auth.login.failed' => __('Tentative de connexion échouée'),
            'auth.logout' => __('Déconnexion'),
            'user.created' => __('Création utilisateur'),
            'user.updated' => __('Modification utilisateur'),
            'user.deactivated' => __('Désactivation utilisateur'),
            'user.activated' => __('Activation utilisateur'),
            'role.assigned' => __('Attribution de rôle'),
            'role.removed' => __('Retrait de rôle'),
            'role.created' => __('Création de rôle'),
            'role.updated' => __('Modification de rôle'),
            'role.deleted' => __('Suppression de rôle'),
            'role.permissions.changed' => __('Permissions du rôle modifiées'),
            'permission.assigned' => __('Permission attribuée'),
            'permission.removed' => __('Permission retirée'),
            'permissions.changed' => __('Permissions modifiées'),
            'permission.updated' => __('Modification de permissions'),
            'tag.created' => __('Tag créé'),
            'tag.updated' => __('Tag modifié'),
            'tag.deleted' => __('Tag supprimé'),
            'tag.assigned' => __('Tag attribué'),
            'tag.removed' => __('Tag retiré'),
            'settings.updated' => __('Paramètres mis à jour'),
            'indexing.started' => __('Démarrage indexation'),
            'indexing.completed' => __('Indexation terminée'),
            'indexing.failed' => __('Échec indexation'),
            'DOCUMENT_INDEXING_STARTED' => __('Démarrage indexation'),
            'DOCUMENT_INDEXING_COMPLETED' => __('Indexation terminée'),
            'DOCUMENT_INDEXING_FAILED' => __('Échec indexation'),
            'QUEUE_JOB_FAILED' => __("Échec d'un travail en file"),
            'QUEUE_LONG_WAIT_DETECTED' => __('Attente prolongée détectée'),
            'notification.sent' => __('Notification envoyée'),
            'notification.failed' => __('Échec notification'),
            // Defensive aliases for shorter event keys that may appear via legacy or future emitters.
            'login' => __('Connexion'),
            'logout' => __('Déconnexion'),
            'upload' => __('Téléversement'),
            'download' => __('Téléchargement'),
            'create' => __('Création'),
            'delete' => __('Suppression'),
            'update' => __('Mise à jour'),
            'view' => __('Consultation'),
            'document.upload' => __('Téléversement'),
            'document.create' => __('Création'),
            'document.delete' => __('Suppression'),
            'document.update' => __('Mise à jour'),
            'document.view' => __('Consultation'),
            'user.create' => __("Création d'utilisateur"),
            'user.update' => __("Modification d'utilisateur"),
        ];
        $filterLabelMap = [
            'date' => __('Date'),
            'event_type' => __("Type d'événement"),
            'user' => __('Utilisateur'),
            'result' => __('Statut'),
        ];
        $statusLabels = [
            'draft' => __('Brouillon'),
            'active' => __('Actif'),
            'archived' => __('Archivé'),
            'soft_deleted' => __('Supprimé'),
        ];
        $canViewAuditTechnicalDetails = auth()->user()?->can('audit.view') ?? false;
        $contextSummary = static function (string $event, array $meta) use ($statusLabels, $eventTypeLabels): array {
            $lines = [];

            $ref = isset($meta['reference_number']) && is_string($meta['reference_number']) ? $meta['reference_number'] : null;
            if ($ref) {
                $lines[] = __('Référence: :ref', ['ref' => $ref]);
            }

            if (isset($meta['watermark_uuid']) && is_string($meta['watermark_uuid'])) {
                $lines[] = __('ID filigrane: :uuid', ['uuid' => $meta['watermark_uuid']]);
            }

            if (isset($meta['previous_status']) && is_string($meta['previous_status'])) {
                $before = $statusLabels[$meta['previous_status']] ?? $meta['previous_status'];
                $afterRaw = is_string($meta['status_after'] ?? null) ? $meta['status_after'] : null;
                $after = $afterRaw ? ($statusLabels[$afterRaw] ?? $afterRaw) : null;
                $lines[] = $after
                    ? __('Statut: :before → :after', ['before' => $before, 'after' => $after])
                    : __('Statut précédent: :before', ['before' => $before]);
            }

            if (isset($meta['status_before']) && is_string($meta['status_before'])) {
                $before = $statusLabels[$meta['status_before']] ?? $meta['status_before'];
                $afterRaw = is_string($meta['status_after'] ?? null) ? $meta['status_after'] : null;
                $after = $afterRaw ? ($statusLabels[$afterRaw] ?? $afterRaw) : null;
                if ($after) {
                    $lines[] = __('Statut: :before → :after', ['before' => $before, 'after' => $after]);
                }
            }

            if (isset($meta['new_version']) && (is_int($meta['new_version']) || is_string($meta['new_version']))) {
                $lines[] = __('Nouvelle version: v:n', ['n' => $meta['new_version']]);
            }

            if (isset($meta['missing_embeddings']) && (is_int($meta['missing_embeddings']) || is_string($meta['missing_embeddings']))) {
                $lines[] = __('Embeddings manquants: :n', ['n' => $meta['missing_embeddings']]);
            }

            if ($event === 'QUEUE_JOB_FAILED') {
                if (isset($meta['queue']) && is_string($meta['queue'])) $lines[] = __('File: :queue', ['queue' => $meta['queue']]);
                if (isset($meta['job_name']) && is_string($meta['job_name'])) $lines[] = __('Job: :job', ['job' => $meta['job_name']]);
                if (isset($meta['exception']) && is_string($meta['exception'])) $lines[] = __('Erreur: :message', ['message' => $meta['exception']]);
            }

            if ($event === 'QUEUE_LONG_WAIT_DETECTED') {
                if (isset($meta['queue']) && is_string($meta['queue'])) $lines[] = __('File: :queue', ['queue' => $meta['queue']]);
                if (isset($meta['wait_seconds']) && (is_int($meta['wait_seconds']) || is_string($meta['wait_seconds']))) $lines[] = __('Attente: :seconds s', ['seconds' => $meta['wait_seconds']]);
            }

            if ($event === 'auth.login.failed') {
                $lines[] = __('Tentative de connexion refusée');
            }

            if ($lines === []) {
                $label = __($eventTypeLabels[$event] ?? $event);
                $lines[] = __('Événement: :label', ['label' => $label]);
            }

            return $lines;
        };
        $nonEmptyFilters = collect(request()->query())
            ->except('page')
            ->filter(fn ($value) => $value !== null && $value !== '');
    @endphp

        <div x-data="auditFilters()" class="bg-white rounded-[14px] border shadow-sm mb-5" style="border-color:rgba(0,0,0,.1);">
        <form method="GET" action="{{ route('audits.index') }}" id="audit-filter-form" x-ref="filterForm">
            <div class="px-5 pt-4 pb-3 flex items-center justify-end">
                <button type="button" id="open-export-modal"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border border-black/10 rounded-[12px] hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-download text-xs"></i>
                    {{ __('Exporter les Logs') }}
                </button>
            </div>

            <div class="px-5 pb-4 grid grid-cols-1 lg:grid-cols-12 gap-3">
                {{-- Date filter --}}
                <div class="lg:col-span-3">
                    <input type="date" name="date" value="{{ request('date') }}"
                           class="h-10 w-full text-sm border border-[#e5e7eb] rounded-[8px] px-3 bg-white focus:outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20 transition">
                </div>

                {{-- Event Type multi-select --}}
                <div class="lg:col-span-3 relative" @click.outside="eventOpen = false">
                    @php
                        $selectedEventTypes = is_array(request('event_type')) ? request('event_type') : (request('event_type') ? [request('event_type')] : []);
                    @endphp
                    <button type="button" @click="eventOpen = !eventOpen"
                            class="h-10 w-full flex items-center gap-2 text-sm border border-[#e5e7eb] rounded-[8px] px-3 bg-white text-start focus:outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20 transition"
                            :class="{ 'border-[#1c398e] ring-2 ring-[#1c398e]/20': eventOpen }">
                        <i class="fa-solid fa-filter text-xs shrink-0" style="color:var(--sikds-muted)"></i>
                        <span class="flex-1 truncate" x-text="selectedEvents.length ? selectedEvents.length + ' ' + @js(__('type(s)')) : @js(__('Type d\'événement'))" :class="selectedEvents.length ? 'text-[#0a0a0a] font-medium' : 'text-[#717182]'"></span>
                        <i class="fa-solid fa-chevron-down text-[10px] shrink-0 transition-transform" :class="{ 'rotate-180': eventOpen }" style="color:var(--sikds-muted)"></i>
                    </button>
                    @if (count($selectedEventTypes) > 0)
                        <span class="absolute -top-1.5 -end-1.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[11px] font-semibold text-white" style="background-color:var(--sikds-primary)">{{ count($selectedEventTypes) }}</span>
                    @endif
                    <div x-show="eventOpen" x-transition.origin.top
                         class="absolute left-0 right-0 top-full z-50 mt-1.5 max-h-64 overflow-y-auto rounded-[12px] border border-black/10 bg-white py-1 shadow-[0_8px_30px_-4px_rgba(0,0,0,0.15)]" x-cloak>
                        @foreach ($eventTypes as $et)
                            <label class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-[#f1f5f9] transition-colors">
                                <input type="checkbox" name="event_type[]" value="{{ $et }}"
                                       {{ in_array($et, $selectedEventTypes, true) ? 'checked' : '' }}
                                       x-model="selectedEvents"
                                       class="h-4 w-4 rounded border-black/20 text-[#1c398e] focus:ring-[#1c398e]/30">
                                <span>{{ __($eventTypeLabels[$et] ?? $et) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- User filter --}}
                <div class="lg:col-span-3">
                    <input type="text" name="user" value="{{ request('user', request('actor')) }}"
                           class="h-10 w-full text-sm border border-[#e5e7eb] rounded-[8px] px-3 bg-white focus:outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20 transition"
                           placeholder="{{ __('Filtrer par utilisateur...') }}">
                </div>

                {{-- Status multi-select --}}
                <div class="lg:col-span-3 relative" @click.outside="statusOpen = false">
                    @php
                        $selectedResults = is_array(request('result')) ? request('result') : (request('result') ? [request('result')] : []);
                    @endphp
                    <button type="button" @click="statusOpen = !statusOpen"
                            class="h-10 w-full flex items-center gap-2 text-sm border border-[#e5e7eb] rounded-[8px] px-3 bg-white text-start focus:outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20 transition"
                            :class="{ 'border-[#1c398e] ring-2 ring-[#1c398e]/20': statusOpen }">
                        <span class="flex-1 truncate" x-text="selectedStatuses.length ? selectedStatuses.length + ' ' + @js(__('statut(s)')) : @js(__('Tous les statuts'))" :class="selectedStatuses.length ? 'text-[#0a0a0a] font-medium' : 'text-[#717182]'"></span>
                        <i class="fa-solid fa-chevron-down text-[10px] shrink-0 transition-transform" :class="{ 'rotate-180': statusOpen }" style="color:var(--sikds-muted)"></i>
                    </button>
                    <div x-show="statusOpen" x-transition.origin.top
                         class="absolute left-0 right-0 top-full z-50 mt-1.5 rounded-[12px] border border-black/10 bg-white py-1 shadow-[0_8px_30px_-4px_rgba(0,0,0,0.15)]" x-cloak>
                        @foreach ($resultLabels as $key => $label)
                            <label class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-[#f1f5f9] transition-colors">
                                <input type="checkbox" name="result[]" value="{{ $key }}"
                                       {{ in_array($key, $selectedResults, true) ? 'checked' : '' }}
                                       x-model="selectedStatuses"
                                       class="h-4 w-4 rounded border-black/20 text-[#1c398e] focus:ring-[#1c398e]/30">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Active filter chips --}}
            <div class="px-5 pb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2 text-sm" style="color:var(--sikds-muted)">
                    <span>{{ __('Filtres actifs:') }}</span>
                    @if ($nonEmptyFilters->isEmpty())
                        <span class="inline-flex items-center rounded-full bg-[#f1f5f9] px-2.5 py-1 text-xs text-[#717182]">{{ __('Aucun') }}</span>
                    @else
                        @foreach ($nonEmptyFilters as $key => $value)
                            @php
                                if (is_array($value)) {
                                    $displayParts = [];
                                    foreach ($value as $v) {
                                        if ($key === 'event_type') {
                                            $displayParts[] = __($eventTypeLabels[$v] ?? $v);
                                        } elseif ($key === 'result') {
                                            $displayParts[] = $resultLabels[$v] ?? $v;
                                        } else {
                                            $displayParts[] = $v;
                                        }
                                    }
                                    $displayValue = implode(', ', $displayParts);
                                } else {
                                    $displayValue = $value;
                                    if ($key === 'event_type' && is_string($value)) {
                                        $displayValue = __($eventTypeLabels[$value] ?? $value);
                                    }
                                    if ($key === 'result' && is_string($value)) {
                                        $displayValue = $resultLabels[$value] ?? $value;
                                    }
                                }
                                $displayKey = $filterLabelMap[$key] ?? str($key)->replace('_', ' ')->title()->toString();
                            @endphp
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#eef2ff] px-2.5 py-1 text-xs font-medium text-[#1c398e]">
                                <i class="fa-solid fa-circle text-[4px]"></i>
                                {{ $displayKey }}: {{ $displayValue }}
                            </span>
                        @endforeach
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('audits.index') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm border border-black/10 rounded-[10px] hover:bg-gray-50 transition-colors" style="color:var(--sikds-ink)">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i>
                        {{ __('Réinitialiser') }}
                    </a>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-semibold text-white rounded-[10px] transition hover:opacity-90" style="background-color:var(--sikds-primary);">
                        <i class="fa-solid fa-check text-[10px]"></i>
                        {{ __('Appliquer') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-[14px] border overflow-hidden" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:rgba(0,0,0,.1);background:#f9f9fb;">
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Type') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Utilisateur') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Action') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Cible') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Horodatage') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Statut') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $event = (string) $log->event_type;
                            $type = match (true) {
                                str_contains($event, 'upload') => ['icon' => 'fa-solid fa-upload', 'color' => '#2563eb'],
                                str_contains($event, 'download') => ['icon' => 'fa-solid fa-download', 'color' => '#16a34a'],
                                str_contains($event, 'update') || str_contains($event, 'edit') => ['icon' => 'fa-solid fa-pen', 'color' => '#ea580c'],
                                str_contains($event, 'login') || str_contains($event, 'auth') => ['icon' => 'fa-solid fa-user-check', 'color' => '#7c3aed'],
                                str_contains($event, 'delete') => ['icon' => 'fa-solid fa-trash', 'color' => '#dc2626'],
                                str_contains($event, 'role') || str_contains($event, 'permission') => ['icon' => 'fa-solid fa-shield', 'color' => '#4f46e5'],
                                default => ['icon' => 'fa-solid fa-circle-info', 'color' => '#0f172a'],
                            };
                            $actionLabel = __($eventTypeLabels[$event] ?? $event);
                            $targetLabel = trim(($log->resource_type ?? 'N/A') . (($log->resource_id !== null) ? ' #' . $log->resource_id : ''));
                            $badgeClass = match($log->result) {
                                'failed' => 'sikds-status--deleted',
                                'warning' => 'sikds-status--archived',
                                default => 'sikds-status--active',
                            };
                        @endphp
                        <tr class="border-b hover:bg-[#f9f9fb] transition-colors" style="border-color:rgba(0,0,0,.06);">
                            <td class="px-4 py-3">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full" style="background-color: color-mix(in srgb, {{ $type['color'] }} 12%, white); color: {{ $type['color'] }};">
                                    <i class="{{ $type['icon'] }} text-xs"></i>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-sm" style="color:var(--sikds-ink)">{{ $log->user?->full_name ?? __('Utilisateur Inconnu') }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ $log->user_email ?? $log->user?->email ?? __('N/A') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ $actionLabel }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ __('Événement audité') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ $targetLabel }}</p>
                                <details>
                                    <summary class="cursor-pointer text-xs font-medium" style="color:var(--sikds-primary)">{{ __('Contexte') }}</summary>
                                    <div class="mt-2 space-y-2">
                                        @php($meta = is_array($log->metadata) ? $log->metadata : [])
                                        <ul class="text-xs space-y-1" style="color:var(--sikds-muted)">
                                            @foreach ($contextSummary($event, $meta) as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                            <li>{{ __('Appareil') }}: {{ $log->user_agent ? __('Navigateur web') : __('Système') }}</li>
                                        </ul>

                                        @if ($canViewAuditTechnicalDetails)
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs font-medium" style="color:var(--sikds-primary)">{{ __('Voir JSON') }}</summary>
                                                <pre class="mt-2 text-xs bg-gray-50 border border-black/10 rounded-[8px] p-2 overflow-auto max-h-28">{{ json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                                            </details>
                                        @endif
                                    </div>
                                </details>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ $log->created_at?->format('Y-m-d H:i:s') }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ $log->ip_address ?? __('N/A') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="sikds-status {{ $badgeClass }}">{{ $resultLabels[$log->result] ?? __($log->result ?? 'success') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-sm" style="color:var(--sikds-muted)">
                                {{ __('Aucun événement d\'audit trouvé.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t flex items-center justify-between" style="border-color:rgba(0,0,0,.1);">
            <p class="text-sm" style="color:var(--sikds-muted)">
                {{ __('Affichage de') }} {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} {{ __('sur') }} {{ number_format($logs->total()) }} {{ __('événements') }}
            </p>
            <div class="flex items-center gap-2">
                @if ($logs->onFirstPage())
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">{{ __('Précédent') }}</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">{{ __('Précédent') }}</a>
                @endif
                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">{{ __('Suivant') }}</a>
                @else
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">{{ __('Suivant') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div id="export-audit-modal" data-modal-portal class="sikds-modal-backdrop hidden">
        <div class="absolute inset-0" id="export-modal-overlay"></div>
        <div class="relative w-full max-w-xl rounded-2xl bg-white border border-black/10 shadow-2xl" style="font-family:'Inter', ui-sans-serif, sans-serif;">
            <div class="flex items-start justify-between px-6 py-5 border-b border-black/10">
                <div>
                    <h3 class="text-[22px] leading-7 font-semibold" style="color:var(--sikds-ink)">{{ __('Exporter les Journaux') }}</h3>
                    <p class="mt-1 text-[14px]" style="color:var(--sikds-muted)">{{ __('Télécharger l\'historique d\'audit') }}</p>
                </div>
                <button type="button" id="close-export-modal" class="text-xl leading-none text-gray-500 hover:text-gray-700"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>

            <form method="GET" action="{{ route('audits.export') }}" class="px-6 py-5 space-y-5">
                @foreach(request()->query() as $k => $v)
                    @if($k !== 'page')
                        <input type="hidden" name="{{ $k }}" value="{{ is_array($v) ? json_encode($v) : $v }}">
                    @endif
                @endforeach

                <div>
                    <label class="block text-[15px] font-semibold mb-2">{{ __('Format d\'Export') }} <span class="text-red-500">*</span></label>
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 rounded-xl border border-black/10 p-3 cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="format" value="csv" checked class="mt-1">
                            <div>
                                <p class="font-semibold text-[14px] flex items-center gap-2">
                                    <img src="/csv_green.svg" alt="" class="h-4 w-4">
                                    CSV
                                </p>
                                <p class="text-[12px]" style="color:var(--sikds-muted)">{{ __('Fichier tableur compatible Excel') }}</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 rounded-xl border border-black/10 p-3 cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="format" value="json" class="mt-1">
                            <div>
                                <p class="font-semibold text-[14px] flex items-center gap-2">
                                    <img src="/csv_blue.svg" alt="" class="h-4 w-4">
                                    JSON
                                </p>
                                <p class="text-[12px]" style="color:var(--sikds-muted)">{{ __('Format structuré pour analyse') }}</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 rounded-xl border border-black/10 p-3 opacity-60 cursor-not-allowed">
                            <input type="radio" disabled class="mt-1">
                            <div>
                                <p class="font-semibold text-[14px] flex items-center gap-2">
                                    <img src="/csv_red.svg" alt="" class="h-4 w-4">
                                    PDF
                                </p>
                                <p class="text-[12px]" style="color:var(--sikds-muted)">{{ __('Rapport imprimable (bientôt disponible)') }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-[15px] font-semibold mb-2">{{ __('Période') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="date" name="export_date_from" class="text-[13px] border border-black/10 rounded-[10px] px-3 py-2">
                        <input type="date" name="export_date_to" class="text-[13px] border border-black/10 rounded-[10px] px-3 py-2">
                    </div>
                </div>

                <div class="rounded-xl px-3 py-2 text-[12px]" style="background:#eef4ff;color:#3859d0;">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    {{ __('L\'export inclura tous les événements selon les filtres actifs.') }}
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" id="cancel-export-modal" class="px-5 py-2 text-sm border border-black/10 rounded-[10px] hover:bg-gray-50">{{ __('Annuler') }}</button>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white rounded-[10px]" style="background-color:var(--sikds-primary);">
                        <i class="fa-solid fa-download me-1"></i> {{ __('Exporter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/pages/audits.js'])
    @endpush
@endsection