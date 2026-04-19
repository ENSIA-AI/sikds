<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class ActivateUserAction
{
    /**
     * Reactivate a previously deactivated user.
     */
    public function execute(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $user->forceFill(['is_active' => true])->save();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $user->fresh(['institution', 'roles']);
        });
    }
}
