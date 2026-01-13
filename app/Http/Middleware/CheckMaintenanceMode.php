<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (MaintenanceMode::isActive()) {
            $user = $request->user();

            if (! $user || ! $user->hasRole('admin')) {
                return response()->json([
                    'message' => MaintenanceMode::getMessage() ?? 'Application en maintenance',
                    'maintenance' => true,
                ], 503);
            }
        }

        return $next($request);
    }
}
