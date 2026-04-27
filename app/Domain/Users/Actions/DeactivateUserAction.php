<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class DeactivateUserAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Mark a user as inactive. Does not remove roles or permissions.
     */
    public function execute(User $user, int $deactivatedById): void
    {
        if ($user->id === $deactivatedById) {
            throw new \Exception('Vous ne pouvez pas désactiver votre propre compte.');
        }

        $wasActive = (bool) $user->is_active;

        DB::transaction(function () use ($user): void {
            if (! $user->is_active) {
                return;
            }

            $user->forceFill(['is_active' => false])->save();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        });

        if ($wasActive) {
            $this->audit->record(
                eventType: 'user.deactivated',
                resourceType: 'user',
                resourceId: $user->id,
                metadata: [
                    'target_user_id'   => $user->id,
                    'target_user_name' => $user->full_name,
                    'target_email'     => $user->email,
                    'deactivated_by'   => $deactivatedById,
                ],
            );
        }
    }
}
