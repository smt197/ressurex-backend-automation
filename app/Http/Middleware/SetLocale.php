<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        $lang = $request->header('Accept-Language', 'en'); // Par défaut, utilisez le français si la langue n'est pas spécifiée
        app()->setLocale($lang);

        return $next($request);
    }
}
