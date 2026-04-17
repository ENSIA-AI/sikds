<?php

/**
 * Create a new role with the given permissions.
 * - Auto-generate slug from name
 * - Associate permissions with role
 */
declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateRoleAction
{
    public function execute(array $data, int $createdById): Role
    {
        return DB::transaction(function () use ($data, $createdById) {
            $slug = Str::slug($data['name']);

            $role = Role::create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'is_system_role' => false,
                'created_by' => $createdById,
            ]);

            if (!empty($data['permission_ids'])) {
                $role->permissions()->attach($data['permission_ids']);
            }

            return $role->load('permissions');
        });
    }
}

