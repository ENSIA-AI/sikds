<?php

declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Notifications\Models\Notification;
use App\Domain\Users\Models\User;
use App\Mail\DocumentForwardedMail;
use App\Mail\DocumentPublishedMail;
use App\Mail\DocumentUpdatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        protected int $notificationId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        /** @var Notification|null $notification */
        $notification = Notification::with(['recipient', 'document'])->find($this->notificationId);
        if (! $notification) {
            return;
        }

        if ($notification->email_status === 'sent') {
            return;
        }

        $notification->email_status = 'pending';
        $notification->email_error = null;
        $notification->save();

        try {
            $recipient = $notification->recipient;
            $document = $notification->document;

            if (! $recipient) {
                throw new \RuntimeException(__('Destinataire introuvable.'));
            }
            if (! $document) {
                throw new \RuntimeException(__('Document introuvable.'));
            }

            $type = (string) $notification->type;
            $metadata = is_array($notification->metadata) ? $notification->metadata : [];
            /** @var array<int, array{name: string, category: ?string, color: ?string}> $tags */
            $tags = is_array($metadata['tags'] ?? null) ? $metadata['tags'] : [];

            $mailable = match ($type) {
                'document.published' => new DocumentPublishedMail($document, $recipient, $tags),
                'document.updated' => new DocumentUpdatedMail(
                    $document,
                    $recipient,
                    (int) ($metadata['new_version'] ?? $document->version_number),
                    (int) ($metadata['previous_version'] ?? max(1, ((int) $document->version_number) - 1)),
                    is_string($metadata['change_summary'] ?? null) ? $metadata['change_summary'] : null,
                    $tags,
                ),
                'document.forwarded' => new DocumentForwardedMail(
                    $document,
                    $recipient,
                    $this->resolveSender($metadata),
                ),
                default => null,
            };

            if (! $mailable) {
                throw new \RuntimeException(__('Type de notification non supporté: :type', ['type' => $type]));
            }

            // Honor the recipient's preferred locale if set; otherwise fall back to the
            // app default. Without ->locale(), queued mailables use whatever locale the
            // worker happens to have, which in a multilingual deployment is wrong.
            $recipientLocale = property_exists($recipient, 'locale') && is_string($recipient->locale)
                ? $recipient->locale
                : (string) config('app.locale');

            Mail::to($recipient->email)->locale($recipientLocale)->send($mailable);

            $notification->email_status = 'sent';
            $notification->email_sent_at = now();
            $notification->save();

            app(AuditService::class)->record(
                eventType: 'notification.sent',
                resourceType: 'notification',
                resourceId: $notification->id,
                metadata: [
                    'type' => $notification->type,
                    'recipient_user_id' => $notification->recipient_user_id,
                    'recipient_name' => $recipient->full_name,
                    'recipient_email' => $recipient->email,
                    'document_id' => $notification->document_id,
                    'document_reference' => $document->reference_number ?? null,
                    'document_title' => $document->title ?? null,
                ],
            );
        } catch (\Throwable $e) {
            $notification->email_status = 'failed';
            $notification->email_error = $e->getMessage();
            $notification->save();

            app(AuditService::class)->record(
                eventType: 'notification.failed',
                result: 'failed',
                resourceType: 'notification',
                resourceId: $notification->id,
                metadata: [
                    'type' => $notification->type,
                    'recipient_user_id' => $notification->recipient_user_id,
                    'recipient_name' => $recipient?->full_name,
                    'recipient_email' => $recipient?->email,
                    'document_id' => $notification->document_id,
                    'document_reference' => $document?->reference_number,
                    'document_title' => $document?->title,
                    'reason' => 'email_delivery_failed',
                    'message' => $e->getMessage(),
                ],
            );

            throw $e;
        }
    }

    /**
     * Hydrate the sender (forwarder) for the forwarded mail. Falls back to a
     * lightweight, non-persisted User instance if the original sender row has
     * been deleted, so the email still renders something meaningful.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function resolveSender(array $metadata): User
    {
        $senderId = isset($metadata['sender_user_id']) ? (int) $metadata['sender_user_id'] : 0;
        if ($senderId > 0) {
            $sender = User::query()->find($senderId);
            if ($sender !== null) {
                return $sender;
            }
        }

        $fallback = new User();
        $fallback->forceFill([
            'id' => $senderId,
            'full_name' => is_string($metadata['sender_full_name'] ?? null) ? $metadata['sender_full_name'] : '',
            'email' => is_string($metadata['sender_email'] ?? null) ? $metadata['sender_email'] : '',
        ]);

        return $fallback;
    }
}

