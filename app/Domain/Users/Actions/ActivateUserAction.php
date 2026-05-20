<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class ActivateUserAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Reactivate a previously deactivated user.
     */
    public function execute(User $user): User
    {
        $wasActive = (bool) $user->is_active;

        $fresh = DB::transaction(function () use ($user): User {
            $user->forceFill(['is_active' => true])->save();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $user->fresh(['institution', 'roles']);
        });

        if (! $wasActive) {
            $this->audit->record(
                eventType: 'user.activated',
                resourceType: 'user',
                resourceId: $fresh->id,
                metadata: [
                    'target_user_id'   => $fresh->id,
                    'target_user_name' => $fresh->full_name,
                    'target_email'     => $fresh->email,
                    'before'           => ['is_active' => false],
                    'after'            => ['is_active' => true],
                ],
            );
        }

        return $fresh;
    }
}
