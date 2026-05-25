<?php

namespace Tests\Unit\Audits;

use App\Modules\Audits\Models\AuditRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditRequestModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeAudit(array $overrides = []): AuditRequest
    {
        return AuditRequest::create(array_merge([
            'type' => 'premium',
            'status' => 'pending_payment',
            'name' => 'Test',
            'email' => 'test@example.com',
            'domain' => 'example.fr',
            'options' => ['seo' => true, 'security' => true],
            'access_token' => str_repeat('a', 64),
        ], $overrides));
    }

    public function test_uuid_is_auto_generated_on_create(): void
    {
        $audit = $this->makeAudit();

        $this->assertNotEmpty($audit->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $audit->uuid);
    }

    public function test_uuid_is_unique_per_record(): void
    {
        $a = $this->makeAudit(['email' => 'a@example.com', 'access_token' => str_repeat('a', 64)]);
        $b = $this->makeAudit(['email' => 'b@example.com', 'access_token' => str_repeat('b', 64)]);

        $this->assertNotEquals($a->uuid, $b->uuid);
    }

    public function test_route_key_is_uuid(): void
    {
        $audit = $this->makeAudit();
        $this->assertSame('uuid', $audit->getRouteKeyName());
    }

    public function test_hidden_attributes_include_id_and_secrets(): void
    {
        $audit = $this->makeAudit([
            'access_token' => str_repeat('z', 64),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'stripe_session_id' => 'cs_secret',
            'stripe_payment_intent' => 'pi_secret',
        ]);

        $array = $audit->toArray();

        $this->assertArrayNotHasKey('id', $array);
        $this->assertArrayNotHasKey('access_token', $array);
        $this->assertArrayNotHasKey('ip_address', $array);
        $this->assertArrayNotHasKey('user_agent', $array);
        $this->assertArrayNotHasKey('stripe_session_id', $array);
        $this->assertArrayNotHasKey('stripe_payment_intent', $array);
        $this->assertArrayHasKey('uuid', $array);
        $this->assertArrayHasKey('domain', $array);
    }

    public function test_options_is_cast_to_array(): void
    {
        $audit = $this->makeAudit([
            'options' => ['seo' => true, 'security' => false],
        ]);

        $this->assertIsArray($audit->fresh()->options);
        $this->assertTrue($audit->fresh()->options['seo']);
        $this->assertFalse($audit->fresh()->options['security']);
    }
}
