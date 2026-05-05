<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services\Api;

use App\Domain\Documents\Models\Document;
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

    /**
     * Enforce institution scope on mutating document actions.
     *
     * Users with `document.view.all` (typically Super Admin) bypass this check.
     * All other users may only act on documents uploaded by their own institution,
     * unless the document was uploaded by themselves.
     */
    public function assertInstitutionScope(User $user, Document $document): void
    {
        // Super Admins / full-view users are not restricted by institution.
        if ($this->canUseViewAll($user)) {
            return;
        }

        // The uploader themselves can always act on their own document.
        if ((int) $document->uploaded_by === (int) $user->id) {
            return;
        }

        // Load uploader institution if not already eager-loaded.
        $document->loadMissing('uploader');
        $uploaderInstitutionId = $document->uploader?->institution_id;

        if ($uploaderInstitutionId === null || (int) $uploaderInstitutionId !== (int) $user->institution_id) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Vous ne pouvez modifier que des documents de votre institution.'
            );
        }
    }
}

