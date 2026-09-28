<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function login()
    {
        return view('admin.login');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'not_regex:/[\x00-\x20\x7f]/', 'max:254'], 'password' => ['required', 'string']]);
        $key = 'admin-login:'.hash('sha256', Str::lower($data['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in a minute.']);
        }
        if (! Auth::attempt([...$data, 'is_admin' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match an admin account.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function dashboard()
    {
        $metrics = AnalyticsEvent::selectRaw('type, COUNT(*) AS total')->groupBy('type')->pluck('total', 'type');
        $variants = [];
        foreach (['A', 'B'] as $variant) {
            $variants[$variant] = AnalyticsEvent::join('assignments', 'assignments.id', '=', 'analytics_events.assignment_id')
                ->where('assignments.variant', $variant)->selectRaw('type, COUNT(*) AS total')->groupBy('type')->pluck('total', 'type');
        }
        $assignments = Assignment::with('lead')->withCount('events')->latest()->paginate(15);

        return view('admin.dashboard', compact('metrics', 'variants', 'assignments'));
    }
}
