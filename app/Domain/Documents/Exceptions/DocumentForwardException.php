<?php

declare(strict_types=1);

namespace App\Domain\Documents\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Domain-level failure when forwarding a document to another user.
 *
 * Carries an HTTP-friendly status code so the controller can translate it to
 * a JSON response without leaking internal stack traces to the client.
 */
class DocumentForwardException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
    ) {
        parent::__construct($message);
    }

    public static function notViewable(): self
    {
        return new self(
            'Vous n’avez pas accès à ce document et ne pouvez pas le transférer.',
            Response::HTTP_FORBIDDEN,
        );
    }

    public static function missingPermission(): self
    {
        return new self(
            'Permission document.forward requise pour transférer un document.',
            Response::HTTP_FORBIDDEN,
        );
    }

    public static function notForwardable(string $status): self
    {
        return new self(
            "Seuls les documents actifs peuvent être transférés (état actuel : {$status}).",
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function recipientNotFound(): self
    {
        return new self(
            'Utilisateur destinataire introuvable.',
            Response::HTTP_NOT_FOUND,
        );
    }

    public static function recipientInactive(): self
    {
        return new self(
            'Le destinataire est désactivé et ne peut pas recevoir de documents.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function forwardToSelf(): self
    {
        return new self(
            'Vous ne pouvez pas vous transférer un document à vous-même.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
