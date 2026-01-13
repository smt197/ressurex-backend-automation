<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseServer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasValidSignature()) {
            return ResponseServer::unauthorizationsignatureRequest();
        }

        return $next($request);
    }
}
