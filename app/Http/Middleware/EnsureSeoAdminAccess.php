<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSeoAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() && $request->user()->can('viewSeoAdmin'), 403);
        return $next($request);
    }
}
