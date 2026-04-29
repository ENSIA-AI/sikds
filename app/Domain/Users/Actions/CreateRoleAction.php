<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create a new role with the given permissions.
 * - Auto-generate slug from name
 * - Associate permissions with role
 */
final class CreateRoleAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(array $data, int $createdById): Role
    {
        $permissionIds = array_values(array_unique(array_map('intval', $data['permission_ids'] ?? [])));
        if ($permissionIds !== []) {
            $selectedCodes = Permission::query()
                ->whereIn('id', $permissionIds)
                ->pluck('code')
                ->filter()
                ->map(fn ($c) => (string) $c)
                ->all();
            if (in_array('document.create', $selectedCodes, true) && ! in_array('tag.assign', $selectedCodes, true)) {
                $tagAssignId = Permission::query()->where('code', 'tag.assign')->value('id');
                if ($tagAssignId) {
                    $permissionIds[] = (int) $tagAssignId;
                    $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
                }
            }
        }

        $role = DB::transaction(function () use ($data, $createdById, $permissionIds) {
            $slug = Str::slug($data['name']);

            $role = Role::create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'is_system_role' => false,
                'created_by' => $createdById,
            ]);

            if ($permissionIds !== []) {
                $role->permissions()->attach($permissionIds);
            }

            return $role->load('permissions');
        });

        $permissionCodes = $permissionIds === []
            ? []
            : Permission::query()->whereIn('id', $permissionIds)->pluck('code')->all();

        $this->audit->record(
            eventType: 'role.created',
            resourceType: 'role',
            resourceId: $role->id,
            metadata: [
                'role_id'          => $role->id,
                'role_name'        => $role->name,
                'role_slug'        => $role->slug,
                'description'      => $role->description,
                'permission_ids'   => $permissionIds,
                'permission_codes' => $permissionCodes,
            ],
        );

        return $role;
    }
}
