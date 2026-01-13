<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip only for API routes and admin password routes
        if ($request->is('api/*') || $request->is('admin/password*')) {
            return $next($request);
        }

        // Check if admin password has been verified in session
        if (session()->has('admin_password_verified')) {
            return $next($request);
        }

        // Store the intended URL in session
        session(['admin_intended_url' => $request->fullUrl()]);

        // Redirect to admin password page
        return redirect()->route('admin.password.show');
    }
}
