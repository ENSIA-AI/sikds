<?php

declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Notifications\Models\Notification;
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
                default => null,
            };

            if (! $mailable) {
                throw new \RuntimeException(__('Type de notification non supporté: :type', ['type' => $type]));
            }

            Mail::to($recipient->email)->send($mailable);

            $notification->email_status = 'sent';
            $notification->email_sent_at = now();
            $notification->save();

            AuditLog::query()->create([
                'event_type' => 'notification.sent',
                'user_id' => $recipient->id,
                'user_email' => $recipient->email,
                'resource_type' => 'notification',
                'resource_id' => $notification->id,
                'metadata' => [
                    'type' => $notification->type,
                    'recipient_user_id' => $notification->recipient_user_id,
                    'recipient_name' => $recipient->full_name,
                    'document_id' => $notification->document_id,
                    'document_reference' => $document->reference_number ?? null,
                ],
                'result' => 'success',
                'ip_address' => null,
                'user_agent' => null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $notification->email_status = 'failed';
            $notification->email_error = $e->getMessage();
            $notification->save();

            AuditLog::query()->create([
                'event_type' => 'notification.failed',
                'user_id' => $recipient?->id,
                'user_email' => $recipient?->email,
                'resource_type' => 'notification',
                'resource_id' => $notification->id,
                'metadata' => [
                    'type' => $notification->type,
                    'recipient_user_id' => $notification->recipient_user_id,
                    'recipient_name' => $recipient?->full_name,
                    'document_id' => $notification->document_id,
                    'document_reference' => $document?->reference_number,
                    'error' => $e->getMessage(),
                ],
                'result' => 'failed',
                'ip_address' => null,
                'user_agent' => null,
                'created_at' => now(),
            ]);

            throw $e;
        }
    }
}

