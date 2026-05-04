@extends('layouts.app')
@section('page_title', 'Détail du Filigrane')
@section('page_subtitle', 'Traçabilité du filigrane de téléchargement')
@section('content')
    @php
        $year       = $log->downloaded_at?->format('Y') ?? date('Y');
        $shortUuid  = 'WM-' . $year . '-' . strtoupper(substr(str_replace('-', '', $log->watermark_uuid), 0, 8));
        $userId     = 'USR-' . ($log->user?->created_at?->format('Y') ?? date('Y')) . '-' . str_pad($log->user?->id, 3, '0', STR_PAD_LEFT);
        $downloadId = 'DL-'  . $year . '-' . str_pad($log->id, 4, '0', STR_PAD_LEFT);
        $auditId    = $auditEntry ? ('AUD-' . ($auditEntry->created_at?->format('Y') ?? date('Y')) . '-' . str_pad($auditEntry->id, 4, '0', STR_PAD_LEFT)) : null;
    @endphp

    {{-- Top bar: back link + identity --}}
    <x-back-link :href="route('watermark.index')" label="Retour à la traçabilité" class="mb-3" />

    <div class="flex items-center gap-3 mb-6">
        <div class="h-10 w-10 rounded-[10px] grid place-items-center flex-shrink-0" style="background:var(--sikds-primary)">
            <img src="/traceability.svg" alt="" class="h-5 w-5">
        </div>
        <div>
            <div class="flex items-center gap-2 mb-0.5">
                <h2 class="font-semibold text-lg" style="color:var(--sikds-ink)">Détail du Filigrane</h2>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#dcfce7;color:#15803d;">&#10003; Vérifié</span>
            </div>
            <p class="font-semibold text-sm font-mono" style="color:var(--sikds-muted)"># {{ $shortUuid }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ===== LEFT 2/3 ===== --}}
        <div class="lg:col-span-2 space-y-5 min-w-0">

            {{-- Document info --}}
            <div class="bg-white rounded-[14px] border p-6" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-5">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/document-blue.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-base" style="color:var(--sikds-ink)">Informations du Document</h2>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-16 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#f6f6f6;">
                        <img src="/document.svg" alt="" class="h-7 w-7 opacity-50">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-base" style="color:var(--sikds-ink)">{{ $log->document?->title ?? '&mdash;' }}</p>
                        <p class="text-xs mt-1" style="color:var(--sikds-muted)">Référence&nbsp;: {{ $log->document?->reference_number ?? '&mdash;' }}</p>
                        <p class="text-xs mt-0.5" style="color:var(--sikds-muted)">Version&nbsp;: {{ $log->document?->version_number ?? '&mdash;' }}</p>
                    </div>
                </div>
            </div>

            {{-- User info --}}
            <div class="bg-white rounded-[14px] border p-6" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-5">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/person-blue.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-base" style="color:var(--sikds-ink)">Informations de l'Utilisateur</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Nom complet</p>
                        <p class="font-semibold text-sm break-words" style="color:var(--sikds-ink)">{{ $log->user?->full_name ?? $log->user?->username ?? '&mdash;' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">ID Utilisateur</p>
                        <p class="text-sm font-mono break-all" style="color:var(--sikds-ink)">{{ $userId }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Email</p>
                        <p class="text-sm break-all" style="color:var(--sikds-ink)">{{ $log->user?->email ?? '&mdash;' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Institution</p>
                        <div class="flex items-start gap-1.5">
                            <img src="/building-blue.svg" alt="" class="h-3.5 w-3.5 opacity-60 mt-0.5 shrink-0">
                            <p class="text-sm break-words" style="color:var(--sikds-ink)">{{ $log->user?->institution?->name ?? '&mdash;' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Download info --}}
            <div class="bg-white rounded-[14px] border p-6" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-5">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/upload-blue.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-base" style="color:var(--sikds-ink)">Informations du Téléchargement</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">ID Téléchargement</p>
                        <p class="text-sm font-mono break-all" style="color:var(--sikds-ink)">{{ $downloadId }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Statut</p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#dcfce7;color:#15803d;">&#x25CF; Complété</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Date &amp; Heure</p>
                        <div class="flex items-start gap-1.5">
                            <img src="/time-dark-blue.svg" alt="" class="h-3.5 w-3.5 opacity-60 mt-0.5 shrink-0">
                            <span class="text-sm break-words" style="color:var(--sikds-ink)">{{ $log->downloaded_at?->format('Y-m-d H:i:s') }}</span>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Adresse IP</p>
                        <div class="flex items-start gap-1.5">
                            <img src="/traceability-blue.svg" alt="" class="h-3.5 w-3.5 opacity-60 mt-0.5 shrink-0">
                            <span class="font-mono text-sm break-all" style="color:var(--sikds-ink)">{{ $log->ip_address }}</span>
                        </div>
                    </div>
                </div>
                @if ($log->user_agent)
                    <div class="mt-3">
                        <p class="text-xs mb-1" style="color:var(--sikds-muted)">User Agent</p>
                        <div class="rounded-[10px] p-3 text-xs font-mono break-all" style="background:#f6f6f6;color:var(--sikds-ink)">{{ $log->user_agent }}</div>
                    </div>
                @endif
            </div>

            {{-- Audit entry --}}
            <div class="bg-white rounded-[14px] border p-6" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-5">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/audit-blue.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-base" style="color:var(--sikds-ink)">Événement d'Audit Lié</h2>
                </div>
                @if ($auditEntry)
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-sm" style="color:var(--sikds-ink)">Action&nbsp;: {{ $auditEntry->event_type }}</p>
                            <p class="text-xs mt-1" style="color:var(--sikds-muted)">ID&nbsp;: {{ $auditId }} | {{ $auditEntry->created_at?->format('Y-m-d H:i:s') }}</p>
                        </div>
                        <a href="{{ route('audits.index', [
                                'event_type' => $auditEntry->event_type,
                                'date' => optional($auditEntry->created_at)->format('Y-m-d'),
                            ]) }}"
                           class="flex-shrink-0 inline-flex items-center gap-1.5 text-sm font-medium hover:underline"
                           style="color:var(--sikds-primary)">
                            <img src="/distribution-blue.svg" alt="" class="h-3.5 w-3.5">
                            Voir dans les audits &rarr;
                        </a>
                    </div>
                @else
                    <x-alert-item type="info" message="Aucun événement d'audit associé à ce téléchargement." timestamp="" />
                @endif
            </div>
        </div>

        {{-- ===== RIGHT 1/3 ===== --}}
        <div class="lg:col-span-1 space-y-5 min-w-0">

            {{-- Visible watermark --}}
            <div class="bg-white rounded-[14px] border p-5" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-4">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/distribution-blue.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-sm" style="color:var(--sikds-ink)">Filigrane Visible</h2>
                </div>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs mb-1" style="color:var(--sikds-muted)">En-tête</p>
                        <div class="rounded-[10px] px-3 py-2 font-mono text-xs break-all" style="background:var(--sikds-primary);color:#ffffff">
                            {{ ($log->user?->full_name ?? $log->user?->username) }} | {{ $log->user?->institution?->name ?? '&mdash;' }}
                        </div>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color:var(--sikds-muted)">Pied de page</p>
                        <div class="rounded-[10px] px-3 py-2 font-mono text-xs break-all" style="background:#f6f6f6;color:var(--sikds-ink)">
                            Téléchargé le {{ $log->downloaded_at?->format('Y-m-d') }} à {{ $log->downloaded_at?->format('H:i') }} | UUID: {{ $shortUuid }}
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs pt-1">
                        <div>
                            <p style="color:var(--sikds-muted)">Position</p>
                            <p class="font-medium mt-0.5" style="color:var(--sikds-ink)">Diagonal &amp; Footer</p>
                        </div>
                        <div>
                            <p style="color:var(--sikds-muted)">Opacité</p>
                            <p class="font-medium mt-0.5" style="color:var(--sikds-ink)">40%</p>
                        </div>
                        <div>
                            <p style="color:var(--sikds-muted)">Couleur</p>
                            <p class="font-medium mt-0.5 font-mono" style="color:var(--sikds-ink)">Gray (#666666)</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PDF Metadata --}}
            @php
                $meta = [
                    'userId'            => 'USR-' . ($log->user?->created_at?->format('Y') ?? date('Y')) . '-' . str_pad((string)($log->user?->id ?? 0), 3, '0', STR_PAD_LEFT),
                    'userName'          => $log->user?->full_name ?? $log->user?->username,
                    'userEmail'         => $log->user?->email,
                    'institutionCode'   => $log->user?->institution?->code,
                    'institutionName'   => $log->user?->institution?->name,
                    'documentId'        => 'DOC-' . ($log->document?->created_at?->format('Y') ?? date('Y')) . '-' . str_pad((string)($log->document?->id ?? 0), 3, '0', STR_PAD_LEFT),
                    'documentVersion'   => $log->document?->version_number,
                    'downloadId'        => $downloadId,
                    'downloadTimestamp' => $log->downloaded_at?->toIso8601String(),
                    'watermarkUUID'     => $shortUuid,
                    'ipAddress'         => $log->ip_address,
                ];
            @endphp
            <div class="bg-white rounded-[14px] border p-5" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-4">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/key-blue.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-sm" style="color:var(--sikds-ink)">Métadonnées PDF</h2>
                </div>
                <div class="space-y-2">
                    @foreach ($meta as $key => $value)
                        <div class="flex flex-col gap-0.5 py-1 border-b last:border-0" style="border-color:rgba(0,0,0,.06);">
                            <span class="text-[11px] font-mono uppercase tracking-wide" style="color:var(--sikds-muted)">{{ $key }}</span>
                            <span class="text-xs font-mono break-all" style="color:var(--sikds-ink)">{{ $value ?? '&mdash;' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
