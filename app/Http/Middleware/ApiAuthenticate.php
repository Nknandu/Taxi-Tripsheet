<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(!auth()->check())
        {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Unauthenticated',
                'message_code' => 'unauthenticated',
            ], 401);

        }
        return $next($request);
    }
}
