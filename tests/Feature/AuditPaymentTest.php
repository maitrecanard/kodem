<?php

namespace Tests\Feature;

use App\Models\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_renders_for_unpaid_audit(): void
    {
        $audit = Audit::create([
            'url' => 'https://shop.example',
            'status' => 'completed',
            'score_total' => 75,
            'price_cents' => 2900,
            'ip_hash' => str_repeat('a', 64),
        ]);

        $this->get('/audit/'.$audit->uuid.'/pay')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/AuditCheckout')
                ->where('audit.uuid', $audit->uuid)
                ->where('price.cents', 2900)
                ->where('price.label', '29,00 €')
                ->where('driver', 'stub')
            );
    }

    public function test_paid_audit_redirects_away_from_checkout(): void
    {
        $audit = Audit::create([
            'url' => 'https://paid.example',
            'status' => 'completed',
            'score_total' => 80,
            'price_cents' => 2900,
            'paid_at' => now(),
            'payment_reference' => 'STUB-DEJAPAYE',
            'ip_hash' => str_repeat('b', 64),
        ]);

        $this->get('/audit/'.$audit->uuid.'/pay')
            ->assertRedirect(route('audit.show', $audit->uuid));
    }

    public function test_stub_payment_marks_audit_as_paid(): void
    {
        $audit = Audit::create([
            'url' => 'https://buy.example',
            'status' => 'completed',
            'score_total' => 60,
            'price_cents' => 2900,
            'ip_hash' => str_repeat('c', 64),
        ]);

        $this->post('/audit/'.$audit->uuid.'/pay', ['confirm' => '1'])
            ->assertRedirect(route('audit.show', $audit->uuid));

        $audit->refresh();
        $this->assertNotNull($audit->paid_at);
        $this->assertStringStartsWith('STUB-', $audit->payment_reference);
    }

    public function test_stub_payment_requires_confirmation(): void
    {
        $audit = Audit::create([
            'url' => 'https://forgot.example',
            'status' => 'completed',
            'score_total' => 60,
            'price_cents' => 2900,
            'ip_hash' => str_repeat('d', 64),
        ]);

        $this->post('/audit/'.$audit->uuid.'/pay', [])
            ->assertSessionHasErrors('confirm');

        $audit->refresh();
        $this->assertNull($audit->paid_at);
    }

    public function test_already_paid_audit_cannot_be_paid_again(): void
    {
        $audit = Audit::create([
            'url' => 'https://double.example',
            'status' => 'completed',
            'score_total' => 70,
            'price_cents' => 2900,
            'paid_at' => now(),
            'payment_reference' => 'STUB-ORIGINAL',
            'ip_hash' => str_repeat('e', 64),
        ]);

        $this->post('/audit/'.$audit->uuid.'/pay', ['confirm' => '1'])
            ->assertRedirect(route('audit.show', $audit->uuid));

        $audit->refresh();
        $this->assertSame('STUB-ORIGINAL', $audit->payment_reference);
    }
}
