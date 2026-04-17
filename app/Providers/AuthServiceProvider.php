<?php
/** before checking any gate, it checks if the user has the corresponding permission
 * it is used to check if the user has the corresponding permission
 */

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Users\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::before(function (User $user, string $ability) {
            // Check if user has the permission
            return $user->hasPermissionTo($ability) ? true : null;
        });
    }
}