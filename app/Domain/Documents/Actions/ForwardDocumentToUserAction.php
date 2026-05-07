<?php

declare(strict_types=1);

namespace App\Domain\Documents\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Documents\Exceptions\DocumentForwardException;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\Api\DocumentApiAuthorizationService;
use App\Domain\Documents\Models\DocumentUserTarget;
use App\Domain\Notifications\Models\Notification;
use App\Domain\Users\Models\User;
use App\Services\Notifications\DocumentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Forward (share) an existing document to another active user.
 *
 * Granting access uses the pre-existing `document_user_targets` pivot which the
 * `Document::scopeVisibleTo()` query already honours, so the recipient gains
 * read/download access through the regular permission-aware pipeline without
 * needing dedicated branches in queries elsewhere in the app.
 */
final class ForwardDocumentToUserAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly DocumentNotificationService $notifications,
        private readonly DocumentApiAuthorizationService $documentAuthorization,
    ) {}

    /**
     * @return array{
     *     target: DocumentUserTarget,
     *     notification: Notification,
     *     was_already_targeted: bool,
     * }
     *
     * @throws DocumentForwardException
     */
    public function execute(
        User $actor,
        Document $document,
        int $recipientId,
        ?Request $request = null,
    ): array {
        $this->guardActor($actor, $document);

        if ($document->trashed() || $document->status === 'soft_deleted') {
            throw DocumentForwardException::notForwardable('soft_deleted');
        }

        if ($document->status !== 'active') {
            throw DocumentForwardException::notForwardable((string) $document->status);
        }

        $recipient = $this->resolveRecipient($actor, $recipientId);

        return DB::transaction(function () use ($actor, $document, $recipient, $request): array {
            $existing = DocumentUserTarget::query()
                ->where('document_id', $document->id)
                ->where('user_id', $recipient->id)
                ->lockForUpdate()
                ->first();

            $wasAlreadyTargeted = $existing !== null;

            $target = $existing ?? DocumentUserTarget::query()->create([
                'document_id' => $document->id,
                'user_id' => $recipient->id,
                'assigned_by' => $actor->id,
                'created_at' => now(),
            ]);

            $notification = $this->notifications->notifyDocumentForwarded($document, $recipient, $actor);

            $this->audit->record(
                eventType: 'document.forwarded',
                user: $actor,
                resourceType: 'document',
                resourceId: $document->id,
                metadata: [
                    'document_id' => $document->id,
                    'reference_number' => $document->reference_number,
                    'document_title' => $document->title,
                    'recipient_user_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'recipient_name' => $recipient->full_name,
                    'sender_user_id' => $actor->id,
                    'sender_email' => $actor->email,
                    'was_already_targeted' => $wasAlreadyTargeted,
                    'notification_id' => $notification->id,
                ],
                request: $request,
            );

            return [
                'target' => $target,
                'notification' => $notification,
                'was_already_targeted' => $wasAlreadyTargeted,
            ];
        });
    }

    private function guardActor(User $actor, Document $document): void
    {
        if (! $actor->can('document.forward')) {
            throw DocumentForwardException::missingPermission();
        }

        if (! $document->isAccessibleBy($actor)) {
            throw DocumentForwardException::notViewable();
        }

        if (! $this->documentAuthorization->isInstitutionScopedActionAllowed($actor, $document)) {
            throw DocumentForwardException::institutionScopeDenied();
        }
    }

    private function resolveRecipient(User $actor, int $recipientId): User
    {
        if ($recipientId <= 0) {
            throw DocumentForwardException::recipientNotFound();
        }

        if ($recipientId === $actor->id) {
            throw DocumentForwardException::forwardToSelf();
        }

        $recipient = User::query()->find($recipientId);
        if ($recipient === null) {
            throw DocumentForwardException::recipientNotFound();
        }

        if (! (bool) $recipient->is_active) {
            throw DocumentForwardException::recipientInactive();
        }

        return $recipient;
    }
}
