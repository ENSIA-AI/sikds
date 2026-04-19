<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class DeactivateUserAction
{
    /**
     * Mark a user as inactive. Does not remove roles or permissions.
     */
    public function execute(User $user, int $deactivatedById): void
    {
        if ($user->id === $deactivatedById) {
            throw new \Exception('Vous ne pouvez pas désactiver votre propre compte.');
        }

        DB::transaction(function () use ($user): void {
            if (! $user->is_active) {
                return;
            }

            $user->forceFill(['is_active' => false])->save();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        });
    }
}
