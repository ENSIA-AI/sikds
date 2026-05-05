<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Domain\Documents\Models\Document;
use App\Domain\Notifications\Models\Notification;
use App\Domain\Users\Models\User;
use App\Jobs\Notifications\SendNotificationEmailJob;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DocumentNotificationService
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    /**
     * Create notifications rows and queue email sending.
     * Types used match SRS: document.published, document.updated.
     */
    public function notifyDocumentPublished(Document $document): int
    {
        if (! (bool) $this->settings->get('notifications.document_published_enabled', true)) {
            return 0;
        }

        return $this->createAndDispatch($document, 'document.published', [
            'reference_number' => $document->reference_number,
            'version_number' => $document->version_number,
            'description' => $document->description,
            'tags' => $this->tagPayload($document),
        ]);
    }

    public function notifyDocumentUpdated(Document $document, int $newVersion, int $previousVersion, ?string $changeSummary = null): int
    {
        if (! (bool) $this->settings->get('notifications.document_updated_enabled', true)) {
            return 0;
        }

        return $this->createAndDispatch($document, 'document.updated', [
            'reference_number' => $document->reference_number,
            'previous_version' => $previousVersion,
            'new_version' => $newVersion,
            'description' => $document->description,
            'change_summary' => $changeSummary,
            'tags' => $this->tagPayload($document),
        ]);
    }

    /**
     * Create the single-recipient notification for a forwarded document and queue
     * its email. Always creates the row (even when the email channel is disabled
     * via system settings) so the recipient sees the in-app notification — only
     * the SMTP step is gated by `notifications.document_forwarded_enabled`.
     */
    public function notifyDocumentForwarded(Document $document, User $recipient, User $sender): Notification
    {
        $emailEnabled = (bool) $this->settings->get('notifications.document_forwarded_enabled', true);

        $notification = Notification::query()->create([
            'type' => 'document.forwarded',
            'recipient_user_id' => $recipient->id,
            'document_id' => $document->id,
            'email_sent_at' => null,
            'email_status' => $emailEnabled ? 'pending' : 'skipped',
            'email_error' => null,
            'metadata' => [
                'reference_number' => $document->reference_number,
                'document_title' => $document->title,
                'description' => $document->description,
                'sender_user_id' => $sender->id,
                'sender_full_name' => $sender->full_name,
                'sender_email' => $sender->email,
                'tags' => $this->tagPayload($document),
            ],
            'created_at' => now(),
        ]);

        if ($emailEnabled && $recipient->email !== null && $recipient->email !== '') {
            SendNotificationEmailJob::dispatch($notification->id)->onQueue('notifications');
        }

        return $notification;
    }

    /**
     * @return array<int, array{name: string, category: ?string, color: ?string}>
     */
    private function tagPayload(Document $document): array
    {
        return $document->tags()->get(['tags.id', 'name', 'category', 'color'])
            ->map(fn ($tag): array => [
                'name' => (string) $tag->name,
                'category' => $tag->category ? (string) $tag->category : null,
                'color' => $tag->color ? (string) $tag->color : null,
            ])
            ->all();
    }

    private function createAndDispatch(Document $document, string $type, array $metadata): int
    {
        $recipients = $this->recipientsForDocument($document);
        $count = 0;

        $recipients->chunk(500, function (Collection $users) use ($document, $type, $metadata, &$count): void {
            foreach ($users as $user) {
                /** @var User $user */
                $notification = Notification::query()->create([
                    'type' => $type,
                    'recipient_user_id' => $user->id,
                    'document_id' => $document->id,
                    'email_sent_at' => null,
                    'email_status' => 'pending',
                    'email_error' => null,
                    'metadata' => $metadata,
                    'created_at' => now(),
                ]);

                SendNotificationEmailJob::dispatch($notification->id)->onQueue('notifications');
                $count++;
            }
        });

        return $count;
    }

    /**
     * Select active recipients in the target audience.
     * This intentionally mirrors distribution visibility rules at a practical level.
     */
    private function recipientsForDocument(Document $document)
    {
        $users = User::query()->active()->whereNotNull('email');

        // Always include direct targets (if any) in addition to audience.
        $directTargetUserIds = DB::table('document_user_targets')
            ->where('document_id', $document->id)
            ->pluck('user_id')
            ->map(fn ($v): int => (int) $v)
            ->all();

        if ($document->target_audience === 'all') {
            return $users->when($directTargetUserIds !== [], function ($q) use ($directTargetUserIds) {
                $q->orWhereIn('id', $directTargetUserIds);
            });
        }

        if ($document->target_audience === 'specific_institutions') {
            $institutionIds = DB::table('document_institution_targets')
                ->where('document_id', $document->id)
                ->pluck('institution_id')
                ->map(fn ($v): int => (int) $v)
                ->all();

            return $users->where(function ($q) use ($institutionIds, $directTargetUserIds): void {
                if ($institutionIds !== []) {
                    $q->whereIn('institution_id', $institutionIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
                if ($directTargetUserIds !== []) {
                    $q->orWhereIn('id', $directTargetUserIds);
                }
            });
        }

        if ($document->target_audience === 'specific_roles') {
            $roleIds = DB::table('document_role_targets')
                ->where('document_id', $document->id)
                ->pluck('role_id')
                ->map(fn ($v): int => (int) $v)
                ->all();

            $roleTable = config('permission.table_names.model_has_roles', 'model_has_roles');

            return $users->where(function ($q) use ($roleIds, $roleTable, $directTargetUserIds): void {
                if ($roleIds !== []) {
                    $q->whereExists(function ($sub) use ($roleIds, $roleTable): void {
                        $sub->selectRaw('1')
                            ->from($roleTable)
                            ->whereColumn($roleTable.'.model_id', 'users.id')
                            ->where($roleTable.'.model_type', '=', addslashes(User::class))
                            ->whereIn($roleTable.'.role_id', $roleIds);
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
                if ($directTargetUserIds !== []) {
                    $q->orWhereIn('id', $directTargetUserIds);
                }
            });
        }

        // Default: only direct targets.
        return $users->whereIn('id', $directTargetUserIds ?: [-1]);
    }
}

