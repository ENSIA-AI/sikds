<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Models\Notification;
use App\Domain\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class UserNotificationService
{
    public const TYPE_LABELS = [
        'document.published' => 'Nouveau document publié',
        'document.updated' => 'Document mis à jour',
    ];

    /**
     * @return Collection<int, Notification>
     */
    public function latestForUser(User $user, int $limit = 5): Collection
    {
        return Notification::query()
            ->with('document:id,title,reference_number')
            ->where('recipient_user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function unreadCountForUser(User $user): int
    {
        return Notification::query()
            ->where('recipient_user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::query()
            ->with('document:id,title,reference_number')
            ->where('recipient_user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function markAsRead(Notification $notification): void
    {
        if ($notification->read_at !== null) {
            return;
        }

        $notification->forceFill(['read_at' => Carbon::now()])->save();
    }

    public function markAllAsReadForUser(User $user): int
    {
        return Notification::query()
            ->where('recipient_user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function buildMessage(Notification $notification): string
    {
        $documentTitle = $notification->document?->title ?? ($notification->metadata['document_title'] ?? null);
        $titleSegment = $documentTitle ? " : « {$documentTitle} »" : '';

        return match ($notification->type) {
            'document.published' => 'Un nouveau document a été publié' . $titleSegment . '.',
            'document.updated' => 'Un document a été mis à jour' . $titleSegment . '.',
            default => self::TYPE_LABELS[$notification->type] ?? $notification->type,
        };
    }

    public function shortLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? $type;
    }
}
