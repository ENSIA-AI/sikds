<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction
{
    
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'institution_id' => $data['institution_id'],
                'is_active' => $data['is_active'] ?? $user->is_active,
            ]);
            
            return $user->fresh('institution', 'roles');
        });
    }
}