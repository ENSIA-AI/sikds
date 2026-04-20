<?php

    /** update the details of an existing role
     * prevent modification of system roles
     * sync permissions with role ie removes old and adds new
     * uses transaction to ensure data consistency
    */

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UpdateRoleAction
{
    
    public function execute(Role $role, array $data): Role
    {
        // Prevent editing system roles
        if ($role->is_system_role) {
            throw new \Exception('System roles cannot be modified.');
        }
        
        return DB::transaction(function () use ($role, $data) {
            // Update basic info
            $role->update([
                'name' => $data['name'],
                'slug' => $role->name !== $data['name']
                    ? str::slug($data['name'])
                    : $role->slug,
                'description' => $data['description'] ?? null,
            ]);
            
            // Sync permissions (this replaces all existing permissions)
            $role->permissions()->sync($data['permission_ids'] ?? []);
            
            return $role->fresh('permissions');
        });
    }
}