<?php

declare(strict_types=1);

namespace App\Domain\Users\Exceptions;

use RuntimeException;
use Throwable;

class SsoAuthenticationException extends RuntimeException
{
    public function __construct(
        string $message = 'Unable to complete SSO authentication.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

