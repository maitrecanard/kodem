<?php

namespace Tests\Feature;

use App\Models\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditCwvTest extends TestCase
{
    use RefreshDatabase;

    protected function paidAudit(array $overrides = []): Audit
    {
        return Audit::create(array_merge([
            'url' => 'https://cwv.example',
            'status' => 'completed',
            'score_total' => 80,
            'price_cents' => 2900,
            'paid_at' => now(),
            'pdf_price_cents' => 900,
            'cwv_price_cents' => 1900,
            'ip_hash' => str_repeat('b', 64),
        ], $overrides));
    }

    protected function pageSpeedPayload(): array
    {
        return [
            'lighthouseResult' => [
                'categories' => [
                    'performance' => ['score' => 0.87],
                ],
                'audits' => [
                    'largest-contentful-paint' => ['displayValue' => '2.3 s'],
                    'cumulative-layout-shift' => ['displayValue' => '0.04'],
                    'interaction-to-next-paint' => ['displayValue' => '180 ms'],
                    'first-contentful-paint' => ['displayValue' => '1.1 s'],
                    'total-blocking-time' => ['displayValue' => '150 ms'],
                ],
            ],
        ];
    }

    public function test_cwv_checkout_renders_when_unpaid(): void
    {
        $audit = $this->paidAudit();

        $this->get('/audit/'.$audit->uuid.'/performance/pay')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/AuditCwvCheckout')
                ->where('price.cents', 1900)
                ->where('price.label', '19,00 €')
            );
    }

    public function test_stub_payment_calls_pagespeed_and_stores_results(): void
    {
        Http::fake([
            '*pagespeedonline*' => Http::response($this->pageSpeedPayload(), 200),
        ]);

        $audit = $this->paidAudit();

        $this->post('/audit/'.$audit->uuid.'/performance/pay', ['confirm' => '1'])
            ->assertRedirect(route('audit.cwv', $audit->uuid));

        $audit->refresh();
        $this->assertNotNull($audit->cwv_paid_at);
        $this->assertSame(87, $audit->cwv_results['performance_score']);
        $this->assertSame('2.3 s', $audit->cwv_results['lcp']);
        $this->assertSame('0.04', $audit->cwv_results['cls']);
    }

    public function test_unpaid_audit_cannot_reach_cwv_flow(): void
    {
        $audit = $this->paidAudit(['paid_at' => null]);

        $this->get('/audit/'.$audit->uuid.'/performance')
            ->assertRedirect(route('audit.pay', $audit->uuid));
        $this->get('/audit/'.$audit->uuid.'/performance/pay')
            ->assertRedirect(route('audit.pay', $audit->uuid));
    }

    public function test_cwv_show_renders_results_when_paid(): void
    {
        $audit = $this->paidAudit([
            'cwv_paid_at' => now(),
            'cwv_results' => [
                'status' => 'completed',
                'performance_score' => 92,
                'lcp' => '1.9 s', 'cls' => '0.02',
                'inp' => '120 ms', 'fcp' => '0.9 s', 'tbt' => '80 ms',
                'strategy' => 'mobile',
                'fetched_at' => now()->toIso8601String(),
            ],
        ]);

        $this->get('/audit/'.$audit->uuid.'/performance')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/AuditCwv')
                ->where('audit.cwv_results.performance_score', 92)
            );
    }
}
