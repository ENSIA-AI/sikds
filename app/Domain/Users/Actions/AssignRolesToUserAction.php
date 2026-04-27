<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Assign roles to a user.
 * Replace all the user's current roles with the new set, recording who made the change.
 */
final class AssignRolesToUserAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(User $user, array $roleIds, int $assignedById): User
    {
        $previousRoleIds = $user->roles()->pluck('roles.id')->map(fn ($id): int => (int) $id)->all();
        $normalized = array_values(array_unique(array_map('intval', $roleIds)));

        $added   = array_values(array_diff($normalized, $previousRoleIds));
        $removed = array_values(array_diff($previousRoleIds, $normalized));

        $fresh = DB::transaction(function () use ($user, $normalized, $assignedById) {
            $syncData = [];
            foreach ($normalized as $roleId) {
                $syncData[$roleId] = [
                    'assigned_at' => now(),
                    'assigned_by' => $assignedById,
                ];
            }

            $user->roles()->sync($syncData);

            return $user->fresh('roles.permissions');
        });

        if ($added !== [] || $removed !== []) {
            $names = Role::query()
                ->whereIn('id', array_unique([...$added, ...$removed]))
                ->pluck('name', 'id')
                ->all();

            foreach ($added as $roleId) {
                $this->audit->record(
                    eventType: 'role.assigned',
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'role_id'          => $roleId,
                        'role_name'        => $names[$roleId] ?? null,
                        'assigned_by'      => $assignedById,
                    ],
                );
            }

            foreach ($removed as $roleId) {
                $this->audit->record(
                    eventType: 'role.removed',
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'role_id'          => $roleId,
                        'role_name'        => $names[$roleId] ?? null,
                        'removed_by'       => $assignedById,
                    ],
                );
            }
        }

        return $fresh;
    }
}
