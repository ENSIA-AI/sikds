<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Update the details of an existing role.
 * - Block modification of system roles
 * - Sync permissions (replaces old set with new)
 */
final class UpdateRoleAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(Role $role, array $data): Role
    {
        if ($role->is_system_role) {
            throw new \Exception('Les rôles système ne peuvent pas être modifiés.');
        }

        $beforeName        = $role->name;
        $beforeDescription = $role->description;
        $previousPermissionIds = $role->permissions()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $normalizedPermissionIds = array_values(array_unique(array_map('intval', $data['permission_ids'] ?? [])));

        $fresh = DB::transaction(function () use ($role, $data, $normalizedPermissionIds) {
            $role->update([
                'name' => $data['name'],
                'slug' => $role->name !== $data['name']
                    ? Str::slug($data['name'])
                    : $role->slug,
                'description' => $data['description'] ?? null,
            ]);

            $role->permissions()->sync($normalizedPermissionIds);

            return $role->fresh('permissions');
        });

        $permsAdded   = array_values(array_diff($normalizedPermissionIds, $previousPermissionIds));
        $permsRemoved = array_values(array_diff($previousPermissionIds, $normalizedPermissionIds));

        $this->audit->record(
            eventType: 'role.updated',
            resourceType: 'role',
            resourceId: $fresh->id,
            metadata: [
                'role_id'              => $fresh->id,
                'role_name'            => $fresh->name,
                'before' => [
                    'name'        => $beforeName,
                    'description' => $beforeDescription,
                ],
                'after' => [
                    'name'        => $fresh->name,
                    'description' => $fresh->description,
                ],
                'permission_ids'       => $normalizedPermissionIds,
                'added_permission_ids' => $permsAdded,
                'removed_permission_ids' => $permsRemoved,
            ],
        );

        if ($permsAdded !== [] || $permsRemoved !== []) {
            $codes = Permission::query()
                ->whereIn('id', array_unique([...$permsAdded, ...$permsRemoved]))
                ->pluck('code', 'id')
                ->all();

            $this->audit->record(
                eventType: 'role.permissions.changed',
                resourceType: 'role',
                resourceId: $fresh->id,
                metadata: [
                    'role_id'                => $fresh->id,
                    'role_name'              => $fresh->name,
                    'added_permission_ids'   => $permsAdded,
                    'removed_permission_ids' => $permsRemoved,
                    'added_permission_codes' => array_values(array_filter(array_map(fn ($id) => $codes[$id] ?? null, $permsAdded))),
                    'removed_permission_codes' => array_values(array_filter(array_map(fn ($id) => $codes[$id] ?? null, $permsRemoved))),
                ],
            );
        }

        return $fresh;
    }
}
