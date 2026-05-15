<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } catch (\Exception $e) {
            // Check session for admin (web routes)
            if (session('admin_logged_in') !== true) {
                return redirect()->route('admin.login');
            }
        }
        return $next($request);
    }
}
