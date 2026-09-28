<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Assignment;
use App\Models\Lead;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Purchase $purchase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['funnel.mock_stripe' => false, 'funnel.stripe_webhook_secret' => 'whsec_testing']);
        $assignment = Assignment::create(['variant' => 'A']);
        $lead = Lead::create(['assignment_id' => $assignment->id, 'email' => 'seller@example.com']);
        $this->purchase = Purchase::create(['lead_id' => $lead->id, 'mode' => 'stripe', 'stripe_session_id' => 'cs_test_example', 'amount' => 2900, 'currency' => 'usd']);
    }

    private function payload(array $session = [], string $id = 'evt_example', string $type = 'checkout.session.completed'): array
    {
        return [
            'id' => $id,
            'object' => 'event',
            'type' => $type,
            'livemode' => false,
            'data' => ['object' => array_replace([
                'id' => 'cs_test_example',
                'object' => 'checkout.session',
                'mode' => 'payment',
                'livemode' => false,
                'payment_status' => 'paid',
                'amount_total' => 2900,
                'currency' => 'usd',
                'client_reference_id' => $this->purchase->id,
                'metadata' => ['purchase_id' => $this->purchase->id],
            ], $session)],
        ];
    }

    private function deliver(array $payload, string $secret = 'whsec_testing', ?int $timestamp = null): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t=$timestamp,v1=$signature",
        ], $body);
    }

    public function test_signed_paid_event_grants_entitlement(): void
    {
        $this->deliver($this->payload())->assertOk();
        $this->assertNotNull($this->purchase->fresh()->paid_at);
        $this->assertNotNull($this->purchase->lead->fresh()->paid_at);
        $this->assertDatabaseHas('analytics_events', ['type' => 'paid']);
        $this->assertDatabaseCount('webhook_receipts', 1);
    }

    public function test_duplicate_event_and_distinct_events_for_same_payment_are_idempotent(): void
    {
        $this->deliver($this->payload())->assertOk();
        $firstPaidAt = $this->purchase->fresh()->paid_at;
        $this->travel(1)->minutes();
        $this->deliver($this->payload())->assertOk();
        $this->deliver($this->payload(id: 'evt_other'))->assertOk();
        $this->assertEquals($firstPaidAt, $this->purchase->fresh()->paid_at);
        $this->assertSame(1, AnalyticsEvent::where('type', 'paid')->count());
        $this->assertDatabaseCount('webhook_receipts', 2);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->deliver($this->payload(), 'wrong-secret')->assertStatus(400);
        $this->assertNull($this->purchase->fresh()->paid_at);
        $this->assertDatabaseCount('webhook_receipts', 0);
    }

    public function test_expired_signature_is_rejected(): void
    {
        $this->deliver($this->payload(), timestamp: time() - 600)->assertStatus(400);
        $this->assertNull($this->purchase->fresh()->paid_at);
    }

    public function test_unpaid_completion_waits_for_async_success(): void
    {
        $this->deliver($this->payload(['payment_status' => 'unpaid']))->assertOk();
        $this->assertNull($this->purchase->fresh()->paid_at);
        $this->assertDatabaseCount('webhook_receipts', 0);
        $this->deliver($this->payload(id: 'evt_async', type: 'checkout.session.async_payment_succeeded'))->assertOk();
        $this->assertNotNull($this->purchase->fresh()->paid_at);
    }

    public function test_wrong_amount_is_rejected(): void
    {
        $this->deliver($this->payload(['amount_total' => 1]))->assertStatus(422);
        $this->assertNull($this->purchase->fresh()->paid_at);
    }

    public function test_wrong_currency_is_rejected(): void
    {
        $this->deliver($this->payload(['currency' => 'eur']))->assertStatus(422);
        $this->assertNull($this->purchase->fresh()->paid_at);
    }

    public function test_wrong_purchase_reference_is_rejected(): void
    {
        $this->deliver($this->payload(['metadata' => ['purchase_id' => 'wrong']]))->assertStatus(422);
        $this->deliver($this->payload(['client_reference_id' => 'wrong']))->assertStatus(422);
        $this->assertNull($this->purchase->fresh()->paid_at);
    }

    public function test_unknown_checkout_requests_retry(): void
    {
        $this->deliver($this->payload(['id' => 'cs_unknown']))->assertStatus(409);
        $this->assertDatabaseCount('webhook_receipts', 0);
    }

    public function test_live_event_is_rejected(): void
    {
        $payload = $this->payload();
        $payload['livemode'] = true;
        $this->deliver($payload)->assertStatus(422);
        $this->assertNull($this->purchase->fresh()->paid_at);
    }

    public function test_unrelated_event_is_acknowledged_without_fulfillment(): void
    {
        $this->deliver($this->payload(type: 'customer.created'))->assertOk();
        $this->assertNull($this->purchase->fresh()->paid_at);
        $this->assertDatabaseCount('webhook_receipts', 0);
    }

    public function test_paid_feature_flag_does_not_drop_in_flight_payment(): void
    {
        config(['funnel.paid_enabled' => false]);
        $this->deliver($this->payload())->assertOk();
        $this->assertNotNull($this->purchase->fresh()->paid_at);
    }

    public function test_webhook_is_disabled_in_mock_mode(): void
    {
        config(['funnel.mock_stripe' => true]);
        $this->deliver($this->payload())->assertNotFound();
        $this->assertNull($this->purchase->fresh()->paid_at);
    }

    public function test_missing_webhook_secret_fails_closed(): void
    {
        config(['funnel.stripe_webhook_secret' => null]);
        $this->deliver($this->payload())->assertStatus(503);
        $this->assertNull($this->purchase->fresh()->paid_at);
    }
}
