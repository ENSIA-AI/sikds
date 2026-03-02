<?php

namespace App\Providers;

use App\Auth\Providers\CachedEloquentUserProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $this->declareCacheElequentProvider();

        $this->automaticallyEagerLoadRelationships();

    }

    /**
     * Configure the general configuration
     */
    public function configure(): void
    {
        // Set the default string length for database columns

    }

    private function declareCacheElequentProvider(): void
    {
        auth()->provider('CachedElequent', function (Application $app, array $config) {
            return new CachedEloquentUserProvider(
                $app['hash'],
                $config['model']
            );
        });
    }

    private function automaticallyEagerLoadRelationships()
    {
        Model::automaticallyEagerLoadRelationships();
    }
}
