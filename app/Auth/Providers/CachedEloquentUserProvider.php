<?php

namespace App\Auth\Providers;

use Illuminate\Auth\EloquentUserProvider;

class CachedEloquentUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        return cache()->remember(
            'user_'.$identifier,
            now()->addMinutes(120),
            function () use ($identifier) {
                return parent::retrieveById($identifier);
            });
    }
}
