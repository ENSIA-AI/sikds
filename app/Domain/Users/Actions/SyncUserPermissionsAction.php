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
        // Roles are OPTIONAL — admin may sync with an empty role set,
        // which means "user has no role; only direct permissions apply".
        $requestedRoleIds = array_values(array_map('intval', $data['role_ids'] ?? []));

        $roles = $requestedRoleIds !== []
            ? Role::whereIn('id', $requestedRoleIds)->with('permissions:id,code')->get()
            : collect();

        if ($requestedRoleIds !== [] && $roles->count() !== count($requestedRoleIds)) {
            throw new \Exception('Un ou plusieurs rôles sélectionnés sont invalides.');
        }

        // Validate permissions exist (if provided)
        $customPermissions = collect();
        if (! empty($data['permission_ids'])) {
            $customPermissions = Permission::whereIn('id', $data['permission_ids'])->get();
            if ($customPermissions->count() !== count($data['permission_ids'])) {
                throw new \Exception('Une ou plusieurs permissions sélectionnées sont invalides.');
            }
        }

        $previousRoleIds = $user->roles()->pluck('roles.id')->map(fn ($id): int => (int) $id)->all();
        $previousPermissionIds = $user->getDirectPermissions()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $normalizedRoleIds = array_values(array_unique(array_map('intval', $data['role_ids'])));
        $normalizedPermissionIds = array_values(array_unique(array_map('intval', $data['permission_ids'] ?? [])));

        // Permission dependencies (auto-required)
        // Example: document.create implies tag.assign.
        $rolePermissionCodes = $roles
            ->pluck('permissions')
            ->flatten()
            ->pluck('code')
            ->filter()
            ->map(fn ($c) => (string) $c)
            ->all();
        $effectiveCodes = array_values(array_unique([
            ...$rolePermissionCodes,
            ...$customPermissions->pluck('code')->filter()->map(fn ($c) => (string) $c)->all(),
        ]));
        $requiredCodes = [];
        if (in_array('document.create', $effectiveCodes, true)) {
            $requiredCodes[] = 'tag.assign';
        }
        $requiredCodes = array_values(array_unique($requiredCodes));
        if ($requiredCodes !== []) {
            $missingCodes = array_values(array_diff($requiredCodes, $effectiveCodes));
            if ($missingCodes !== []) {
                $extra = Permission::query()->whereIn('code', $missingCodes)->get();
                $customPermissions = $customPermissions->concat($extra)->unique('id')->values();
                $normalizedPermissionIds = $customPermissions->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
            }
        }

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
                    user: $assignedById,
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'target_email'     => $user->email,
                        'role_id'          => $roleId,
                        'role_name'        => $roleNames[$roleId] ?? null,
                        'assigned_by'      => $assignedById,
                        'before'           => ['role_ids' => $previousRoleIds],
                        'after'            => ['role_ids' => $normalizedRoleIds],
                    ],
                );
            }

            foreach ($rolesRemoved as $roleId) {
                $this->audit->record(
                    eventType: 'role.removed',
                    user: $assignedById,
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'target_email'     => $user->email,
                        'role_id'          => $roleId,
                        'role_name'        => $roleNames[$roleId] ?? null,
                        'removed_by'       => $assignedById,
                        'before'           => ['role_ids' => $previousRoleIds],
                        'after'            => ['role_ids' => $normalizedRoleIds],
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
                    user: $assignedById,
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'target_email'     => $user->email,
                        'permission_id'    => $permId,
                        'permission_code'  => $perm?->code,
                        'permission_name'  => $perm?->name,
                        'assigned_by'      => $assignedById,
                        'before'           => ['permission_ids' => $previousPermissionIds],
                        'after'            => ['permission_ids' => $normalizedPermissionIds],
                    ],
                );
            }

            foreach ($permsRemoved as $permId) {
                $perm = $permissionMeta->get($permId);
                $this->audit->record(
                    eventType: 'permission.removed',
                    user: $assignedById,
                    resourceType: 'user',
                    resourceId: $user->id,
                    metadata: [
                        'target_user_id'   => $user->id,
                        'target_user_name' => $user->full_name,
                        'target_email'     => $user->email,
                        'permission_id'    => $permId,
                        'permission_code'  => $perm?->code,
                        'permission_name'  => $perm?->name,
                        'removed_by'       => $assignedById,
                        'before'           => ['permission_ids' => $previousPermissionIds],
                        'after'            => ['permission_ids' => $normalizedPermissionIds],
                    ],
                );
            }

            $this->audit->record(
                eventType: 'permissions.changed',
                user: $assignedById,
                resourceType: 'user',
                resourceId: $user->id,
                metadata: [
                    'target_user_id'    => $user->id,
                    'target_user_name'  => $user->full_name,
                    'target_email'      => $user->email,
                    'added_permission_ids'   => $permsAdded,
                    'removed_permission_ids' => $permsRemoved,
                    'assigned_by'       => $assignedById,
                    'before'            => [
                        'role_ids' => $previousRoleIds,
                        'permission_ids' => $previousPermissionIds,
                    ],
                    'after'             => [
                        'role_ids' => $normalizedRoleIds,
                        'permission_ids' => $normalizedPermissionIds,
                    ],
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
