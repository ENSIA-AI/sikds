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

    /**
     * SRS §3.1 / §7.2: soft-deleted documents are reviewed and restored only by the seeded
     * system Super Administrateur — not by other users who hold document.restore (peers included).
     * Route middleware still requires can:document.restore; super admins typically satisfy it via
     * RolesSeeder (all permissions). AuthServiceProvider Gate::before is optional (not in
     * bootstrap/providers.php), so the permission must exist on the role for middleware to pass.
     */
    public function assertCanRestore(User $user): void
    {
        if (! $user->hasRole('Super Administrateur')) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Seul un super administrateur peut restaurer un document supprimé.'
            );
        }
    }

    /**
     * Whether the user may perform institution-scoped mutations (edit, publish,
     * archive, delete, forward, restore) on this document apart from permission checks.
     *
     * Restore also calls this after {@see assertCanRestore}; actors without
     * `document.view.all` must be the uploader or share the uploader’s institution.
     */
    public function isInstitutionScopedActionAllowed(User $user, Document $document): bool
    {
        if ($this->canUseViewAll($user)) {
            return true;
        }

        if ((int) $document->uploaded_by === (int) $user->id) {
            return true;
        }

        $document->loadMissing('uploader');
        $uploaderInstitutionId = $document->uploader?->institution_id;

        return $uploaderInstitutionId !== null
            && (int) $uploaderInstitutionId === (int) $user->institution_id;
    }

    public function assertInstitutionScope(User $user, Document $document): void
    {
        if (! $this->isInstitutionScopedActionAllowed($user, $document)) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Vous ne pouvez modifier que des documents de votre institution.'
            );
        }
    }
}

