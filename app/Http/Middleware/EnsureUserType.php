<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    /**
     * Restrict a route to one or more user types, e.g. `->middleware('user.type:merchant')`.
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->type, $types, true)) {
            abort(403);
        }

        return $next($request);
    }
}
