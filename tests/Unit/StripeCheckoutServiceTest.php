<?php

namespace Tests\Unit;

use App\Models\Audit;
use App\Services\StripeCheckoutService;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeCheckoutServiceTest extends TestCase
{
    private function service(): StripeCheckoutService
    {
        // Client factice : aucune requête réseau n'est émise par buildAuditSessionParams().
        return new StripeCheckoutService(new StripeClient('sk_test_dummy'));
    }

    private function audit(array $attributes = []): Audit
    {
        return new Audit(array_merge([
            'uuid' => '11111111-2222-3333-4444-555555555555',
            'url' => 'https://exemple.fr',
            'price_cents' => 2900,
        ], $attributes));
    }

    public function test_it_builds_a_one_time_payment_for_the_buyer_email(): void
    {
        $params = $this->service()->buildAuditSessionParams($this->audit(), 'client@example.com', 'tok-abc');

        $this->assertSame('payment', $params['mode']);
        $this->assertSame('client@example.com', $params['customer_email']);
    }

    public function test_line_item_uses_audit_price_in_euros(): void
    {
        $params = $this->service()->buildAuditSessionParams(
            $this->audit(['price_cents' => 4500]),
            'client@example.com',
            'tok-abc'
        );

        $item = $params['line_items'][0];
        $this->assertSame(1, $item['quantity']);
        $this->assertSame(4500, $item['price_data']['unit_amount']);
        $this->assertSame('eur', $item['price_data']['currency']);
        $this->assertStringContainsString('Kodem', $item['price_data']['product_data']['name']);
        $this->assertStringContainsString('https://exemple.fr', $item['price_data']['product_data']['description']);
    }

    public function test_success_url_carries_the_uuid_and_private_token(): void
    {
        $audit = $this->audit();
        $params = $this->service()->buildAuditSessionParams($audit, 'client@example.com', 'super-secret-token');

        $this->assertStringContainsString('/audit/'.$audit->uuid.'/pay/confirm', $params['success_url']);
        $this->assertStringContainsString('token=super-secret-token', $params['success_url']);
    }

    public function test_cancel_url_points_back_to_the_checkout_page(): void
    {
        $audit = $this->audit();
        $params = $this->service()->buildAuditSessionParams($audit, 'client@example.com', 'tok-abc');

        $this->assertSame(route('audit.pay', ['audit' => $audit->uuid]), $params['cancel_url']);
    }

    public function test_metadata_carries_the_uuid_and_token(): void
    {
        $audit = $this->audit();
        $params = $this->service()->buildAuditSessionParams($audit, 'client@example.com', 'tok-xyz');

        $this->assertSame($audit->uuid, $params['metadata']['audit_uuid']);
        $this->assertSame('tok-xyz', $params['metadata']['token']);
    }

    public function test_session_expires_in_about_thirty_minutes(): void
    {
        $params = $this->service()->buildAuditSessionParams($this->audit(), 'client@example.com', 'tok-abc');

        $this->assertEqualsWithDelta(now()->addMinutes(30)->timestamp, $params['expires_at'], 5);
    }
}
