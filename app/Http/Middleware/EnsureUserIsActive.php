<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();
            
            return redirect()->route('login')
                ->withErrors(['email' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.']);
        }

        return $next($request);
    }
}