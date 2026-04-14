<x-app-layout :activeNav="'indexing'">
    <x-slot name="header">
        <h1 class="sikds-page-title">Document Indexing Monitor</h1>
        <p class="sikds-page-subtitle">Suivi de l’indexation des documents</p>
    </x-slot>

    <div
        class="bg-white rounded-[14px] border overflow-hidden"
        style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);"
        x-data="{ refreshUrl: @js(url()->full()) }"
    >
        <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:rgba(0,0,0,.1);background:#f9f9fb;">
            <p class="text-sm font-medium" style="color:var(--sikds-ink)">Mise à jour automatique toutes les 10 secondes</p>
            <a href="{{ url()->current() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">
                Rafraîchir
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:rgba(0,0,0,.1);background:#f9f9fb;">
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Reference</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Title</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Status</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Chunks</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Last Updated</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider" style="color:var(--sikds-muted)">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $doc)
                        @php
                            $badge = match ($doc->indexing_status) {
                                'pending' => ['label' => 'Pending', 'bg' => '#e5e7eb', 'fg' => '#374151'],
                                'processing' => ['label' => 'Processing', 'bg' => '#fef3c7', 'fg' => '#92400e'],
                                'indexed' => ['label' => 'Indexed', 'bg' => '#dcfce7', 'fg' => '#166534'],
                                'failed' => ['label' => 'Failed', 'bg' => '#fee2e2', 'fg' => '#991b1b'],
                                default => ['label' => (string) $doc->indexing_status, 'bg' => '#e5e7eb', 'fg' => '#374151'],
                            };
                        @endphp
                        <tr class="border-b hover:bg-[#f9f9fb] transition-colors" style="border-color:rgba(0,0,0,.06);">
                            <td class="px-5 py-4">
                                <span class="font-mono text-xs" style="color:var(--sikds-ink)">{{ $doc->reference_number }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-sm" style="color:var(--sikds-ink)">{{ $doc->title }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold"
                                      style="background:{{ $badge['bg'] }};color:{{ $badge['fg'] }};">
                                    @if ($doc->indexing_status === 'processing')
                                        <span class="inline-block h-2 w-2 rounded-full animate-pulse" style="background:{{ $badge['fg'] }};"></span>
                                    @endif
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm" style="color:var(--sikds-ink)">{{ (int) $doc->chunks_count }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm" style="color:var(--sikds-ink)">{{ $doc->updated_at?->format('Y-m-d H:i') }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($doc->indexing_status === 'failed')
                                    <form method="POST" action="{{ route('indexing.retry', $doc) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="px-4 py-1.5 text-sm font-semibold text-white rounded-[10px]"
                                            style="background-color:var(--sikds-primary);"
                                        >
                                            Retry
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs" style="color:var(--sikds-muted)">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-sm" style="color:var(--sikds-muted)">
                                Aucun document à afficher.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t flex items-center justify-between" style="border-color:rgba(0,0,0,.1);">
            <p class="text-sm" style="color:var(--sikds-muted)">
                Affichage de {{ $documents->firstItem() ?? 0 }}&ndash;{{ $documents->lastItem() ?? 0 }} sur {{ number_format($documents->total()) }} documents
            </p>
            <div class="flex items-center gap-2">
                @if ($documents->onFirstPage())
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Précédent</span>
                @else
                    <a href="{{ $documents->previousPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Précédent</a>
                @endif
                @if ($documents->hasMorePages())
                    <a href="{{ $documents->nextPageUrl() }}" class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors" style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Suivant</a>
                @else
                    <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed" style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Suivant</span>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            try {
                setInterval(function () {
                    window.location.reload();
                }, 10000);
            } catch (e) {}
        })();
    </script>
</x-app-layout>

