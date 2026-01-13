<?php

use App\Helpers\ResponseServer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Orion\Exceptions\MaxPaginationLimitExceededException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(HandleCors::class);
        $middleware->statefulApi();
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'localFix' => \App\Http\Middleware\FixLocaleMiddleware::class,
            'language' => \App\Http\Middleware\SetLocale::class,
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
            'invalidate.menu.cache' => \App\Http\Middleware\InvalidateMenuCache::class,
            // ... autres alias
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'user.is.blocked' => \App\Http\Middleware\CheckUserIsBlocked::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
            'isadmin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.password' => \App\Http\Middleware\CheckAdminPassword::class,

        ]);
        $middleware->append(
            \Illuminate\Session\Middleware\StartSession::class,
        );
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (MaxPaginationLimitExceededException $e, Request $request) {
            $limitExceeded = $request->input('limit');

            return ResponseServer::handleMaxPaginationLimitExceededException($limitExceeded);
        });

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            return ResponseServer::notFoundHttpException($e);
        });
    })->create();
