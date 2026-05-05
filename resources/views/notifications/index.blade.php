@extends('layouts.app')
@section('page_title', 'Notifications')
@section('page_subtitle', 'Suivi des notifications email et de leur état de livraison')
@section('content')
    @php
        $typeLabels = [
            'document.published' => 'Document publié',
            'document.updated' => 'Document mis à jour',
        ];
        $statusLabels = [
            'sent' => 'Envoyé',
            'pending' => 'En attente',
            'failed' => 'Échec',
        ];
        $canViewTechnicalDetails = auth()->user()?->can('audit.view') ?? false;
        $humanEmailError = static function (?string $raw): ?string {
            if (! is_string($raw) || trim($raw) === '') {
                return null;
            }
            $msg = trim($raw);

            // Common local-dev misconfigurations (Mailpit / SMTP)
            if (str_contains($msg, 'Unsupported mail transport')) {
                return "L'envoi email est indisponible (configuration SMTP incomplète). Vérifiez la configuration Mailpit et relancez le worker.";
            }
            if (str_contains($msg, 'Connection refused') || str_contains($msg, 'Could not connect')) {
                return "Impossible de se connecter au serveur mail. Vérifiez que Mailpit est démarré et accessible.";
            }
            if (str_contains($msg, 'getaddrinfo') || str_contains($msg, 'Name or service not known')) {
                return "Serveur mail introuvable. Vérifiez l'hôte SMTP (MAIL_HOST).";
            }
            if (str_contains($msg, 'Authentication') || str_contains($msg, 'auth')) {
                return "Échec d'authentification SMTP. Vérifiez les identifiants mail.";
            }

            // Default (still French, generic)
            return "Échec lors de l'envoi de l'email. Veuillez réessayer ou contacter l'administrateur.";
        };
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <x-stat-card icon="/bell-blue.svg" value="{{ number_format($stats['total']) }}" label="Total" trend="{{ $stats['total'] }}" iconStyle="filter: brightness(0) saturate(100%) invert(23%) sepia(66%) saturate(1400%) hue-rotate(210deg) brightness(95%) contrast(95%);" />
        <x-stat-card icon="/upload-blue.svg" value="{{ number_format($stats['sent']) }}" label="Envoyés" trend="{{ $stats['sent'] }}" iconStyle="filter: brightness(0) saturate(100%) invert(23%) sepia(66%) saturate(1400%) hue-rotate(210deg) brightness(95%) contrast(95%);" />
        <x-stat-card icon="/danger.svg" value="{{ number_format($stats['failed']) }}" label="En échec" trend="{{ $stats['failed'] }}" iconStyle="filter: brightness(0) saturate(100%) invert(23%) sepia(66%) saturate(1400%) hue-rotate(210deg) brightness(95%) contrast(95%);" />
        <x-stat-card icon="/time-dark-blue.svg" value="{{ number_format($stats['pending']) }}" label="En attente" trend="{{ $stats['pending'] }}" iconStyle="filter: brightness(0) saturate(100%) invert(23%) sepia(66%) saturate(1400%) hue-rotate(210deg) brightness(95%) contrast(95%);" />
    </div>

    <div class="bg-white rounded-[14px] border shadow-sm mb-5" style="border-color:rgba(0,0,0,.1);">
        <form method="GET" action="{{ route('notifications.index') }}" class="px-5 py-4" id="notifications-filter-form">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <input type="text" name="q" value="{{ request('q') }}"
                       class="md:col-span-2 text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none"
                       placeholder="Rechercher utilisateur/document/type...">
                <select name="type" class="text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none">
                    <option value="">Tous les types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ $typeLabels[$type] ?? $type }}</option>
                    @endforeach
                </select>
                <select name="status" class="text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none">
                    <option value="">Tous les statuts</option>
                    @foreach ($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none">
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="text-sm border border-black/10 rounded-[10px] px-3 py-2 focus:outline-none">
            </div>
            <div class="mt-3 flex justify-end gap-2">
                <a href="{{ route('notifications.index') }}"
                   class="px-4 py-2 text-sm border border-black/10 rounded-[10px] hover:bg-gray-50 transition-colors">
                    Réinitialiser
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white rounded-[10px]" style="background-color:var(--sikds-primary);">
                    Appliquer
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-[14px] border overflow-hidden" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:rgba(0,0,0,.1);background:#f9f9fb;">
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Destinataire</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Document</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Statut email</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Détail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notifications as $notification)
                        @php
                            $status = $notification->email_status ?: 'pending';
                            $badgeClass = match($status) {
                                'failed' => 'sikds-status--deleted',
                                'pending' => 'sikds-status--archived',
                                default => 'sikds-status--active',
                            };
                        @endphp
                        <tr class="border-b hover:bg-[#f9f9fb] transition-colors" style="border-color:rgba(0,0,0,.06);">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ \Illuminate\Support\Carbon::parse($notification->created_at)->format('Y-m-d') }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ \Illuminate\Support\Carbon::parse($notification->created_at)->format('H:i:s') }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium" style="color:var(--sikds-ink)">
                                {{ $typeLabels[$notification->type] ?? $notification->type }}
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-sm" style="color:var(--sikds-ink)">{{ $notification->recipient_name ?? 'Utilisateur supprimé' }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ $notification->recipient_email ?? 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ $notification->document_title ?? 'N/A' }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ $notification->document_reference ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="sikds-status {{ $badgeClass }}">{{ $statusLabels[$status] ?? $status }}</span>
                                @if ($notification->email_sent_at)
                                    <p class="text-xs mt-1" style="color:var(--sikds-muted)">Envoyé: {{ \Illuminate\Support\Carbon::parse($notification->email_sent_at)->format('Y-m-d H:i') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <details>
                                    <summary class="cursor-pointer text-xs font-medium" style="color:var(--sikds-primary)">Voir</summary>
                                    <div class="mt-2 space-y-2">
                                        @php
                                            $rawError = is_string($notification->email_error) ? $notification->email_error : null;
                                            $friendly = $humanEmailError($rawError);
                                            $meta = json_decode($notification->metadata, true);
                                            $meta = is_array($meta) ? $meta : [];
                                        @endphp

                                        @if ($status === 'sent')
                                            <p class="text-xs" style="color:var(--sikds-muted)">Email envoyé avec succès.</p>
                                        @elseif ($status === 'pending')
                                            <p class="text-xs" style="color:var(--sikds-muted)">Email en attente de traitement par le worker.</p>
                                        @elseif ($status === 'failed')
                                            <p class="text-xs text-red-600">{{ $friendly ?? "Échec lors de l'envoi de l'email." }}</p>
                                        @endif

                                        @if (!empty($meta))
                                            <div class="text-xs rounded-[10px] border border-black/10 bg-gray-50 p-2">
                                                <p class="font-semibold mb-1" style="color:var(--sikds-ink)">Données</p>
                                                <ul class="space-y-1" style="color:var(--sikds-muted)">
                                                    @if(isset($meta['reference_number'])) <li>Référence: {{ $meta['reference_number'] }}</li> @endif
                                                    @if(isset($meta['version_number'])) <li>Version: v{{ $meta['version_number'] }}</li> @endif
                                                    @if(isset($meta['new_version'])) <li>Nouvelle version: v{{ $meta['new_version'] }}</li> @endif
                                                </ul>
                                            </div>
                                        @endif

                                        @if ($canViewTechnicalDetails && $rawError)
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs font-medium" style="color:var(--sikds-primary)">Détails techniques</summary>
                                                <pre class="mt-2 text-xs bg-gray-50 border border-black/10 rounded-[8px] p-2 overflow-auto max-h-28">{{ $rawError }}</pre>
                                            </details>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-sm" style="color:var(--sikds-muted)">
                                Aucune notification trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t flex items-center justify-between" style="border-color:rgba(0,0,0,.1);">
            <p class="text-sm" style="color:var(--sikds-muted)">
                Affichage de {{ $notifications->firstItem() ?? 0 }}-{{ $notifications->lastItem() ?? 0 }} sur {{ number_format($notifications->total()) }} notifications
            </p>
            <div class="flex items-center gap-2">
                @if ($notifications->onFirstPage())
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Précédent</span>
                @else
                    <a href="{{ $notifications->previousPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Précédent</a>
                @endif
                @if ($notifications->hasMorePages())
                    <a href="{{ $notifications->nextPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Suivant</a>
                @else
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Suivant</span>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('notifications-filter-form');
                if (!form) return;

                const q = form.querySelector('input[name="q"]');
                const type = form.querySelector('select[name="type"]');
                const status = form.querySelector('select[name="status"]');
                const from = form.querySelector('input[name="date_from"]');
                const to = form.querySelector('input[name="date_to"]');
                const apply = form.querySelector('button[type="submit"]');

                const submit = () => {
                    apply?.classList.add('opacity-70');
                    apply?.setAttribute('disabled', 'disabled');
                    form.submit();
                };

                type?.addEventListener('change', submit);
                status?.addEventListener('change', submit);
                from?.addEventListener('change', submit);
                to?.addEventListener('change', submit);

                let t = null;
                q?.addEventListener('input', function () {
                    if (t) clearTimeout(t);
                    t = setTimeout(submit, 450);
                });

                q?.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        submit();
                    }
                });
            });
        </script>
    @endpush
@endsection
