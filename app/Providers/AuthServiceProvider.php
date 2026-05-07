<?php
/** before checking any gate, it checks if the user has the corresponding permission
 * it is used to check if the user has the corresponding permission
 */

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Users\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class AuthServiceProvider extends ServiceProvider
{
    
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for all permissions
        // This allows using: Gate::allows('role.create')
        Gate::before(function ($user, $ability): ?bool {
            if (! $user instanceof User || ! is_string($ability)) {
                return null;
            }

            if ($user->hasRole('Super Administrateur')) {
                return true;
            }

            try {
                return $user->hasPermissionTo($ability) ? true : null;
            } catch (PermissionDoesNotExist) {
                return null;
            } catch (\Throwable) {
                return null;
            }
        });
    }
}