<?php

declare(strict_types=1);

namespace App\Domain\Users\Enums;

enum SsoFailureReason: string
{
    case InvalidCallback = 'invalid_callback';
    case UnauthorizedEmailDomain = 'unauthorized_email_domain';
    case UnauthorizedSsoRole = 'unauthorized_sso_role';
    case DeactivatedAccount = 'deactivated_account';

    public function translationKey(): string
    {
        return match ($this) {
            self::InvalidCallback => 'La réponse SSO est invalide ou a expiré. Veuillez réessayer.',
            self::UnauthorizedEmailDomain => 'Votre domaine email n’est pas autorisé pour accéder à ce système.',
            self::UnauthorizedSsoRole => 'Accès non autorisé. Votre compte SSO ne possède pas l’un des rôles requis pour ce système.',
            self::DeactivatedAccount => 'Votre compte est désactivé. Veuillez contacter un administrateur.',
        };
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
