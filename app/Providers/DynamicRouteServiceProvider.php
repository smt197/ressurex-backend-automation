<?php

namespace App\Providers;

use App\Models\DynamicRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DynamicRouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load dynamic routes after all other routes
        $this->loadDynamicRoutes();
    }

    /**
     * Load all active dynamic routes from database.
     */
    protected function loadDynamicRoutes(): void
    {
        try {
            $routes = DynamicRoute::active()->ordered()->get();

            foreach ($routes as $route) {
                $this->registerRoute($route);
            }
        } catch (\Exception $e) {
            // Table might not exist yet during migration
            return;
        }
    }

    /**
     * Register a single dynamic route.
     */
    protected function registerRoute(DynamicRoute $route): void
    {
        $method = strtolower($route->method);
        $middleware = $route->getAllMiddleware();

        // Build route registration
        $routeInstance = Route::middleware($middleware)
            ->name($route->name);

        // Determine action
        if ($route->controller && $route->action) {
            $action = $route->controller.'@'.$route->action;
        } elseif ($route->controller) {
            $action = $route->controller;
        } else {
            // No controller specified, skip
            return;
        }

        // Register the route with the appropriate HTTP method
        $routeInstance->$method($route->uri, $action);
    }
}
