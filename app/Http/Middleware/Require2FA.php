<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2FA
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->google2fa_enabled) {
            if (! $request->routeIs('admin.2fa.*')) {
                return redirect()->route('admin.2fa.setup');
            }

            return $next($request);
        }

        if (! $request->session()->get('2fa_verified')) {
            if (! $request->routeIs('admin.2fa.challenge') && ! $request->routeIs('admin.2fa.verify')) {
                return redirect()->route('admin.2fa.challenge');
            }
        }

        return $next($request);
    }
}
