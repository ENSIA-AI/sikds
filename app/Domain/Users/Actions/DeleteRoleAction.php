<?php

    /** delete a role
     * prevent deleting system roles
     * prevent deleting roles if are assigned to users
     * uses transaction to ensure data consistency
     */
declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\Role;
use Illuminate\Support\Facades\DB;

final class DeleteRoleAction
{
    
    public function execute(Role $role): bool
    {
        // Prevent deleting system roles
        if ($role->is_system_role) {
            throw new \Exception('System roles cannot be deleted.');
        }
        
        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            throw new \Exception('Cannot delete role that is assigned to users. Please remove all user assignments first.');
        }
        
        return DB::transaction(function () use ($role) {
            // Detach all permissions first
            $role->permissions()->detach();
            
            // Delete the role
            return $role->delete();
        });
    }
}