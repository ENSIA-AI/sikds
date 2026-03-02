<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FonctionMiddleware
{
    public function handle(Request $request, Closure $next, int $fonction)
    {
        $request->session()->put('active_fonction', $fonction);

        return $next($request);
    }
}
