<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class SyncUserPermissionsAction
{
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

        return DB::transaction(function () use ($user, $data, $assignedById, $roles, $customPermissions) {
            // Step 1: Sync roles
            $roleSyncData = [];
            foreach ($data['role_ids'] as $roleId) {
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