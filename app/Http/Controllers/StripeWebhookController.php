<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\FulfillPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, FulfillPurchase $fulfill)
    {
        abort_if(config('funnel.mock_stripe'), 404);
        $secret = config('funnel.stripe_webhook_secret');
        abort_unless(is_string($secret) && $secret !== '', 503);
        try {
            $event = Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), $secret);
        } catch (SignatureVerificationException|\UnexpectedValueException $exception) {
            return response()->json(['error' => 'Invalid webhook signature or payload.'], 400);
        }
        if (! in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return response()->json(['received' => true]);
        }
        $session = $event->data->object;
        // A completed Checkout may still be awaiting a delayed payment method.
        if ($session->payment_status !== 'paid') {
            return response()->json(['received' => true]);
        }
        if ($event->livemode || ($session->livemode ?? false)) {
            return response()->json(['error' => 'Test mode only.'], 422);
        }
        $purchase = Purchase::where('stripe_session_id', $session->id)->where('mode', 'stripe')->first();
        // Retry unknown sessions: delivery may race with persistence of the Checkout response.
        if (! $purchase) {
            return response()->json(['error' => 'Unknown checkout session.'], 409);
        }
        if (($session->metadata->purchase_id ?? null) !== $purchase->id
            || ($session->client_reference_id ?? null) !== $purchase->id
            || $session->mode !== 'payment'
            || $session->amount_total !== $purchase->amount
            || $session->currency !== $purchase->currency) {
            return response()->json(['error' => 'Checkout details do not match.'], 422);
        }
        DB::transaction(function () use ($event, $purchase, $fulfill) {
            // Lock before receipt insertion: same-session concurrent events serialize.
            Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if (DB::table('webhook_receipts')->where('id', $event->id)->exists()) {
                return;
            }
            $fulfill->handle($purchase);
            DB::table('webhook_receipts')->insert(['id' => $event->id, 'created_at' => now(), 'updated_at' => now()]);
        });

        return response()->json(['received' => true]);
    }
}
