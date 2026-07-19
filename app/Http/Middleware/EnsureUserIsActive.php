<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login already refuses deactivated accounts, but that alone leaves anyone with
 * an existing session working until they choose to log out. This closes that
 * gap: every request from an inactive account ends the session on the spot.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'This account has been deactivated.'], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'This account has been deactivated. Please contact an administrator.']);
        }

        return $next($request);
    }
}
