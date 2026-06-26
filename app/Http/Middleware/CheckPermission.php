<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) return redirect()->route('login');

        if (!auth()->user()->hasPermissionTo($permission)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
