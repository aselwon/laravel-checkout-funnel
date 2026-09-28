<?php

namespace App\Http\Middleware;

use App\Models\Lead;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLead
{
    public function handle(Request $request, Closure $next): Response
    {
        $lead = Lead::find($request->session()->get('lead_id'));
        if (! $lead) {
            return redirect()->route('home')->with('status', 'Start with your email to access your workspace.');
        }
        $request->attributes->set('lead', $lead);

        return $next($request);
    }
}
