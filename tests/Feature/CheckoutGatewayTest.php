<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Lead;
use App\Models\Purchase;
use App\Services\CheckoutGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Tests\TestCase;

class CheckoutGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_checkout_sends_test_mode_session_and_persists_provider_id(): void
    {
        config(['funnel.stripe_secret' => 'sk_test_example', 'funnel.stripe_price_id' => 'price_test_29']);
        $assignment = Assignment::create(['variant' => 'A']);
        $lead = Lead::create(['assignment_id' => $assignment->id, 'email' => 'seller@example.com']);
        $purchase = Purchase::create(['lead_id' => $lead->id, 'mode' => 'stripe', 'amount' => 2900, 'currency' => 'usd']);
        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')->once()->withArgs(function ($method, $url, $headers, $params, $hasFile, $apiMode = null) use ($purchase) {
            $this->assertSame('post', $method);
            $this->assertStringEndsWith('/v1/checkout/sessions', $url);
            $this->assertSame('payment', $params['mode']);
            $this->assertSame('price_test_29', $params['line_items'][0]['price']);
            $this->assertSame($purchase->id, $params['metadata']['purchase_id']);
            $this->assertSame($purchase->id, $params['client_reference_id']);
            $this->assertSame('seller@example.com', $params['customer_email']);
            $this->assertSame(route('checkout.success', $purchase), $params['success_url']);

            return true;
        })->andReturn([json_encode(['id' => 'cs_test_created', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_created']), 200, []]);
        ApiRequestor::setHttpClient($client);
        try {
            $url = app(CheckoutGateway::class)->create($purchase);
            $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_created', $url);
            $this->assertSame('cs_test_created', $purchase->fresh()->stripe_session_id);
        } finally {
            ApiRequestor::setHttpClient(CurlClient::instance());
        }
    }

    public function test_live_secret_is_refused_before_any_network_call(): void
    {
        config(['funnel.stripe_secret' => 'sk_live_example', 'funnel.stripe_price_id' => 'price_example']);
        $purchase = new Purchase(['mode' => 'stripe']);
        $this->expectException(\RuntimeException::class);
        app(CheckoutGateway::class)->create($purchase);
    }
}
