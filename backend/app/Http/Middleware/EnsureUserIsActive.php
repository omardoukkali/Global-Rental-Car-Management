<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Block users whose account is no longer active and delete their tokens,
     * so a suspension takes effect immediately (see F-02).
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Your account is not active.',
            ], 403);
        }

        return $next($request);
    }
}
