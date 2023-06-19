<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdminRole
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
        return $next($request);
        if(auth()->check())
        {
            if(\Request::route()->getName() == 'admin.logout')  return $next($request);
            $admin = auth('admin')->user();
            $admin_permissions = $admin->getAdminPermissions();
            if(in_array(\Request::route()->getName(), $admin_permissions))
            {
                return $next($request);
            }
            return abort(403);
        }
        return $next($request);
    }
}
