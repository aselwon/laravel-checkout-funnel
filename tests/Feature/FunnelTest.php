<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Assignment;
use App\Models\Lead;
use App\Models\Purchase;
use App\Models\User;
use App\Services\CheckoutGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FunnelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function lead(bool $paid = false): Lead
    {
        $assignment = Assignment::create(['variant' => 'A']);
        $lead = Lead::create(['assignment_id' => $assignment->id, 'email' => 'seller@example.com']);
        if ($paid) {
            $lead->paid_at = now();
            $lead->save();
        }

        return $lead;
    }

    private function purchase(Lead $lead): Purchase
    {
        return Purchase::create(['lead_id' => $lead->id, 'mode' => 'mock', 'amount' => 2900, 'currency' => 'usd']);
    }

    public function test_first_visit_assigns_a_variant_and_records_one_visitor(): void
    {
        $this->get('/')->assertOk()->assertCookie('sb_visitor')->assertSee('The Fresh Start');
        $this->assertContains(Assignment::first()->variant, ['A', 'B']);
        $this->assertDatabaseCount('assignments', 1);
        $this->assertDatabaseHas('analytics_events', ['type' => 'visitor']);
    }

    public function test_returning_cookie_keeps_variant_and_deduplicates_visit(): void
    {
        $assignment = Assignment::create(['variant' => 'B']);
        $this->withCookie('sb_visitor', $assignment->id)->get('/')->assertSee('Less guesswork.');
        $this->withCookie('sb_visitor', $assignment->id)->get('/')->assertOk();
        $this->assertDatabaseCount('assignments', 1);
        $this->assertDatabaseCount('analytics_events', 1);
    }

    public function test_malformed_cookie_creates_fresh_assignment(): void
    {
        $this->withCookie('sb_visitor', 'not-a-uuid')->get('/')->assertOk();
        $this->assertDatabaseCount('assignments', 1);
    }

    public function test_free_signup_creates_workspace_and_conversion(): void
    {
        $this->post('/signup', ['email' => 'Maker@Example.com'])->assertRedirect('/onboarding');
        $lead = Lead::firstOrFail();
        $this->assertSame('maker@example.com', $lead->email);
        $this->assertNull($lead->paid_at);
        $this->assertSame($lead->id, session('lead_id'));
        $this->get('/onboarding')->assertOk()->assertSee('Your growth checklist');
        $this->assertDatabaseHas('analytics_events', ['type' => 'signup']);
    }

    public function test_repeated_signup_is_counted_once(): void
    {
        $assignment = Assignment::create(['variant' => 'A']);
        $this->withCookie('sb_visitor', $assignment->id)->post('/signup', ['email' => 'maker@example.com']);
        $this->withCookie('sb_visitor', $assignment->id)->post('/signup', ['email' => 'maker@example.com']);
        $this->assertDatabaseCount('leads', 1);
        $this->assertSame(1, AnalyticsEvent::where('type', 'signup')->count());
    }

    public function test_invalid_email_is_rejected_without_signup(): void
    {
        $this->post('/signup', ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->post('/signup', ['email' => "seller@example.com\r\nBcc:other@example.com"])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_email_alone_cannot_claim_another_visitors_paid_access(): void
    {
        $paidLead = $this->lead(true);
        $this->post('/signup', ['email' => $paidLead->email])->assertRedirect('/onboarding');
        $this->assertNotSame($paidLead->id, session('lead_id'));
        $this->get('/growth-kit')->assertRedirect('/onboarding');
    }

    public function test_guest_cannot_open_workspace_or_paid_kit(): void
    {
        $this->get('/onboarding')->assertRedirect('/');
        $this->get('/growth-kit')->assertRedirect('/');
    }

    public function test_free_lead_is_denied_paid_kit(): void
    {
        $this->withSession(['lead_id' => $this->lead()->id])->get('/growth-kit')->assertRedirect('/onboarding');
    }

    public function test_paid_lead_can_open_kit(): void
    {
        $this->withSession(['lead_id' => $this->lead(true)->id])->get('/growth-kit')->assertOk()->assertSee('COPY TEMPLATE');
    }

    public function test_checklist_progress_persists_and_can_be_cleared(): void
    {
        $lead = $this->lead();
        $this->withSession(['lead_id' => $lead->id])->post('/onboarding', ['completed' => ['promise', 'proof']])->assertRedirect('/onboarding');
        $this->assertSame(['promise', 'proof'], $lead->fresh()->checklist);
        $this->post('/onboarding')->assertRedirect('/onboarding');
        $this->assertSame([], $lead->fresh()->checklist);
    }

    public function test_unknown_checklist_items_are_rejected(): void
    {
        $this->withSession(['lead_id' => $this->lead()->id])->post('/onboarding', ['completed' => ['hacked']])->assertSessionHasErrors('completed.0');
    }

    public function test_mock_checkout_completes_and_deduplicates_paid_conversion(): void
    {
        $lead = $this->lead();
        $response = $this->withSession(['lead_id' => $lead->id])->post('/checkout');
        $purchase = Purchase::firstOrFail();
        $response->assertRedirect(route('checkout.mock', $purchase));
        $this->get(route('checkout.mock', $purchase))->assertOk();
        $this->post(route('checkout.complete', $purchase))->assertRedirect(route('checkout.success', $purchase));
        $firstPaidAt = $lead->fresh()->paid_at;
        $this->travel(2)->minutes();
        $this->post(route('checkout.complete', $purchase))->assertRedirect();
        $this->assertEquals($firstPaidAt, $lead->fresh()->paid_at);
        $this->assertSame(1, AnalyticsEvent::where('type', 'paid')->count());
        $this->get(route('checkout.success', $purchase))->assertSee('is unlocked.');
        $this->get('/growth-kit')->assertOk();
    }

    public function test_success_page_does_not_fulfill_unpaid_purchase(): void
    {
        $lead = $this->lead();
        $purchase = $this->purchase($lead);
        $this->withSession(['lead_id' => $lead->id])->get(route('checkout.success', $purchase))->assertOk()->assertSee('WAITING FOR CONFIRMATION');
        $this->assertNull($lead->fresh()->paid_at);
        $this->assertNull($purchase->fresh()->paid_at);
    }

    public function test_another_session_cannot_access_or_fulfill_purchase(): void
    {
        $purchase = $this->purchase($this->lead());
        $this->withSession(['lead_id' => $this->lead()->id])->get(route('checkout.mock', $purchase))->assertNotFound();
        $this->post(route('checkout.complete', $purchase))->assertNotFound();
        $this->get(route('checkout.success', $purchase))->assertNotFound();
        $this->assertNull($purchase->fresh()->paid_at);
    }

    public function test_paid_flag_hides_offer_and_blocks_checkout_but_preserves_entitlement(): void
    {
        config(['funnel.paid_enabled' => false]);
        $this->get('/')->assertDontSee('Start free, then unlock the kit');
        $lead = $this->lead(true);
        $purchase = $this->purchase($lead);
        $this->withSession(['lead_id' => $lead->id])->post('/checkout')->assertNotFound();
        $this->post(route('checkout.complete', $purchase))->assertNotFound();
        $this->get('/growth-kit')->assertOk();
    }

    public function test_mock_endpoints_are_disabled_in_stripe_mode(): void
    {
        config(['funnel.mock_stripe' => false]);
        $lead = $this->lead();
        $purchase = $this->purchase($lead);
        $this->withSession(['lead_id' => $lead->id])->get(route('checkout.mock', $purchase))->assertNotFound();
        $this->post(route('checkout.complete', $purchase))->assertNotFound();
    }

    public function test_checkout_failure_is_recoverable_and_never_grants_access(): void
    {
        $this->mock(CheckoutGateway::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andThrow(new \RuntimeException('Provider unavailable'));
        });
        $lead = $this->lead();
        $this->withSession(['lead_id' => $lead->id])->post('/checkout')->assertRedirect('/onboarding')->assertSessionHasErrors('checkout');
        $this->assertNull($lead->fresh()->paid_at);
        $this->assertSame(0, AnalyticsEvent::where('type', 'checkout')->count());
    }

    public function test_guest_admin_redirect_and_non_admin_forbidden(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admin_can_login_view_metrics_and_logout(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => Hash::make('A-secure-demo-password'), 'is_admin' => true]);
        $assignment = Assignment::create(['variant' => 'B']);
        AnalyticsEvent::record($assignment->id, 'visitor');
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'A-secure-demo-password'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk()->assertSee('Recent assignments')->assertViewHas('metrics', fn ($metrics) => $metrics['visitor'] === 1);
        $this->post('/admin/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_non_admin_credentials_do_not_authenticate(): void
    {
        User::factory()->create(['email' => 'seller@example.com', 'password' => Hash::make('password'), 'is_admin' => false]);
        $this->post('/admin/login', ['email' => 'seller@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_login_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertSessionHasErrors(['email' => 'Too many attempts. Try again in a minute.']);
    }

    public function test_empty_admin_dashboard_has_zero_safe_rates(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('0.0%')->assertSee('Your funnel is ready.');
    }

    public function test_browser_forms_require_csrf_token(): void
    {
        $this->app['env'] = 'local';
        $this->post('/signup', ['email' => 'seller@example.com'])->assertStatus(419);
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'anything'])->assertStatus(419);
    }

    public function test_seed_is_repeatable_and_does_not_reset_existing_password(): void
    {
        config(['funnel.admin_email' => 'admin@seed.test', 'funnel.admin_password' => 'First-demo-password']);
        $this->seed();
        $admin = User::where('email', 'admin@seed.test')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        config(['funnel.admin_password' => 'Second-demo-password']);
        $this->seed();
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('First-demo-password', $admin->fresh()->password));
    }
}
