<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Notifications\Services\UserNotificationService;
use App\Domain\Users\Models\User;

class UserDashboardService
{
    public function __construct(private readonly UserNotificationService $notifications) {}

    /**
     * Decide whether the authenticated user should see the simplified dashboard.
     * Regular users = those without administrative permissions.
     */
    public function shouldShowSimpleDashboard(User $user): bool
    {
        $adminPermissions = [
            'user.view.all',
            'user.manage',
            'role.view',
            'institution.view',
            'audit.view',
            'tag.manage',
            'indexing.manage',
        ];

        foreach ($adminPermissions as $permission) {
            if ($user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array{icon: string, value: string, label: string}>
     */
    public function statsForUser(User $user): array
    {
        $base = Document::query()->visibleTo($user);

        $available = (clone $base)
            ->where('status', 'active')
            ->count();

        $recent = (clone $base)
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            [
                'icon' => '/document-blue.svg',
                'value' => number_format($available),
                'label' => 'Documents disponibles',
            ],
            [
                'icon' => '/time-dark-blue.svg',
                'value' => number_format($recent),
                'label' => 'Nouveaux documents',
            ],
        ];
    }

    /**
     * @return array<int, array{id: int, title: string, reference: ?string, updated_at: ?string, time: ?string}>
     */
    public function recentDocumentsForUser(User $user, int $limit = 5): array
    {
        return Document::query()
            ->visibleTo($user)
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'title', 'reference_number', 'updated_at', 'created_at'])
            ->map(fn (Document $doc): array => [
                'id' => (int) $doc->id,
                'title' => (string) $doc->title,
                'reference' => $doc->reference_number ? (string) $doc->reference_number : null,
                'updated_at' => $doc->updated_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'time' => $doc->updated_at?->locale('fr')->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Recent personal downloads as proxy for "Chats Récents" until real chats exist.
     *
     * @return array<int, array{title: string, time: string}>
     */
    public function recentDownloadsForUser(User $user, int $limit = 5): array
    {
        return DownloadLog::query()
            ->with(['document:id,title,reference_number'])
            ->where('user_id', $user->id)
            ->orderByDesc('downloaded_at')
            ->limit($limit)
            ->get(['id', 'document_id', 'downloaded_at'])
            ->map(function (DownloadLog $log): array {
                $document = $log->document;

                return [
                    'title' => $document?->title ?? 'Document',
                    'reference' => $document?->reference_number,
                    'document_id' => $document?->id,
                    'time' => $log->downloaded_at
                        ? \Illuminate\Support\Carbon::parse($log->downloaded_at)->locale('fr')->diffForHumans()
                        : '—',
                ];
            })
            ->all();
    }
}
