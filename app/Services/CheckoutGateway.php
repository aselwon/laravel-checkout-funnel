<?php

namespace App\Services;

use App\Models\Purchase;
use Stripe\StripeClient;

class CheckoutGateway
{
    public function create(Purchase $purchase): string
    {
        if ($purchase->mode === 'mock') {
            return route('checkout.mock', $purchase);
        }
        // This MVP deliberately accepts test credentials only.
        $secret = config('funnel.stripe_secret');
        if (! is_string($secret) || ! str_starts_with($secret, 'sk_test_') || ! config('funnel.stripe_price_id')) {
            throw new \RuntimeException('Stripe test credentials and price are required.');
        }
        $stripe = new StripeClient($secret);
        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $purchase->lead->email,
            'line_items' => [['price' => config('funnel.stripe_price_id'), 'quantity' => 1]],
            'client_reference_id' => $purchase->id,
            'metadata' => ['purchase_id' => $purchase->id],
            'success_url' => route('checkout.success', $purchase),
            'cancel_url' => route('onboarding').'?checkout=cancelled',
        ], ['idempotency_key' => 'checkout-'.$purchase->id]);
        $purchase->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }
}
