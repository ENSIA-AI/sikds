<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Delete a role.
 * - Block deletion of system roles
 * - Block deletion if any users are still assigned to the role
 */
final class DeleteRoleAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(Role $role): bool
    {
        if ($role->is_system_role) {
            $this->audit->record(
                eventType: 'role.deleted',
                result: 'failed',
                resourceType: 'role',
                resourceId: $role->id,
                metadata: [
                    'role_id'   => $role->id,
                    'role_name' => $role->name,
                    'reason'    => 'system_role',
                    'message'   => 'Les rôles système ne peuvent pas être supprimés.',
                ],
            );
            throw new \Exception('Les rôles système ne peuvent pas être supprimés.');
        }

        $assignedUsers = $role->users()->count();
        if ($assignedUsers > 0) {
            $this->audit->record(
                eventType: 'role.deleted',
                result: 'failed',
                resourceType: 'role',
                resourceId: $role->id,
                metadata: [
                    'role_id'        => $role->id,
                    'role_name'      => $role->name,
                    'reason'         => 'role_in_use',
                    'message'        => "Impossible de supprimer ce rôle : il est attribué à {$assignedUsers} utilisateur(s).",
                    'assigned_users' => $assignedUsers,
                ],
            );
            throw new \Exception("Impossible de supprimer ce rôle : il est attribué à {$assignedUsers} utilisateur(s). Retirez d'abord toutes les attributions.");
        }

        $snapshot = [
            'role_id'   => $role->id,
            'role_name' => $role->name,
            'role_slug' => $role->slug,
        ];
        $roleId = $role->id;

        $deleted = DB::transaction(function () use ($role) {
            $role->permissions()->detach();

            return $role->delete();
        });

        $this->audit->record(
            eventType: 'role.deleted',
            resourceType: 'role',
            resourceId: $roleId,
            metadata: [
                ...$snapshot,
                'before' => $snapshot,
                'after' => [],
            ],
        );

        return (bool) $deleted;
    }
}
