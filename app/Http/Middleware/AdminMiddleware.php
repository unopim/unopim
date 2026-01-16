<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check() || ! Auth::user()->hasRole('Administrator')) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
