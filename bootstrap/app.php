<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        // health: '/up',
        using: function () {
            $centralDomains = config('tenancy.identification.central_domains');

            foreach ($centralDomains as $domain) {
                Route::middleware('web')
                    ->domain($domain)
                    ->group(base_path('routes/web.php'));
            }

            // Load main API routes (handles tenant resolution internally)
            Route::prefix('api')->group(base_path('routes/api.php'));

            Route::middleware([
                'tenant',
                InitializeTenancyByPath::class,
                PreventAccessFromCentralDomains::class,
            ])->group(function () {
                Route::middleware('web')->group(base_path('routes/tenant/web.php'));
                Route::middleware('api')->group(base_path('routes/tenant/api.php'));
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias(['role' => CheckRole::class, 'permission' => CheckPermission::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
