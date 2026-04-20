<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function paidAudit(array $overrides = []): Audit
    {
        return Audit::create(array_merge([
            'url' => 'https://pdf.example',
            'status' => 'completed',
            'score_seo' => 80,
            'score_security' => 90,
            'score_total' => 85,
            'results' => [
                'seo' => ['checks' => [['key' => 'title', 'label' => 'Title', 'status' => 'pass', 'detail' => 'ok']]],
                'security' => ['checks' => [['key' => 'https', 'label' => 'HTTPS', 'status' => 'pass', 'detail' => 'ok']]],
            ],
            'price_cents' => 2900,
            'paid_at' => now(),
            'pdf_price_cents' => 900,
            'cwv_price_cents' => 1900,
            'ip_hash' => str_repeat('a', 64),
        ], $overrides));
    }

    public function test_unpaid_audit_cannot_access_pdf_flow(): void
    {
        $audit = $this->paidAudit(['paid_at' => null]);

        $this->get('/audit/'.$audit->uuid.'/pdf/pay')
            ->assertRedirect(route('audit.pay', $audit->uuid));

        $this->get('/audit/'.$audit->uuid.'/pdf')
            ->assertRedirect(route('audit.pay', $audit->uuid));
    }

    public function test_pdf_checkout_renders_when_unpaid(): void
    {
        $audit = $this->paidAudit();

        $this->get('/audit/'.$audit->uuid.'/pdf/pay')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/AuditPdfCheckout')
                ->where('price.cents', 900)
                ->where('price.label', '9,00 €')
            );
    }

    public function test_stub_payment_unlocks_pdf_and_download_works(): void
    {
        $audit = $this->paidAudit();

        $this->post('/audit/'.$audit->uuid.'/pdf/pay', ['confirm' => '1'])
            ->assertRedirect(route('audit.pdf', $audit->uuid));

        $audit->refresh();
        $this->assertNotNull($audit->pdf_paid_at);

        $response = $this->get('/audit/'.$audit->uuid.'/pdf');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_admin_downloads_pdf_without_addon_payment(): void
    {
        $audit = $this->paidAudit();
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/audit/'.$audit->uuid.'/pdf');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_paid_pdf_redirects_from_checkout_to_download(): void
    {
        $audit = $this->paidAudit(['pdf_paid_at' => now()]);

        $this->get('/audit/'.$audit->uuid.'/pdf/pay')
            ->assertRedirect(route('audit.pdf', $audit->uuid));
    }
}
