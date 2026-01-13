<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredRole = 'admin'): Response
    {
        // Si l'utilisateur est déjà authentifié, vérifier son rôle
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->hasRole($requiredRole)) {
                Auth::logout();

                return response()->json([
                    'message' => __('maintenance.unauthorized_user'),
                ], 403);
            }

            return $next($request);
        }

        // Vérifier le rôle avant l'authentification basée sur les credentials fournis
        $email = $request->input('email');

        if ($email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // Vérifier si l'utilisateur a le bon rôle pour cette route
                if (! $user->hasRole($requiredRole)) {
                    return response()->json([
                        'message' => __('maintenance.unauthorized_user'),
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
