<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaidPathEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('funnel.paid_enabled'), 404);

        return $next($request);
    }
}
