<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use Carbon\Carbon;

class DashboardService
{
    public function getKpis(): array
    {
        return [
            [
                'icon'  => '/document.svg',
                'value' => number_format(Document::count()),
                'label' => 'Total Documents',
                'trend' => null,
            ],
            [
                'icon'  => '/upload-blue.svg',
                'value' => number_format(Document::where('created_at', '>=', now()->subDays(30))->count()),
                'label' => 'Téléversements Récents',
                'trend' => null,
            ],
            [
                'icon'  => '/people.svg',
                'value' => number_format(User::count()),
                'label' => 'Utilisateurs',
                'trend' => null,
            ],
            [
                'icon'  => '/building-blue.svg',
                'value' => number_format(DownloadLog::count()),
                'label' => 'Téléchargements',
                'trend' => null,
            ],
        ];
    }

    public function getStatusStats(): array
    {
        return [
            ['label' => 'Brouillon', 'value' => number_format(Document::where('status', 'draft')->count())],
            ['label' => 'Actif',     'value' => number_format(Document::where('status', 'active')->count())],
            ['label' => 'Archivé',   'value' => number_format(Document::where('status', 'archived')->count())],
            ['label' => 'Supprimé',  'value' => number_format(Document::where('status', 'soft_deleted')->count())],
        ];
    }

    public function getIndexingStats(): array
    {
        return [
            'indexed'    => Document::where('indexing_status', 'indexed')->count(),
            'processing' => Document::where('indexing_status', 'processing')->count(),
            'failed'     => Document::where('indexing_status', 'failed')->count(),
            'pending'    => Document::where('indexing_status', 'pending')->count(),
        ];
    }

    public function getAlerts(): array
    {
        $alerts = [];

        $failedCount = Document::where('indexing_status', 'failed')->count();
        if ($failedCount > 0) {
            $alerts[] = [
                'type'      => 'danger',
                'message'   => "Échec d'indexation pour {$failedCount} document" . ($failedCount > 1 ? 's' : ''),
                'timestamp' => null,
            ];
        }

        $expiringCount = Document::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();
        if ($expiringCount > 0) {
            $alerts[] = [
                'type'      => 'warning',
                'message'   => "{$expiringCount} document" . ($expiringCount > 1 ? 's expirent' : ' expire') . ' dans les 7 prochains jours',
                'timestamp' => null,
            ];
        }

        $processingCount = Document::where('indexing_status', 'processing')->count();
        if ($processingCount > 0) {
            $alerts[] = [
                'type'      => 'info',
                'message'   => "{$processingCount} document" . ($processingCount > 1 ? 's en cours' : ' en cours') . " d'indexation",
                'timestamp' => null,
            ];
        }

        return $alerts;
    }

    public function getRecentActivities(): array
    {
        return AuditLog::latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'icon'     => $this->iconForEvent($log->event_type),
                'user'     => $log->user_email ?? 'Système',
                'action'   => $this->labelForEvent($log->event_type),
                'document' => $this->documentLabelFromLog($log),
                'time'     => $log->created_at?->diffForHumans() ?? '—',
            ])
            ->all();
    }

    public function getActiveInstitutions(): array
    {
        return Institution::withCount('users')
            ->where('is_active', true)
            ->orderByDesc('users_count')
            ->limit(5)
            ->get()
            ->map(fn (Institution $i) => [
                'label' => $i->code ?? $i->name,
                'value' => $i->users_count . ' utilisateur' . ($i->users_count !== 1 ? 's' : ''),
            ])
            ->all();
    }

    public function getChartData(): array
    {
        $days = collect(range(6, 0))->map(fn (int $d) => now()->subDays($d)->toDateString());

        $downloads = DownloadLog::selectRaw('DATE(downloaded_at) as day, COUNT(*) as total')
            ->where('downloaded_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $uploads = Document::selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        return [
            'labels'    => $days->map(fn (string $d) => Carbon::parse($d)->locale('fr')->isoFormat('D MMM'))->values()->all(),
            'downloads' => $days->map(fn (string $d) => (int) ($downloads[$d] ?? 0))->values()->all(),
            'uploads'   => $days->map(fn (string $d) => (int) ($uploads[$d] ?? 0))->values()->all(),
        ];
    }

    private function iconForEvent(string $eventType): string
    {
        return match (true) {
            str_contains($eventType, 'upload') || str_contains($eventType, 'creat') => '/upload.svg',
            str_contains($eventType, 'download')                                     => '/download-black.svg',
            str_contains($eventType, 'updat') || str_contains($eventType, 'edit')   => '/edit-black.svg',
            str_contains($eventType, 'archiv') || str_contains($eventType, 'delet') => '/archive.svg',
            default                                                                   => '/document.svg',
        };
    }

    private function labelForEvent(string $eventType): string
    {
        return match (true) {
            str_contains($eventType, 'upload') || str_contains($eventType, 'creat') => 'a téléversé',
            str_contains($eventType, 'download')                                     => 'a téléchargé',
            str_contains($eventType, 'updat') || str_contains($eventType, 'edit')   => 'a modifié',
            str_contains($eventType, 'archiv')                                       => 'a archivé',
            str_contains($eventType, 'delet')                                        => 'a supprimé',
            str_contains($eventType, 'login')                                        => "s'est connecté",
            str_contains($eventType, 'logout')                                       => "s'est déconnecté",
            default                                                                   => 'a effectué une action',
        };
    }

    private function documentLabelFromLog(AuditLog $log): string
    {
        $meta = $log->metadata ?? [];
        $title = $meta['document_title'] ?? $meta['title'] ?? $meta['name'] ?? null;

        if ($title) {
            return '"' . $title . '"';
        }

        if ($log->resource_type && $log->resource_id) {
            return ucfirst((string) $log->resource_type) . ' #' . $log->resource_id;
        }

        return $log->resource_type ? ucfirst((string) $log->resource_type) : '—';
    }
}
