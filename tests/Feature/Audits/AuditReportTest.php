<?php

namespace Tests\Feature\Audits;

use App\Modules\Audits\Models\AuditRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeToken(): string
    {
        return Str::random(64);
    }

    private function createAuditRequest(array $attributes = []): AuditRequest
    {
        return AuditRequest::create(array_merge([
            'name'         => 'Jean Test',
            'email'        => 'jean@example.com',
            'domain'       => 'example.fr',
            'type'         => 'premium',
            'status'       => 'queued',
            'options'      => ['seo' => true, 'security' => true],
            'amount_cents' => 14900,
            'currency'     => 'eur',
            'access_token' => $this->makeToken(),
            'paid_at'      => now(),
        ], $attributes));
    }

    public function test_valid_paid_completed_within_ttl_returns_ok(): void
    {
        $ttl = config('audits.report_token_ttl_days');
        $audit = $this->createAuditRequest([
            'status'  => 'completed',
            'paid_at' => now()->subDays($ttl - 1),
        ]);

        $this->get(route('audits.report', $audit->access_token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Audits/Report')
                ->where('domain', $audit->domain)
                ->where('status', 'completed')
            );
    }

    public function test_queued_status_paid_returns_ok(): void
    {
        $audit = $this->createAuditRequest([
            'status'  => 'queued',
            'paid_at' => now(),
        ]);

        $this->get(route('audits.report', $audit->access_token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Audits/Report'));
    }

    public function test_expired_paid_at_returns_not_found(): void
    {
        $ttl = config('audits.report_token_ttl_days');
        $audit = $this->createAuditRequest([
            'paid_at' => now()->subDays($ttl + 1),
        ]);

        $this->get(route('audits.report', $audit->access_token))
            ->assertNotFound();
    }

    public function test_unpaid_returns_not_found(): void
    {
        $audit = $this->createAuditRequest([
            'paid_at' => null,
        ]);

        $this->get(route('audits.report', $audit->access_token))
            ->assertNotFound();
    }

    public function test_unknown_64_char_token_returns_not_found(): void
    {
        $this->get(route('audits.report', $this->makeToken()))
            ->assertNotFound();
    }

    public function test_malformed_token_too_short_returns_not_found(): void
    {
        $shortToken = Str::random(10);

        $this->get("/r/{$shortToken}")
            ->assertNotFound();
    }

    public function test_no_sensitive_field_leak_on_valid_response(): void
    {
        $audit = $this->createAuditRequest([
            'status'  => 'completed',
            'paid_at' => now(),
        ]);

        $this->get(route('audits.report', $audit->access_token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Audits/Report')
                ->missing('id')
                ->missing('access_token')
                ->missing('stripe_session_id')
                ->missing('stripe_payment_intent')
                ->missing('ip_address')
                ->missing('user_agent')
                ->missing('email')
                ->missing('name')
                ->missing('uuid')
            );
    }

    public function test_boundary_just_within_ttl_returns_ok(): void
    {
        $ttl = config('audits.report_token_ttl_days');
        $audit = $this->createAuditRequest([
            'paid_at' => now()->subDays($ttl)->addMinute(),
        ]);

        $this->get(route('audits.report', $audit->access_token))
            ->assertOk();
    }
}
