<x-app-layout :activeNav="'traceability'">
    <x-slot name="header">
        @php
            $year       = $log->downloaded_at?->format('Y') ?? date('Y');
            $shortUuid  = 'WM-' . $year . '-' . strtoupper(substr(str_replace('-', '', $log->watermark_uuid), 0, 8));
            $userId     = 'USR-' . ($log->user?->created_at?->format('Y') ?? date('Y')) . '-' . str_pad($log->user?->id, 3, '0', STR_PAD_LEFT);
            $downloadId = 'DL-'  . $year . '-' . str_pad($log->id, 4, '0', STR_PAD_LEFT);
            $auditId    = $auditEntry ? ('AUD-' . ($auditEntry->created_at?->format('Y') ?? date('Y')) . '-' . str_pad($auditEntry->id, 4, '0', STR_PAD_LEFT)) : null;
        @endphp
        <div>
            <a href="{{ route('watermark.index') }}" class="flex items-center gap-1 text-xs font-medium mb-1 hover:underline" style="color:var(--sikds-muted)">
                &larr; Retour &agrave; la tra&ccedil;abilit&eacute;
            </a>
            <div class="flex items-center gap-3">
                <img src="/shield.svg" alt="" class="h-10 w-10 flex-shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="sikds-page-title">D&eacute;tail du Filigrane</h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#dcfce7;color:#15803d;">
                            &#10003; V&eacute;rifi&eacute;
                        </span>
                    </div>
                    <p class="sikds-page-subtitle font-mono"># {{ $shortUuid }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-3 gap-5">

        {{-- ===== LEFT 2/3 ===== --}}
        <div class="col-span-2 space-y-5">

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
                        <p class="text-xs mt-1" style="color:var(--sikds-muted)">R&eacute;f&eacute;rence&nbsp;: {{ $log->document?->reference_number ?? '&mdash;' }}</p>
                        <p class="text-xs mt-0.5" style="color:var(--sikds-muted)">Version&nbsp;: {{ $log->document?->version_number ?? '&mdash;' }}</p>
                    </div>
                    @if ($log->document)
                    <a href="#" class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-[10px]" style="background:var(--sikds-ink);">
                        <img src="/distribution.svg" alt="" class="h-4 w-4 brightness-0 invert">
                        Voir le document
                    </a>
                    @endif
                </div>
            </div>

            {{-- User info --}}
            <div class="bg-white rounded-[14px] border p-6" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-5">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/person.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-base" style="color:var(--sikds-ink)">Informations de l&apos;Utilisateur</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Nom complet</p>
                        <p class="font-semibold text-sm" style="color:var(--sikds-ink)">{{ $log->user?->full_name ?? $log->user?->username ?? '&mdash;' }}</p>
                    </div>
                    <div>
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">ID Utilisateur</p>
                        <p class="text-sm font-mono" style="color:var(--sikds-ink)">{{ $userId }}</p>
                    </div>
                    <div>
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Email</p>
                        <p class="text-sm" style="color:var(--sikds-ink)">{{ $log->user?->email ?? '&mdash;' }}</p>
                    </div>
                    <div>
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Institution</p>
                        <div class="flex items-center gap-1.5">
                            <img src="/building.svg" alt="" class="h-3.5 w-3.5 opacity-40">
                            <p class="text-sm" style="color:var(--sikds-ink)">{{ $log->user?->institution?->name ?? '&mdash;' }}</p>
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
                    <h2 class="font-semibold text-base" style="color:var(--sikds-ink)">Informations du T&eacute;l&eacute;chargement</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">ID T&eacute;l&eacute;chargement</p>
                        <p class="text-sm font-mono" style="color:var(--sikds-ink)">{{ $downloadId }}</p>
                    </div>
                    <div>
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Statut</p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#dcfce7;color:#15803d;">&#x25CF; Compl&eacute;t&eacute;</span>
                    </div>
                    <div>
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Date &amp; Heure</p>
                        <p class="text-sm" style="color:var(--sikds-ink)">{{ $log->downloaded_at?->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="text-xs mb-0.5" style="color:var(--sikds-muted)">Adresse IP</p>
                        <div class="flex items-center gap-1.5">
                            <img src="/traceability.svg" alt="" class="h-3.5 w-3.5 opacity-40">
                            <span class="font-mono text-sm" style="color:var(--sikds-ink)">{{ $log->ip_address }}</span>
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
                    <h2 class="font-semibold text-base" style="color:var(--sikds-ink)">&Eacute;v&eacute;nement d&apos;Audit Li&eacute;</h2>
                </div>
                @if ($auditEntry)
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-sm" style="color:var(--sikds-ink)">Action&nbsp;: {{ $auditEntry->event_type }}</p>
                            <p class="text-xs mt-1" style="color:var(--sikds-muted)">ID&nbsp;: {{ $auditId }} | {{ $auditEntry->created_at?->format('Y-m-d H:i:s') }}</p>
                        </div>
                        <a href="#" class="flex-shrink-0 inline-flex items-center gap-1 text-sm font-medium hover:underline" style="color:var(--sikds-primary)">
                            Voir dans les audits &rarr;
                        </a>
                    </div>
                @else
                    <x-alert-item type="info" message="Aucun &eacute;v&eacute;nement d&apos;audit associ&eacute; &agrave; ce t&eacute;l&eacute;chargement." timestamp="" />
                @endif
            </div>
        </div>

        {{-- ===== RIGHT 1/3 ===== --}}
        <div class="col-span-1 space-y-5">

            {{-- Visible watermark --}}
            <div class="bg-white rounded-[14px] border p-5" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-4">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/distribution.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-sm" style="color:var(--sikds-ink)">Filigrane Visible</h2>
                </div>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs mb-1" style="color:var(--sikds-muted)">En-t&ecirc;te</p>
                        <div class="rounded-[10px] px-3 py-2 font-mono text-xs break-all" style="background:#f6f6f6;color:var(--sikds-ink)">
                            {{ ($log->user?->full_name ?? $log->user?->username) }} | {{ $log->user?->institution?->name ?? '&mdash;' }}
                        </div>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color:var(--sikds-muted)">Pied de page</p>
                        <div class="rounded-[10px] px-3 py-2 font-mono text-xs break-all" style="background:#f6f6f6;color:var(--sikds-ink)">
                            T&eacute;l&eacute;charg&eacute; le {{ $log->downloaded_at?->format('d/m/Y') }} &agrave; {{ $log->downloaded_at?->format('H:i:s') }} | UUID: {{ $shortUuid }}
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs pt-1">
                        <div>
                            <p style="color:var(--sikds-muted)">Position</p>
                            <p class="font-medium mt-0.5" style="color:var(--sikds-ink)">Diagonale + Pied</p>
                        </div>
                        <div>
                            <p style="color:var(--sikds-muted)">Opacit&eacute;</p>
                            <p class="font-medium mt-0.5" style="color:var(--sikds-ink)">18%</p>
                        </div>
                        <div>
                            <p style="color:var(--sikds-muted)">Couleur</p>
                            <p class="font-medium mt-0.5 font-mono" style="color:var(--sikds-ink)">#787878</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PDF Metadata --}}
            @php
                $meta = [
                    'userId'            => $log->user?->id,
                    'userName'          => $log->user?->full_name ?? $log->user?->username,
                    'userEmail'         => $log->user?->email,
                    'institutionCode'   => $log->user?->institution?->code,
                    'institutionName'   => $log->user?->institution?->name,
                    'documentId'        => $log->document?->id,
                    'documentVersion'   => $log->document?->version_number,
                    'downloadId'        => $log->id,
                    'downloadTimestamp' => $log->downloaded_at?->toISOString(),
                    'watermarkUUID'     => $log->watermark_uuid,
                    'ipAddress'         => $log->ip_address,
                ];
            @endphp
            <div class="bg-white rounded-[14px] border p-5" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-4">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/key-blue.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-sm" style="color:var(--sikds-ink)">M&eacute;tadonn&eacute;es PDF</h2>
                </div>
                <div class="space-y-2">
                    @foreach ($meta as $key => $value)
                        <div class="flex items-start justify-between gap-2 py-1 border-b last:border-0" style="border-color:rgba(0,0,0,.06);">
                            <span class="text-xs font-mono shrink-0" style="color:var(--sikds-muted)">{{ $key }}</span>
                            <span class="text-xs font-mono text-right break-all" style="color:var(--sikds-ink)">{{ $value ?? '&mdash;' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Export JSON --}}
            <div class="bg-white rounded-[14px] border p-5" style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
                <div class="flex items-center gap-2 mb-4">
                    <div class="sikds-activity-icon-wrap">
                        <img src="/download-black.svg" alt="" class="h-4 w-4">
                    </div>
                    <h2 class="font-semibold text-sm" style="color:var(--sikds-ink)">Export JSON</h2>
                </div>
                <p class="text-xs mb-3" style="color:var(--sikds-muted)">T&eacute;l&eacute;chargez les 11 m&eacute;tadonn&eacute;es associ&eacute;es &agrave; ce filigrane.</p>
                <button type="button"
                    onclick="(function(){
                        var data = {{ Js::from($meta) }};
                        var blob = new Blob([JSON.stringify(data, null, 2)], {type:'application/json'});
                        var a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = '{{ $shortUuid }}.json';
                        a.click();
                    })()"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-[10px] border transition-colors hover:opacity-90"
                    style="border-color:rgba(0,0,0,.15);color:var(--sikds-ink);">
                    <img src="/download-black.svg" alt="" class="h-4 w-4">
                    T&eacute;l&eacute;charger JSON
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
