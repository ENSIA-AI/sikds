<?php

declare(strict_types=1);

namespace App\Domain\Users\Exceptions;

use RuntimeException;
use Throwable;

class SsoAuthenticationException extends RuntimeException
{
    public const UNAUTHORIZED_SSO_ROLE = 40301;

    public function __construct(
        string $message = 'Unable to complete SSO authentication.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function isUnauthorizedSsoRole(): bool
    {
        return $this->getCode() === self::UNAUTHORIZED_SSO_ROLE;
    }
}

