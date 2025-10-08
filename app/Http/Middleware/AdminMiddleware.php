<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Debug logging
        \Log::info('AdminMiddleware Debug:', [
            'has_user' => !!$user,
            'user_id' => $user ? $user->id : null,
            'user_role' => $user ? $user->role : null,
            'is_admin' => $user ? $user->isAdmin() : false,
            'request_path' => $request->path(),
            'auth_header' => $request->header('Authorization') ? 'Present' : 'Missing'
        ]);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. No user found.'
            ], 401);
        }
        
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required. Current role: ' . $user->role
            ], 403);
        }

        return $next($request);
    }
}
