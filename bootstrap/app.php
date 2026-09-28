<?php

use App\Http\Middleware\AssignVariant;
use App\Http\Middleware\PaidPathEnabled;
use App\Http\Middleware\RequireAdmin;
use App\Http\Middleware\RequireLead;
use App\Http\Middleware\RequirePaid;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'variant' => AssignVariant::class,
            'lead' => RequireLead::class,
            'paid' => RequirePaid::class,
            'admin' => RequireAdmin::class,
            'paid.enabled' => PaidPathEnabled::class,
        ]);
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Stripe retries failed webhook requests; do not acknowledge processing failures.
    })->create();
