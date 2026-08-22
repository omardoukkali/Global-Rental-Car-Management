<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user || $user->role !== 'agency') {
            return response()->json([
                'message' => 'Agency access required.',
            ], 403);
        }

        if (!$user->agency) {
            return response()->json([
                'message' => 'Agency profile not found.',
            ], 403);
        }

        if ($user->agency->status !== 'approved') {
            return response()->json([
                'message' => 'Your agency is awaiting approval.',
            ], 403);
        }

        return $next($request);
    }
}
