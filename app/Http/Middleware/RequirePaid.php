<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePaid
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->get('lead')->paid_at) {
            return redirect()->route('onboarding')->with('status', 'Upgrade to the Growth Kit to unlock this workspace.');
        }

        return $next($request);
    }
}
