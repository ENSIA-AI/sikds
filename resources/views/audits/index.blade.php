@extends('layouts.app')
@section('page_title', 'Journaux d’Audit')
@section('page_subtitle', 'Traçabilité complète des actions système et sécurité')
@section('content')
    @php
        $resultLabels = ['success' => 'Succès', 'warning' => 'Avertissement', 'failed' => 'Échec'];
        $eventTypeLabels = [
            'document.uploaded' => 'Téléversement de document',
            'document.updated' => 'Modification de document',
            'document.published' => 'Publication de document',
            'document.archived' => 'Archivage de document',
            'document.soft_deleted' => 'Suppression logique de document',
            'document.restored' => 'Restauration de document',
            'document.download' => 'Téléchargement de document',
            'auth.login.success' => 'Connexion réussie',
            'auth.login.failed' => 'Tentative de connexion échouée',
            'auth.logout' => 'Déconnexion',
            'user.created' => 'Création utilisateur',
            'user.updated' => 'Modification utilisateur',
            'user.deactivated' => 'Désactivation utilisateur',
            'user.activated' => 'Activation utilisateur',
            'role.assigned' => 'Attribution de rôle',
            'role.removed' => 'Retrait de rôle',
            'role.created' => 'Création de rôle',
            'role.updated' => 'Modification de rôle',
            'role.deleted' => 'Suppression de rôle',
            'role.permissions.changed' => 'Permissions du rôle modifiées',
            'permission.assigned' => 'Permission attribuée',
            'permission.removed' => 'Permission retirée',
            'permissions.changed' => 'Permissions modifiées',
            'permission.updated' => 'Modification de permissions',
            'tag.created' => 'Tag créé',
            'tag.updated' => 'Tag modifié',
            'tag.deleted' => 'Tag supprimé',
            'tag.assigned' => 'Tag attribué',
            'tag.removed' => 'Tag retiré',
            'settings.updated' => 'Paramètres mis à jour',
            'indexing.started' => 'Démarrage indexation',
            'indexing.completed' => 'Indexation terminée',
            'indexing.failed' => 'Échec indexation',
            'notification.sent' => 'Notification envoyée',
            'notification.failed' => 'Échec notification',
        ];
        $fallbackEventLabel = static function (string $event): string {
            return str($event)
                ->replace('.', ' ')
                ->replace('_', ' ')
                ->replace('document', 'document')
                ->replace('uploaded', 'téléversement')
                ->replace('updated', 'modification')
                ->replace('published', 'publication')
                ->replace('archived', 'archivage')
                ->replace('deleted', 'suppression')
                ->replace('restored', 'restauration')
                ->replace('download', 'téléchargement')
                ->replace('auth', 'authentification')
                ->replace('login', 'connexion')
                ->replace('logout', 'déconnexion')
                ->replace('failed', 'échouée')
                ->replace('success', 'réussie')
                ->title()
                ->toString();
        };
        $filterLabelMap = [
            'date' => 'Date',
            'event_type' => "Type d'événement",
            'user' => 'Utilisateur',
            'result' => 'Statut',
        ];
        $statusLabels = [
            'draft' => 'Brouillon',
            'active' => 'Actif',
            'archived' => 'Archivé',
            'soft_deleted' => 'Supprimé',
        ];
        $isSuperAdmin = auth()->user()?->hasRole('Super Administrateur') ?? false;
        $contextSummary = static function (string $event, array $meta) use ($statusLabels, $eventTypeLabels, $fallbackEventLabel): array {
            $lines = [];

            $ref = isset($meta['reference_number']) && is_string($meta['reference_number']) ? $meta['reference_number'] : null;
            if ($ref) {
                $lines[] = "Référence: {$ref}";
            }

            if (isset($meta['watermark_uuid']) && is_string($meta['watermark_uuid'])) {
                $lines[] = "ID filigrane: {$meta['watermark_uuid']}";
            }

            if (isset($meta['previous_status']) && is_string($meta['previous_status'])) {
                $before = $statusLabels[$meta['previous_status']] ?? $meta['previous_status'];
                $afterRaw = is_string($meta['status_after'] ?? null) ? $meta['status_after'] : null;
                $after = $afterRaw ? ($statusLabels[$afterRaw] ?? $afterRaw) : null;
                $lines[] = $after ? "Statut: {$before} → {$after}" : "Statut précédent: {$before}";
            }

            if (isset($meta['status_before']) && is_string($meta['status_before'])) {
                $before = $statusLabels[$meta['status_before']] ?? $meta['status_before'];
                $afterRaw = is_string($meta['status_after'] ?? null) ? $meta['status_after'] : null;
                $after = $afterRaw ? ($statusLabels[$afterRaw] ?? $afterRaw) : null;
                if ($after) {
                    $lines[] = "Statut: {$before} → {$after}";
                }
            }

            if (isset($meta['new_version']) && (is_int($meta['new_version']) || is_string($meta['new_version']))) {
                $lines[] = 'Nouvelle version: v' . $meta['new_version'];
            }

            if (isset($meta['missing_embeddings']) && (is_int($meta['missing_embeddings']) || is_string($meta['missing_embeddings']))) {
                $lines[] = 'Embeddings manquants: ' . $meta['missing_embeddings'];
            }

            if ($event === 'QUEUE_JOB_FAILED') {
                if (isset($meta['queue']) && is_string($meta['queue'])) $lines[] = 'File: ' . $meta['queue'];
                if (isset($meta['job_name']) && is_string($meta['job_name'])) $lines[] = 'Job: ' . $meta['job_name'];
                if (isset($meta['exception']) && is_string($meta['exception'])) $lines[] = 'Erreur: ' . $meta['exception'];
            }

            if ($event === 'QUEUE_LONG_WAIT_DETECTED') {
                if (isset($meta['queue']) && is_string($meta['queue'])) $lines[] = 'File: ' . $meta['queue'];
                if (isset($meta['wait_seconds']) && (is_int($meta['wait_seconds']) || is_string($meta['wait_seconds']))) $lines[] = 'Attente: ' . $meta['wait_seconds'] . 's';
            }

            if ($event === 'auth.login.failed') {
                $lines[] = 'Tentative de connexion refusée';
            }

            if ($lines === []) {
                $label = $eventTypeLabels[$event] ?? $fallbackEventLabel($event);
                $lines[] = "Événement: {$label}";
            }

            return $lines;
        };
        $nonEmptyFilters = collect(request()->query())
            ->except('page')
            ->filter(fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="bg-white rounded-[14px] border shadow-sm mb-5" style="border-color:rgba(0,0,0,.1);">
        <form method="GET" action="{{ route('audits.index') }}" id="audit-filter-form">
            <div class="px-5 pt-4 pb-3 flex items-center justify-end">
                <button type="button" id="open-export-modal"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border border-black/10 rounded-[12px] hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-download text-xs"></i>
                    Exporter les Logs
                </button>
            </div>

            <div class="px-5 pb-4 grid grid-cols-1 lg:grid-cols-12 gap-3">
                <input type="date" name="date" value="{{ request('date') }}"
                       class="lg:col-span-3 text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[color:var(--sikds-primary)]/30">

                <div class="lg:col-span-3 flex items-center rounded-[10px] bg-[#060b2b] px-3 py-2 text-white">
                    <i class="fa-solid fa-filter text-xs mr-2"></i>
                    <select name="event_type" class="w-full bg-transparent text-sm focus:outline-none">
                        <option value="" class="text-black">Type d'événement</option>
                        @foreach ($eventTypes as $eventType)
                            <option value="{{ $eventType }}" class="text-black" @selected(request('event_type') === $eventType)>{{ $eventTypeLabels[$eventType] ?? $fallbackEventLabel($eventType) }}</option>
                        @endforeach
                    </select>
                    @if ($activeFiltersCount > 0)
                        <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-white px-1 text-[11px] font-semibold text-[#060b2b]">{{ $activeFiltersCount }}</span>
                    @endif
                </div>

                <input type="text" name="user" value="{{ request('user', request('actor')) }}"
                       class="lg:col-span-3 text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none"
                       placeholder="Filtrer par utilisateur...">

                <select name="result" class="lg:col-span-3 text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none">
                    <option value="">Tous les statuts</option>
                    @foreach ($resultLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('result') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="px-5 pb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm" style="color:var(--sikds-muted)">
                    Filtres actifs:
                    @if ($nonEmptyFilters->isEmpty())
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs">Aucun</span>
                    @else
                        @foreach ($nonEmptyFilters as $key => $value)
                            @php
                                $displayValue = is_string($value) ? $value : json_encode($value);
                                if ($key === 'event_type' && is_string($value)) {
                                    $displayValue = $eventTypeLabels[$value] ?? $fallbackEventLabel($value);
                                }
                                if ($key === 'result' && is_string($value)) {
                                    $displayValue = $resultLabels[$value] ?? $value;
                                }
                                $displayKey = $filterLabelMap[$key] ?? str($key)->replace('_', ' ')->title()->toString();
                            @endphp
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs">
                                {{ $displayKey }}: {{ $displayValue }}
                            </span>
                        @endforeach
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="copy-audits-link"
                            class="px-4 py-2 text-sm border border-black/10 rounded-[10px] hover:bg-gray-50 transition-colors">
                        Copier le lien
                    </button>
                    <a href="{{ route('audits.index') }}"
                       class="px-4 py-2 text-sm border border-black/10 rounded-[10px] hover:bg-gray-50 transition-colors">
                        Réinitialiser
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white rounded-[10px]" style="background-color:var(--sikds-primary);">
                        Appliquer
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
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Utilisateur</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Action</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Cible</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Horodatage</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Statut</th>
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
                            $actionLabel = $eventTypeLabels[$event] ?? $fallbackEventLabel($event);
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
                                <p class="font-semibold text-sm" style="color:var(--sikds-ink)">{{ $log->user?->full_name ?? 'Utilisateur Inconnu' }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ $log->user_email ?? $log->user?->email ?? 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ $actionLabel }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">Événement audité</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ $targetLabel }}</p>
                                <details>
                                    <summary class="cursor-pointer text-xs font-medium" style="color:var(--sikds-primary)">Contexte</summary>
                                    <div class="mt-2 space-y-2">
                                        @php($meta = is_array($log->metadata) ? $log->metadata : [])
                                        <ul class="text-xs space-y-1" style="color:var(--sikds-muted)">
                                            @foreach ($contextSummary($event, $meta) as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                            <li>Appareil: {{ $log->user_agent ? 'Navigateur web' : 'Système' }}</li>
                                        </ul>

                                        @if ($isSuperAdmin)
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs font-medium" style="color:var(--sikds-primary)">Voir JSON</summary>
                                                <pre class="mt-2 text-xs bg-gray-50 border border-black/10 rounded-[8px] p-2 overflow-auto max-h-28">{{ json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                                            </details>
                                        @endif
                                    </div>
                                </details>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ $log->created_at?->format('Y-m-d H:i:s') }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ $log->ip_address ?? 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="sikds-status {{ $badgeClass }}">{{ $resultLabels[$log->result] ?? ucfirst((string) ($log->result ?? 'success')) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-sm" style="color:var(--sikds-muted)">
                                Aucun événement d'audit trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t flex items-center justify-between" style="border-color:rgba(0,0,0,.1);">
            <p class="text-sm" style="color:var(--sikds-muted)">
                Affichage de {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} sur {{ number_format($logs->total()) }} événements
            </p>
            <div class="flex items-center gap-2">
                @if ($logs->onFirstPage())
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Précédent</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Précédent</a>
                @endif
                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Suivant</a>
                @else
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Suivant</span>
                @endif
            </div>
        </div>
    </div>

    <div id="export-audit-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/30" id="export-modal-overlay"></div>
        <div class="relative w-full max-w-xl rounded-2xl bg-white border border-black/10 shadow-2xl" style="font-family:'Inter', ui-sans-serif, sans-serif;">
            <div class="flex items-start justify-between px-6 py-5 border-b border-black/10">
                <div>
                    <h3 class="text-[22px] leading-7 font-semibold" style="color:var(--sikds-ink)">Exporter les Journaux</h3>
                    <p class="mt-1 text-[14px]" style="color:var(--sikds-muted)">Télécharger l'historique d'audit</p>
                </div>
                <button type="button" id="close-export-modal" class="text-xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
            </div>

            <form method="GET" action="{{ route('audits.export') }}" class="px-6 py-5 space-y-5">
                @foreach(request()->query() as $k => $v)
                    @if($k !== 'page')
                        <input type="hidden" name="{{ $k }}" value="{{ is_array($v) ? json_encode($v) : $v }}">
                    @endif
                @endforeach

                <div>
                    <label class="block text-[15px] font-semibold mb-2">Format d'Export <span class="text-red-500">*</span></label>
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 rounded-xl border border-black/10 p-3 cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="format" value="csv" checked class="mt-1">
                            <div>
                                <p class="font-semibold text-[14px] flex items-center gap-2">
                                    <img src="/csv_green.svg" alt="" class="h-4 w-4">
                                    CSV
                                </p>
                                <p class="text-[12px]" style="color:var(--sikds-muted)">Fichier tableur compatible Excel</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 rounded-xl border border-black/10 p-3 cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="format" value="json" class="mt-1">
                            <div>
                                <p class="font-semibold text-[14px] flex items-center gap-2">
                                    <img src="/csv_blue.svg" alt="" class="h-4 w-4">
                                    JSON
                                </p>
                                <p class="text-[12px]" style="color:var(--sikds-muted)">Format structuré pour analyse</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 rounded-xl border border-black/10 p-3 opacity-60 cursor-not-allowed">
                            <input type="radio" disabled class="mt-1">
                            <div>
                                <p class="font-semibold text-[14px] flex items-center gap-2">
                                    <img src="/csv_red.svg" alt="" class="h-4 w-4">
                                    PDF
                                </p>
                                <p class="text-[12px]" style="color:var(--sikds-muted)">Rapport imprimable (bientôt disponible)</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-[15px] font-semibold mb-2">Période</label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="date" name="export_date_from" class="text-[13px] border border-black/10 rounded-[10px] px-3 py-2">
                        <input type="date" name="export_date_to" class="text-[13px] border border-black/10 rounded-[10px] px-3 py-2">
                    </div>
                </div>

                <div class="rounded-xl px-3 py-2 text-[12px]" style="background:#eef4ff;color:#3859d0;">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    L'export inclura tous les événements selon les filtres actifs.
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" id="cancel-export-modal" class="px-5 py-2 text-sm border border-black/10 rounded-[10px] hover:bg-gray-50">Annuler</button>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white rounded-[10px]" style="background-color:var(--sikds-primary);">
                        <i class="fa-solid fa-download mr-1"></i> Exporter
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const filterForm = document.getElementById('audit-filter-form');
                const dateInput = filterForm?.querySelector('input[name="date"]');
                const eventTypeSelect = filterForm?.querySelector('select[name="event_type"]');
                const resultSelect = filterForm?.querySelector('select[name="result"]');
                const userInput = filterForm?.querySelector('input[name="user"]');
                const applyBtn = filterForm?.querySelector('button[type="submit"]');

                const submitFilters = () => {
                    if (!filterForm) return;
                    applyBtn?.classList.add('opacity-70');
                    applyBtn?.setAttribute('disabled', 'disabled');
                    filterForm.submit();
                };

                eventTypeSelect?.addEventListener('change', submitFilters);
                resultSelect?.addEventListener('change', submitFilters);
                dateInput?.addEventListener('change', submitFilters);

                let userDebounceTimer = null;
                userInput?.addEventListener('input', function () {
                    if (userDebounceTimer) clearTimeout(userDebounceTimer);
                    userDebounceTimer = setTimeout(submitFilters, 450);
                });

                userInput?.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        submitFilters();
                    }
                });

                const copyBtn = document.getElementById('copy-audits-link');
                if (copyBtn) {
                    copyBtn.addEventListener('click', async function () {
                        try {
                            await navigator.clipboard.writeText(window.location.href);
                            copyBtn.textContent = 'Lien copié';
                            setTimeout(() => { copyBtn.textContent = 'Copier le lien'; }, 1200);
                        } catch (e) {
                            copyBtn.textContent = 'Copie impossible';
                            setTimeout(() => { copyBtn.textContent = 'Copier le lien'; }, 1200);
                        }
                    });
                }

                const modal = document.getElementById('export-audit-modal');
                const openBtn = document.getElementById('open-export-modal');
                const closeBtn = document.getElementById('close-export-modal');
                const cancelBtn = document.getElementById('cancel-export-modal');
                const overlay = document.getElementById('export-modal-overlay');

                if (!modal || !openBtn) return;

                const open = () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                };
                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                };

                openBtn.addEventListener('click', open);
                closeBtn?.addEventListener('click', close);
                cancelBtn?.addEventListener('click', close);
                overlay?.addEventListener('click', close);
            });
        </script>
    @endpush
@endsection
