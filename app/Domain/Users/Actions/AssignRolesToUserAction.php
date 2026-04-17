<?php

    /** assign roles to a user
     * replace all user's current roles with new set
     * record who made the assignment
     */

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class AssignRolesToUserAction
{
    
    public function execute(User $user, array $roleIds, int $assignedById): User
    {
        return DB::transaction(function () use ($user, $roleIds, $assignedById) {
            // Prepare sync data with assigned_by metadata
            $syncData = [];
            foreach ($roleIds as $roleId) {
                $syncData[$roleId] = [
                    'assigned_at' => now(),
                    'assigned_by' => $assignedById,
                ];
            }
            
            // Sync roles (replaces existing role assignments)
            $user->roles()->sync($syncData);
            
            return $user->fresh('roles.permissions');
        });
    }
}