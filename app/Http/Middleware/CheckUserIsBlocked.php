<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('email')) {
            $user = User::where('email', $request->email)->first();

            if ($user && $user->isBlocked()) {
                return response()->json([
                    'message' => __('user.message_blocked'),
                ], 403);
            }
        }

        return $next($request);
    }
}
