@extends('layouts.app')
@section('page_title', __('Notifications'))
@section('page_subtitle', __('Suivi des notifications email et de leur état de livraison'))
@section('content')
    <script type="application/json" id="notifications-selected-types">@json(is_array(request('type')) ? request('type') : (request('type') ? [request('type')] : []))</script>
    <script type="application/json" id="notifications-selected-statuses">@json(is_array(request('status')) ? request('status') : (request('status') ? [request('status')] : []))</script>
    @php
        $typeLabels = [
            'document.published' => __('Document publié'),
            'document.updated' => __('Document mis à jour'),
            'document.forwarded' => __('Document partagé'),
        ];
        $statusLabels = [
            'sent' => __('Envoyé'),
            'pending' => __('En attente'),
            'failed' => __('Échec'),
        ];
        $filterLabelMap = [
            'q' => __('Recherche'),
            'type' => __('Type'),
            'status' => __('Statut'),
            'date_from' => __('Du'),
            'date_to' => __('Au'),
        ];
        $selectedTypes = is_array(request('type')) ? request('type') : (request('type') ? [request('type')] : []);
        $selectedStatuses = is_array(request('status')) ? request('status') : (request('status') ? [request('status')] : []);
        $nonEmptyFilters = collect(request()->query())
            ->except('page')
            ->filter(function ($value): bool {
                if (is_array($value)) {
                    return collect($value)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
                }
                return $value !== null && $value !== '';
            });
        $canViewTechnicalDetails = auth()->user()?->can('audit.view') ?? false;
        $humanEmailError = static function (?string $raw): ?string {
            if (! is_string($raw) || trim($raw) === '') {
                return null;
            }
            $msg = trim($raw);

            // Common local-dev misconfigurations (Mailpit / SMTP)
            if (str_contains($msg, 'Unsupported mail transport')) {
                return __("L'envoi email est indisponible (configuration SMTP incomplète). Vérifiez la configuration Mailpit et relancez le worker.");
            }
            if (str_contains($msg, 'Connection refused') || str_contains($msg, 'Could not connect')) {
                return __("Impossible de se connecter au serveur mail. Vérifiez que Mailpit est démarré et accessible.");
            }
            if (str_contains($msg, 'getaddrinfo') || str_contains($msg, 'Name or service not known')) {
                return __("Serveur mail introuvable. Vérifiez l'hôte SMTP (MAIL_HOST).");
            }
            if (str_contains($msg, 'Authentication') || str_contains($msg, 'auth')) {
                return __("Échec d'authentification SMTP. Vérifiez les identifiants mail.");
            }

            // Default (still French, generic)
            return __("Échec lors de l'envoi de l'email. Veuillez réessayer ou contacter l'administrateur.");
        };
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <x-stat-card icon="/bell-blue.svg" value="{{ number_format($stats['total']) }}" :label="__('Total')" trend="{{ $stats['total'] }}" iconStyle="filter: brightness(0) saturate(100%) invert(23%) sepia(66%) saturate(1400%) hue-rotate(210deg) brightness(95%) contrast(95%);" />
        <x-stat-card icon="/upload-blue.svg" value="{{ number_format($stats['sent']) }}" :label="__('Envoyés')" trend="{{ $stats['sent'] }}" iconStyle="filter: brightness(0) saturate(100%) invert(23%) sepia(66%) saturate(1400%) hue-rotate(210deg) brightness(95%) contrast(95%);" />
        <x-stat-card icon="/danger.svg" value="{{ number_format($stats['failed']) }}" :label="__('En échec')" trend="{{ $stats['failed'] }}" iconStyle="filter: brightness(0) saturate(100%) invert(23%) sepia(66%) saturate(1400%) hue-rotate(210deg) brightness(95%) contrast(95%);" />
        <x-stat-card icon="/time-dark-blue.svg" value="{{ number_format($stats['pending']) }}" :label="__('En attente')" trend="{{ $stats['pending'] }}" iconStyle="filter: brightness(0) saturate(100%) invert(23%) sepia(66%) saturate(1400%) hue-rotate(210deg) brightness(95%) contrast(95%);" />
    </div>

    <div x-data="notificationFilters()" class="bg-white rounded-[14px] border shadow-sm mb-5" style="border-color:rgba(0,0,0,.1);">
        <form method="GET" action="{{ route('notifications.index') }}" id="notifications-filter-form" x-ref="filterForm">
            <div class="px-5 pt-4 pb-3 grid grid-cols-1 lg:grid-cols-12 gap-3">
                {{-- Type multi-select --}}
                <div class="lg:col-span-3 relative" @click.outside="typeOpen = false">
                    <button type="button" @click="typeOpen = !typeOpen"
                            class="h-10 w-full flex items-center gap-2 text-sm border border-[#e5e7eb] rounded-[8px] px-3 bg-white text-start focus:outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20 transition"
                            :class="{ 'border-[#1c398e] ring-2 ring-[#1c398e]/20': typeOpen }">
                        <i class="fa-solid fa-filter text-xs shrink-0" style="color:var(--sikds-muted)"></i>
                        <span class="flex-1 truncate" x-text="selectedTypes.length ? selectedTypes.length + ' ' + @js(__('type(s)')) : @js(__('Tous les types'))" :class="selectedTypes.length ? 'text-[#0a0a0a] font-medium' : 'text-[#717182]'"></span>
                        <i class="fa-solid fa-chevron-down text-[10px] shrink-0 transition-transform" :class="{ 'rotate-180': typeOpen }" style="color:var(--sikds-muted)"></i>
                    </button>
                    @if (count($selectedTypes) > 0)
                        <span class="absolute -top-1.5 -end-1.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[11px] font-semibold text-white" style="background-color:var(--sikds-primary)">{{ count($selectedTypes) }}</span>
                    @endif
                    <div x-show="typeOpen" x-transition.origin.top
                         class="absolute left-0 right-0 top-full z-50 mt-1.5 max-h-64 overflow-y-auto rounded-[12px] border border-black/10 bg-white py-1 shadow-[0_8px_30px_-4px_rgba(0,0,0,0.15)]" x-cloak>
                        @foreach ($types as $t)
                            <label class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-[#f1f5f9] transition-colors">
                                <input type="checkbox" name="type[]" value="{{ $t }}"
                                       {{ in_array($t, $selectedTypes, true) ? 'checked' : '' }}
                                       x-model="selectedTypes"
                                       class="h-4 w-4 rounded border-black/20 text-[#1c398e] focus:ring-[#1c398e]/30">
                                <span>{{ $typeLabels[$t] ?? $t }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Status multi-select --}}
                <div class="lg:col-span-3 relative" @click.outside="statusOpen = false">
                    <button type="button" @click="statusOpen = !statusOpen"
                            class="h-10 w-full flex items-center gap-2 text-sm border border-[#e5e7eb] rounded-[8px] px-3 bg-white text-start focus:outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20 transition"
                            :class="{ 'border-[#1c398e] ring-2 ring-[#1c398e]/20': statusOpen }">
                        <span class="flex-1 truncate" x-text="selectedStatuses.length ? selectedStatuses.length + ' ' + @js(__('statut(s)')) : @js(__('Tous les statuts'))" :class="selectedStatuses.length ? 'text-[#0a0a0a] font-medium' : 'text-[#717182]'"></span>
                        <i class="fa-solid fa-chevron-down text-[10px] shrink-0 transition-transform" :class="{ 'rotate-180': statusOpen }" style="color:var(--sikds-muted)"></i>
                    </button>
                    @if (count($selectedStatuses) > 0)
                        <span class="absolute -top-1.5 -end-1.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[11px] font-semibold text-white" style="background-color:var(--sikds-primary)">{{ count($selectedStatuses) }}</span>
                    @endif
                    <div x-show="statusOpen" x-transition.origin.top
                         class="absolute left-0 right-0 top-full z-50 mt-1.5 rounded-[12px] border border-black/10 bg-white py-1 shadow-[0_8px_30px_-4px_rgba(0,0,0,0.15)]" x-cloak>
                        @foreach ($statusLabels as $key => $label)
                            <label class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-[#f1f5f9] transition-colors">
                                <input type="checkbox" name="status[]" value="{{ $key }}"
                                       {{ in_array($key, $selectedStatuses, true) ? 'checked' : '' }}
                                       x-model="selectedStatuses"
                                       class="h-4 w-4 rounded border-black/20 text-[#1c398e] focus:ring-[#1c398e]/30">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Date from --}}
                <div class="lg:col-span-3">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="h-10 w-full text-sm border border-[#e5e7eb] rounded-[8px] px-3 bg-white focus:outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20 transition">
                </div>

                {{-- Date to --}}
                <div class="lg:col-span-3">
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="h-10 w-full text-sm border border-[#e5e7eb] rounded-[8px] px-3 bg-white focus:outline-none focus:border-[#1c398e] focus:ring-2 focus:ring-[#1c398e]/20 transition">
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
                                        if ($key === 'type') {
                                            $displayParts[] = $typeLabels[$v] ?? $v;
                                        } elseif ($key === 'status') {
                                            $displayParts[] = $statusLabels[$v] ?? $v;
                                        } else {
                                            $displayParts[] = $v;
                                        }
                                    }
                                    $displayValue = implode(', ', $displayParts);
                                } else {
                                    $displayValue = $value;
                                    if ($key === 'type' && is_string($value)) {
                                        $displayValue = $typeLabels[$value] ?? $value;
                                    }
                                    if ($key === 'status' && is_string($value)) {
                                        $displayValue = $statusLabels[$value] ?? $value;
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
                    <a href="{{ route('notifications.index') }}"
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
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Date') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Type') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Destinataire') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Document') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Statut email') }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">{{ __('Détail') }}</th>
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
                                <p class="font-semibold text-sm" style="color:var(--sikds-ink)">{{ $notification->recipient_name ?? __('Utilisateur supprimé') }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ $notification->recipient_email ?? __('N/A') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium" style="color:var(--sikds-ink)">{{ $notification->document_title ?? __('N/A') }}</p>
                                <p class="text-xs" style="color:var(--sikds-muted)">{{ $notification->document_reference ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="sikds-status {{ $badgeClass }}">{{ $statusLabels[$status] ?? $status }}</span>
                                @if ($notification->email_sent_at)
                                    <p class="text-xs mt-1" style="color:var(--sikds-muted)">{{ __('Envoyé:') }} {{ \Illuminate\Support\Carbon::parse($notification->email_sent_at)->format('Y-m-d H:i') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <details>
                                    <summary class="cursor-pointer text-xs font-medium" style="color:var(--sikds-primary)">{{ __('Voir') }}</summary>
                                    <div class="mt-2 space-y-2">
                                        @php
                                            $rawError = is_string($notification->email_error) ? $notification->email_error : null;
                                            $friendly = $humanEmailError($rawError);
                                            $meta = json_decode($notification->metadata, true);
                                            $meta = is_array($meta) ? $meta : [];
                                        @endphp

                                        @if ($status === 'sent')
                                            <p class="text-xs" style="color:var(--sikds-muted)">{{ __('Email envoyé avec succès.') }}</p>
                                        @elseif ($status === 'pending')
                                            <p class="text-xs" style="color:var(--sikds-muted)">{{ __('Email en attente de traitement par le worker.') }}</p>
                                        @elseif ($status === 'failed')
                                            <p class="text-xs text-red-600">{{ $friendly ?? __('Échec lors de l\'envoi de l\'email.') }}</p>
                                        @endif

                                        @if (!empty($meta))
                                            <div class="text-xs rounded-[10px] border border-black/10 bg-gray-50 p-2">
                                                <p class="font-semibold mb-1" style="color:var(--sikds-ink)">{{ __('Données') }}</p>
                                                <ul class="space-y-1" style="color:var(--sikds-muted)">
                                                    @if(isset($meta['reference_number'])) <li>{{ __('Référence:') }} {{ $meta['reference_number'] }}</li> @endif
                                                    @if(isset($meta['version_number'])) <li>{{ __('Version:') }} v{{ $meta['version_number'] }}</li> @endif
                                                    @if(isset($meta['new_version'])) <li>{{ __('Nouvelle version:') }} v{{ $meta['new_version'] }}</li> @endif
                                                </ul>
                                            </div>
                                        @endif

                                        @if ($canViewTechnicalDetails && $rawError)
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs font-medium" style="color:var(--sikds-primary)">{{ __('Détails techniques') }}</summary>
                                                <pre class="mt-2 text-xs bg-gray-50 border border-black/10 rounded-[8px] p-2 overflow-auto max-h-28">{{ $rawError }}</pre>
                                            </details>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8">
                                <x-empty-state
                                    icon="fa-regular fa-bell-slash"
                                    :title="$nonEmptyFilters->isNotEmpty() ? __('Aucune notification trouvée.') : __('Aucune notification')"
                                    :description="$nonEmptyFilters->isNotEmpty() ? __('Modifiez ou réinitialisez les filtres pour afficher davantage de notifications.') : __('Les notifications email apparaîtront ici après les publications, mises à jour et partages de documents.')"
                                    class="shadow-none"
                                >
                                    @if ($nonEmptyFilters->isNotEmpty())
                                        <x-slot:action>
                                            <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-2 rounded-[10px] border border-black/10 bg-white px-4 py-2 text-sm font-medium text-[#0A0A0A] transition hover:bg-black/[0.03]">
                                                <i class="fa-solid fa-rotate-left text-xs"></i>
                                                {{ __('Réinitialiser les filtres') }}
                                            </a>
                                        </x-slot:action>
                                    @endif
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t flex items-center justify-between" style="border-color:rgba(0,0,0,.1);">
            <p class="text-sm" style="color:var(--sikds-muted)">
                {{ __('Affichage de') }} {{ $notifications->firstItem() ?? 0 }}-{{ $notifications->lastItem() ?? 0 }} {{ __('sur') }} {{ number_format($notifications->total()) }} {{ __('notifications') }}
            </p>
            <div class="flex items-center gap-2">
                @if ($notifications->onFirstPage())
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">{{ __('Précédent') }}</span>
                @else
                    <a href="{{ $notifications->previousPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">{{ __('Précédent') }}</a>
                @endif
                @if ($notifications->hasMorePages())
                    <a href="{{ $notifications->nextPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">{{ __('Suivant') }}</a>
                @else
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">{{ __('Suivant') }}</span>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function notificationFilters() {
                return {
                    typeOpen: false,
                    statusOpen: false,
                    selectedTypes: JSON.parse(document.getElementById('notifications-selected-types')?.textContent || '[]'),
                    selectedStatuses: JSON.parse(document.getElementById('notifications-selected-statuses')?.textContent || '[]'),
                };
            }
            window.notificationFilters = notificationFilters;

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('notifications-filter-form');
                if (!form) return;

                const from = form.querySelector('input[name="date_from"]');
                const to = form.querySelector('input[name="date_to"]');
                const apply = form.querySelector('button[type="submit"]');

                const submit = () => {
                    apply?.classList.add('opacity-70');
                    apply?.setAttribute('disabled', 'disabled');
                    form.submit();
                };

                from?.addEventListener('change', submit);
                to?.addEventListener('change', submit);

                let multiSelectTimer = null;
                form.querySelectorAll('input[type="checkbox"][name="type[]"], input[type="checkbox"][name="status[]"]').forEach((cb) => {
                    cb.addEventListener('change', function () {
                        if (multiSelectTimer) clearTimeout(multiSelectTimer);
                        multiSelectTimer = setTimeout(submit, 250);
                    });
                });
            });
        </script>
    @endpush
@endsection
