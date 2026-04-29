<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services\Api;

use App\Domain\Users\Models\User;
use Symfony\Component\HttpFoundation\Response;

class DocumentApiAuthorizationService
{
    public function canUseViewAll(User $user): bool
    {
        return $user->can('document.view.all');
    }

    public function assertCanList(User $user): void
    {
        if (
            $this->canUseViewAll($user)
            || $user->can('document.view.own_institution')
            || $user->can('document.view.assigned')
        ) {
            return;
        }

        abort(Response::HTTP_FORBIDDEN, 'Permission insuffisante pour la liste des documents.');
    }

    public function assertCanPreview(User $user): void
    {
        if (! $this->canUseViewAll($user)) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Permission document.view.all requise pour la prévisualisation.'
            );
        }
    }

    public function assertPermission(User $user, string $permission): void
    {
        abort_if(! $user->can($permission), Response::HTTP_FORBIDDEN, "Permission {$permission} requise.");
    }

    public function assertCanRestore(User $user): void
    {
        $this->assertPermission($user, 'document.restore');
    }
}

