<?php

namespace Tests\Feature;

use App\Models\MonitoringSubscription;
use App\Services\MonitoringRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('contact');
    }

    public function test_landing_page_renders(): void
    {
        $this->get('/monitoring')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/MonitoringSubscribe')
                ->where('price.cents', 4900)
                ->where('price.label', '49,00 €')
                ->where('period_days', 30)
            );
    }

    public function test_stub_subscription_activates_immediately(): void
    {
        $this->post('/monitoring/subscribe', [
            'url' => 'https://client.example',
            'email' => 'client@exemple.fr',
            'confirm' => '1',
        ])->assertRedirect();

        $sub = MonitoringSubscription::first();
        $this->assertNotNull($sub);
        $this->assertSame('active', $sub->status);
        $this->assertTrue($sub->active_until->isFuture());
        $this->assertStringStartsWith('STUB-MON-', $sub->payment_reference);
    }

    public function test_subscription_requires_confirmation(): void
    {
        $this->post('/monitoring/subscribe', [
            'url' => 'https://noconf.example',
            'email' => 'a@a.fr',
        ])->assertSessionHasErrors('confirm');
    }

    public function test_dashboard_is_accessible_by_token_only(): void
    {
        $sub = MonitoringSubscription::create([
            'url' => 'https://dash.example',
            'email' => 'dash@exemple.fr',
            'price_cents' => 4900,
            'status' => 'active',
            'active_until' => now()->addDays(30),
        ]);

        $this->get('/monitoring/'.$sub->token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/MonitoringDashboard')
                ->where('subscription.token', $sub->token)
                ->where('subscription.active', true)
            );
    }

    public function test_cancel_moves_subscription_to_cancelled(): void
    {
        $sub = MonitoringSubscription::create([
            'url' => 'https://cancel.example',
            'email' => 'c@c.fr',
            'price_cents' => 4900,
            'status' => 'active',
            'active_until' => now()->addDays(30),
        ]);

        $this->post('/monitoring/'.$sub->token.'/cancel')->assertRedirect();

        $sub->refresh();
        $this->assertSame('cancelled', $sub->status);
    }

    public function test_monitoring_runner_creates_audit_and_updates_subscription(): void
    {
        Mail::fake();
        Http::fake([
            'example.com' => Http::response('<html lang="fr"><head><title>Kodem audit</title><meta name="description" content="Une description suffisamment longue pour passer le test de longueur."><meta name="viewport"><link rel="canonical" href="https://example.com"></head><body><h1>H</h1></body></html>', 200, [
                'Strict-Transport-Security' => 'max-age=63072000',
                'Content-Security-Policy' => "default-src 'self'",
                'X-Frame-Options' => 'DENY',
                'X-Content-Type-Options' => 'nosniff',
                'Referrer-Policy' => 'strict-origin',
                'Permissions-Policy' => 'camera=()',
            ]),
            '*' => Http::response('', 200, []),
        ]);

        $sub = MonitoringSubscription::create([
            'url' => 'https://example.com',
            'email' => 'run@exemple.fr',
            'price_cents' => 4900,
            'status' => 'active',
            'active_until' => now()->addDays(30),
        ]);

        $runner = app(MonitoringRunner::class);
        $result = $runner->run($sub);

        $sub->refresh();
        $this->assertNotNull($sub->last_run_at);
        $this->assertNotNull($sub->last_score_total);
        $this->assertSame($result['audit']->uuid, $sub->last_audit_uuid);
        $this->assertTrue($result['audit']->isPaid(), 'monitoring audit must be paid');
    }

    public function test_monitoring_runner_flags_alert_on_score_drop(): void
    {
        Mail::fake();
        Http::fake([
            '*' => Http::response('<html><body>poor</body></html>', 200, []),
        ]);

        $sub = MonitoringSubscription::create([
            'url' => 'https://drop.example',
            'email' => 'drop@exemple.fr',
            'price_cents' => 4900,
            'status' => 'active',
            'active_until' => now()->addDays(30),
            'last_score_total' => 90,
        ]);

        $result = app(MonitoringRunner::class)->run($sub);

        $this->assertTrue($result['alert'], 'alert should fire when score drops by ≥ threshold');
    }

    public function test_command_only_runs_active_subscriptions(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response('<html><body></body></html>', 200, [])]);

        MonitoringSubscription::create([
            'url' => 'https://active.example', 'email' => 'a@a.fr',
            'price_cents' => 4900, 'status' => 'active', 'active_until' => now()->addDays(30),
        ]);
        MonitoringSubscription::create([
            'url' => 'https://cancelled.example', 'email' => 'b@b.fr',
            'price_cents' => 4900, 'status' => 'cancelled', 'active_until' => now()->addDays(30),
        ]);
        MonitoringSubscription::create([
            'url' => 'https://expired.example', 'email' => 'c@c.fr',
            'price_cents' => 4900, 'status' => 'active', 'active_until' => now()->subDay(),
        ]);

        $this->artisan('monitoring:run')->assertSuccessful();

        $this->assertNotNull(MonitoringSubscription::where('url', 'https://active.example')->first()->last_run_at);
        $this->assertNull(MonitoringSubscription::where('url', 'https://cancelled.example')->first()->last_run_at);

        $expired = MonitoringSubscription::where('url', 'https://expired.example')->first();
        $this->assertSame('expired', $expired->status);
    }
}
