<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class SyncUserPermissionsAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Synchronize user's roles and custom permissions.
     *
     * Users will have:
     * - All permissions from assigned roles (automatic)
     * - Custom permissions assigned directly (manual additions)
     */
    public function execute(User $user, array $data, int $assignedById): User
    {
        // Validate at least one role
        if (empty($data['role_ids'])) {
            throw new \Exception('L\'utilisateur doit avoir au moins un rôle.');
        }

        // Validate roles exist
        $roles = Role::whereIn('id', $data['role_ids'])->get();
        if ($roles->count() !== count($data['role_ids'])) {
            throw new \Exception('Un ou plusieurs rôles sélectionnés sont invalides.');
        }

        // Validate permissions exist (if provided)
        $customPermissions = [];
        if (!empty($data['permission_ids'])) {
            $customPermissions = Permission::whereIn('id', $data['permission_ids'])->get();
            if ($customPermissions->count() !== count($data['permission_ids'])) {
                throw new \Exception('Une ou plusieurs permissions sélectionnées sont invalides.');
            }
        }

        $previousRoleIds = $user->roles()->pluck('roles.id')->map(fn ($id): int => (int) $id)->all();
        $previousPermissionIds = $user->getDirectPermissions()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $normalizedRoleIds = array_values(array_unique(array_map('intval', $data['role_ids'])));
        $normalizedPermissionIds = array_values(array_unique(array_map('intval', $data['permission_ids'] ?? [])));

        $fresh = DB::transaction(function () use ($user, $normalizedRoleIds, $assignedById, $customPermissions) {
            // Step 1: Sync roles
            $roleSyncData = [];
            foreach ($normalizedRoleIds as $roleId) {
                $roleSyncData[$roleId] = [
                    'assigned_at' => now(),
                    'assigned_by' => $assignedById,
                ];
            }
            $user->roles()->sync($roleSyncData);

            // Step 2: Sync custom (direct) permissions
            // Note: syncPermissions() is a Spatie method that handles direct permissions
            $user->syncPermissions($customPermissions);

            // Clear permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $user->fresh('roles.permissions');
        });

        $rolesAdded   = array_values(array_diff($normalizedRoleIds, $previousRoleIds));
        $rolesRemoved = array_values(array_diff($previousRoleIds, $normalizedRoleIds));
        $permsAdded   = array_values(array_diff($normalizedPermissionIds, $previousPermissionIds));
        $permsRemoved = array_values(array_diff($previousPermissionIds, $normalizedPermissionIds));

        if ($rolesAdded !== [] || $rolesRemoved !== []) {
            $roleNames = Role::query()
                ->whereIn('id', array_unique([...$rolesAdded, ...$rolesRemoved]))
                ->pluck('name', 'id')
                ->all();

            foreach ($rolesAdded as $roleId) {
                $this->audit->record(
                    eventType: 'role.assigned',
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'role_id'          => $roleId,
                        'role_name'        => $roleNames[$roleId] ?? null,
                        'assigned_by'      => $assignedById,
                    ],
                );
            }

            foreach ($rolesRemoved as $roleId) {
                $this->audit->record(
                    eventType: 'role.removed',
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'role_id'          => $roleId,
                        'role_name'        => $roleNames[$roleId] ?? null,
                        'removed_by'       => $assignedById,
                    ],
                );
            }
        }

        if ($permsAdded !== [] || $permsRemoved !== []) {
            $permissionMeta = Permission::query()
                ->whereIn('id', array_unique([...$permsAdded, ...$permsRemoved]))
                ->get(['id', 'name', 'code'])
                ->keyBy('id');

            foreach ($permsAdded as $permId) {
                $perm = $permissionMeta->get($permId);
                $this->audit->record(
                    eventType: 'permission.assigned',
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'permission_id'    => $permId,
                        'permission_code'  => $perm?->code,
                        'permission_name'  => $perm?->name,
                        'assigned_by'      => $assignedById,
                    ],
                );
            }

            foreach ($permsRemoved as $permId) {
                $perm = $permissionMeta->get($permId);
                $this->audit->record(
                    eventType: 'permission.removed',
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'permission_id'    => $permId,
                        'permission_code'  => $perm?->code,
                        'permission_name'  => $perm?->name,
                        'removed_by'       => $assignedById,
                    ],
                );
            }

            $this->audit->record(
                eventType: 'permissions.changed',
                resourceType: 'user',
                resourceId: $user->id,
                metadata: [
                    'target_user_id'    => $user->id,
                    'target_user_name'  => $user->full_name,
                    'added_permission_ids'   => $permsAdded,
                    'removed_permission_ids' => $permsRemoved,
                    'assigned_by'       => $assignedById,
                ],
            );
        }

        return $fresh;
    }

    /**
     * Get permission IDs that are redundant (already covered by roles).
     * Useful for UI to show which custom permissions are unnecessary.
     *
     * @param User $user
     * @param array<int> $customPermissionIds
     * @return array<int>
     */
    public function getRedundantPermissions(User $user, array $customPermissionIds): array
    {
        // Get all permission IDs from user's roles
        $rolePermissionIds = $user->getPermissionsViaRoles()->pluck('id')->toArray();

        // Find custom permissions that are already in roles
        return array_values(array_intersect($customPermissionIds, $rolePermissionIds));
    }
}
