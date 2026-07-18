<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Verify user is authenticated
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // 2. Verify user has one of the required roles
        if (!in_array($request->user()->role, $roles)) {
            session()->flash('error', 'Unauthorized access.');

            return match ($request->user()->role) {
                'client' => redirect()->route('client.reservations'),
                'agency_owner' => redirect()->route('agency.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                default => redirect()->route('landing'),
            };
        }

        return $next($request);
    }
}
