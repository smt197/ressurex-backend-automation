<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $minutes = '10', $prefix = 'cache'): Response
    {
        // Désactiver le cache en développement si APP_DEBUG=true
        if (config('app.debug')) {
            return $next($request);
        }

        // Générer une clé unique basée sur l'utilisateur, la route et les paramètres
        $user = auth()->user();
        $userId = $user ? $user->id : 'guest';
        $route = $request->route()->getName() ?? $request->path();
        $params = md5(serialize($request->all()));

        $cacheKey = "{$prefix}_{$userId}_{$route}_{$params}";

        // Vérifier si la réponse est déjà en cache
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);

            return response($cachedResponse['content'], $cachedResponse['status'])
                ->withHeaders($cachedResponse['headers']);
        }

        // Exécuter la requête
        $response = $next($request);

        // Mettre en cache seulement les réponses GET réussies
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $cacheData = [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ];

            Cache::put($cacheKey, $cacheData, now()->addMinutes((int) $minutes));
        }

        return $response;
    }
}
