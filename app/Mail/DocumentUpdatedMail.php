<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Documents\Models\Document;
use App\Domain\Users\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array<int, array{name: string, category: ?string, color: ?string}> */
    public array $documentTags;

    public string $supportContact;

    /**
     * @param  array<int, array{name: string, category: ?string, color: ?string}>  $documentTags
     */
    public function __construct(
        public readonly Document $document,
        public readonly User $recipient,
        public readonly int $newVersion,
        public readonly int $previousVersion,
        public readonly ?string $changeSummary = null,
        array $documentTags = [],
        ?string $supportContact = null,
    ) {
        $this->documentTags = $documentTags;
        $this->supportContact = $supportContact ?? (string) app(SystemSettingsService::class)->get('notifications.support_contact_email', 'support@mesrs.dz');
    }

    public function build(): self
    {
        return $this
            ->subject("Document mis à jour — {$this->document->reference_number} (v{$this->previousVersion} → v{$this->newVersion})")
            ->view('emails.document-updated')
            ->text('emails.document-updated-text');
    }
}
