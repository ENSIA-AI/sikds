<?php

declare(strict_types=1);

namespace App\Domain\Users\Exceptions;

use App\Domain\Users\Enums\SsoFailureReason;
use RuntimeException;
use Throwable;

class SsoAuthenticationException extends RuntimeException
{
    public function __construct(
        string $message = 'Unable to complete SSO authentication.',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly ?SsoFailureReason $failureReason = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function forReason(
        SsoFailureReason $failureReason,
        string $message = 'Unable to complete SSO authentication.',
        ?Throwable $previous = null
    ): self {
        return new self($message, previous: $previous, failureReason: $failureReason);
    }

    public function failureReason(): ?SsoFailureReason
    {
        return $this->failureReason;
    }

    public function auditReason(): string
    {
        return $this->failureReason?->value ?? 'sso_authentication_failed';
    }

    public function publicMessageKey(): string
    {
        return $this->failureReason?->translationKey()
            ?? 'La connexion SSO a échoué. Veuillez réessayer ou contacter le support.';
    }

    public function shouldReport(): bool
    {
        return $this->failureReason?->shouldReport() ?? true;
    }
}

