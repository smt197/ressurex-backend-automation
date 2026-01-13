<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;

class FixLocaleMiddleware
{
    public function handle($request, Closure $next)
    {
        // Liste des locales supportées
        $supportedLocales = ['en', 'fr', 'pt'];

        // Récupère la locale préférée valide
        $locale = $this->getValidLocale($request, $supportedLocales);

        // Applique la locale
        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    protected function getValidLocale($request, array $supportedLocales)
    {
        $header = $request->header('Accept-Language');

        if (empty($header)) {
            return config('app.locale');
        }

        // Nettoie le header pour ne garder que les codes de langue
        $cleaned = preg_replace('/;q=[0-9.]+/', '', $header);
        $locales = explode(',', $cleaned);

        foreach ($locales as $locale) {
            $locale = trim($locale);
            // Garde seulement la partie langue (fr_FR => fr)
            $shortLocale = explode('_', $locale)[0];

            if (in_array($shortLocale, $supportedLocales)) {
                return $shortLocale;
            }
        }

        return config('app.locale');
    }
}
