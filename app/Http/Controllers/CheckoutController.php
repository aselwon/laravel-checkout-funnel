<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Purchase;
use App\Services\CheckoutGateway;
use App\Services\FulfillPurchase;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function store(Request $request, CheckoutGateway $gateway)
    {
        $lead = $request->attributes->get('lead');
        if ($lead->paid_at) {
            return redirect()->route('growth');
        }
        $purchase = Purchase::create([
            'lead_id' => $lead->id,
            'mode' => config('funnel.mock_stripe') ? 'mock' : 'stripe',
            'amount' => config('funnel.price_cents'),
            'currency' => config('funnel.currency'),
        ]);
        try {
            $url = $gateway->create($purchase);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('onboarding')->withErrors(['checkout' => 'Checkout is temporarily unavailable. Please try again.']);
        }
        AnalyticsEvent::record($lead->assignment_id, 'checkout');

        return redirect()->away($url);
    }

    public function mock(Request $request, Purchase $purchase)
    {
        $this->authorizePurchase($request, $purchase);
        abort_unless(config('funnel.mock_stripe') && $purchase->mode === 'mock', 404);

        return view('checkout', compact('purchase'));
    }

    public function completeMock(Request $request, Purchase $purchase, FulfillPurchase $fulfill)
    {
        $this->authorizePurchase($request, $purchase);
        abort_unless(config('funnel.mock_stripe') && $purchase->mode === 'mock', 404);
        $fulfill->handle($purchase);

        return redirect()->route('checkout.success', $purchase);
    }

    public function success(Request $request, Purchase $purchase)
    {
        $this->authorizePurchase($request, $purchase);

        return view('success', compact('purchase'));
    }

    private function authorizePurchase(Request $request, Purchase $purchase): void
    {
        abort_unless($purchase->lead_id === $request->attributes->get('lead')->id, 404);
    }
}
