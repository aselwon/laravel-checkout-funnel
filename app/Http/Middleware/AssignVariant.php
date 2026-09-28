<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsEvent;
use App\Models\Assignment;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignVariant
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->cookie('sb_visitor');
        $assignment = is_string($id) && Str::isUuid($id) ? Assignment::find($id) : null;
        if (! $assignment) {
            $assignment = Assignment::create(['variant' => random_int(0, 1) ? 'A' : 'B']);
        }
        AnalyticsEvent::record($assignment->id, 'visitor');
        $request->attributes->set('assignment', $assignment);
        Cookie::queue(cookie('sb_visitor', $assignment->id, 60 * 24 * 30, '/', null, $request->isSecure(), true, false, 'lax'));

        return $next($request);
    }
}
