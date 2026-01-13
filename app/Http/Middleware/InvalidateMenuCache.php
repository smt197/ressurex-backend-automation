<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class InvalidateMenuCache
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Invalider le cache seulement pour les opérations de modification (POST, PUT, PATCH, DELETE)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']) && $response->getStatusCode() < 400) {
            $this->invalidateMenuCaches();
        }

        return $response;
    }

    /**
     * Invalide tous les caches liés aux menus
     */
    private function invalidateMenuCaches(): void
    {
        try {
            // Pour le driver file, on utilise une approche différente
            if (config('cache.default') === 'file') {
                // Supprimer les caches connus avec patterns
                $this->invalidateFileCachePatterns();
            } else {
                // Pour Redis ou autres drivers compatibles
                $this->invalidateRedisCachePatterns();
            }
        } catch (\Exception $e) {
            \Log::warning('Impossible d\'invalider le cache des menus: '.$e->getMessage());
        }

        \Log::info('Cache des menus invalidé après opération CRUD');
    }

    /**
     * Invalide les caches avec le driver file
     */
    private function invalidateFileCachePatterns(): void
    {
        // Avec le driver file, on ne peut pas utiliser de patterns
        // On utilise Cache::flush() pour tout vider
        Cache::flush();

        \Log::info('Cache file vidé complètement après opération CRUD menu');
    }

    /**
     * Invalide les caches avec Redis
     */
    private function invalidateRedisCachePatterns(): void
    {
        $patterns = [
            'cache_*_menus*',  // Cache du middleware CacheResponse pour les routes menus
            'menus_index_*',   // Cache de la méthode index du controller
            'user_menus_*',     // Cache des menus utilisateur
        ];

        foreach ($patterns as $pattern) {
            $cacheKeys = Cache::getRedis()->keys($pattern);

            if ($cacheKeys && count($cacheKeys) > 0) {
                foreach ($cacheKeys as $key) {
                    // Enlever le préfixe Redis si nécessaire
                    $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);
                    Cache::forget($cleanKey);
                }
            }
        }
    }
}
