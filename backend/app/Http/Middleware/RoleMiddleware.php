<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,string ...$roles): mixed
    {   
        // Check if user is authenticated
        if(!$request->user()){
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if user has required role
        if(!in_array($request->user()->role, $roles)){
            return response()->json([
                'message' => 'Unauthorized — you do not have access to this resource'
            ], 403); 
        }
        return $next($request);
    }
}
