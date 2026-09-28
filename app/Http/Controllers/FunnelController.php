<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FunnelController extends Controller
{
    public const CHECKLIST = [
        'promise' => ['Write your one-sentence promise', 'Name the customer, the problem, and the result your product delivers.'],
        'product' => ['Make one product the hero', 'Choose your strongest product and give it a focused landing page.'],
        'proof' => ['Add a reason to believe', 'Place a real customer quote or a useful product detail beside your CTA.'],
        'checkout' => ['Walk through your checkout', 'Try the mobile journey yourself. Remove one unnecessary step.'],
        'measure' => ['Set your baseline', 'Record visits, signups, and purchases before changing anything.'],
    ];

    public function landing(Request $request)
    {
        return view('landing', ['assignment' => $request->attributes->get('assignment')]);
    }

    public function signup(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'not_regex:/[\x00-\x20\x7f]/', 'max:254']]);
        $assignment = $request->attributes->get('assignment');
        $lead = DB::transaction(function () use ($assignment, $data) {
            // Serialize submissions for the same visitor; email is contact data, never identity proof.
            $assignment->newQuery()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            $lead = Lead::firstOrCreate(['assignment_id' => $assignment->id], ['email' => mb_strtolower($data['email'])]);
            AnalyticsEvent::record($assignment->id, 'signup');

            return $lead;
        });
        $request->session()->regenerate();
        $request->session()->put('lead_id', $lead->id);

        return redirect()->route('onboarding');
    }

    public function onboarding(Request $request)
    {
        return view('onboarding', ['lead' => $request->attributes->get('lead'), 'checklist' => self::CHECKLIST]);
    }

    public function checklist(Request $request)
    {
        $data = $request->validate([
            'completed' => ['sometimes', 'array', 'max:5'],
            'completed.*' => ['string', 'distinct', Rule::in(array_keys(self::CHECKLIST))],
        ]);
        $request->attributes->get('lead')->update(['checklist' => $data['completed'] ?? []]);

        return redirect()->route('onboarding')->with('status', 'Progress saved. Keep building momentum.');
    }

    public function growth(Request $request)
    {
        return view('growth', ['lead' => $request->attributes->get('lead')]);
    }
}
