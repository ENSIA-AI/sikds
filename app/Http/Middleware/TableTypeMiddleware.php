<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TableTypeMiddleware
{
    public function handle(Request $request, Closure $next, int $table_type)
    {
        // Select the evaluation cretireas based on the table type
        $round = match ($table_type) {
            1 => 1, // evaluation-first round
            2 => 1, // Selection to the second round
            3 => 2, // evaluation second round
            4 => 2, // Selection to the third round
            default => 1,
        };

        Session::put('criteria_round', $round);
        Session::put('table_type', $table_type);

        return $next($request);
    }
}
