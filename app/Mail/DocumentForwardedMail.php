<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Documents\Models\Document;
use App\Domain\Users\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentForwardedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $supportContact;

    public function __construct(
        public readonly Document $document,
        public readonly User $recipient,
        public readonly User $sender,
        ?string $supportContact = null,
    ) {
        $this->supportContact = $supportContact
            ?? (string) app(SystemSettingsService::class)->get('notifications.support_contact_email', 'support@mesrs.dz');
    }

    public function build(): self
    {
        $senderName = $this->sender->full_name ?: $this->sender->email;

        return $this
            ->subject(__('Document partagé par :sender — :reference', [
                'sender' => $senderName,
                'reference' => $this->document->reference_number,
            ]))
            ->view('emails.document-forwarded')
            ->text('emails.document-forwarded-text');
    }
}
