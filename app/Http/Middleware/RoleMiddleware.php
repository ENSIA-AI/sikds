<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // check if the active role has the actual route permission
        $modules = Cache::get('menu-modules_'.Auth::user()->activeRoleId);

        if (! $modules) {
            return $next($request);
        }
        // check if the actual role active role has the same role as the url from the request
        $path = '/'.$request->uri()->path();
        $hasPermission = $modules->contains(function ($module) use ($path) {
            return $module->url === $path && $module->role_id === Auth::user()->activeRoleId;
        });

        // if the user does not have permission, redirect to the dashboard
        if (! $hasPermission) {
            return redirect('/dashboard')->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
