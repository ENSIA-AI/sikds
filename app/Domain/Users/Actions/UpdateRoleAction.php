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
 * - Keep system role identity immutable (name/slug/description)
 *   while still allowing permission updates
 * - Sync permissions (replaces old set with new)
 */
final class UpdateRoleAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(Role $role, array $data): Role
    {
        $beforeName        = $role->name;
        $beforeDescription = $role->description;
        $previousPermissionIds = $role->permissions()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $normalizedPermissionIds = array_values(array_unique(array_map('intval', $data['permission_ids'] ?? [])));
        if ($normalizedPermissionIds !== []) {
            $selectedCodes = Permission::query()
                ->whereIn('id', $normalizedPermissionIds)
                ->pluck('code')
                ->filter()
                ->map(fn ($c) => (string) $c)
                ->all();
            if (in_array('document.create', $selectedCodes, true) && ! in_array('tag.assign', $selectedCodes, true)) {
                $tagAssignId = Permission::query()->where('code', 'tag.assign')->value('id');
                if ($tagAssignId) {
                    $normalizedPermissionIds[] = (int) $tagAssignId;
                    $normalizedPermissionIds = array_values(array_unique(array_map('intval', $normalizedPermissionIds)));
                }
            }
        }

        $fresh = DB::transaction(function () use ($role, $data, $normalizedPermissionIds) {
            if (! $role->is_system_role) {
                $role->update([
                    'name' => $data['name'],
                    'slug' => $role->name !== $data['name']
                        ? Str::slug($data['name'])
                        : $role->slug,
                    'description' => $data['description'] ?? null,
                ]);
            }

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
                    'permission_ids' => $previousPermissionIds,
                ],
                'after' => [
                    'name'        => $fresh->name,
                    'description' => $fresh->description,
                    'permission_ids' => $normalizedPermissionIds,
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
                    'before' => [
                        'permission_ids' => $previousPermissionIds,
                    ],
                    'after' => [
                        'permission_ids' => $normalizedPermissionIds,
                    ],
                ],
            );
        }

        return $fresh;
    }
}
