<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('tracking');
    }

    public function test_valid_event_is_stored(): void
    {
        $this->withSession([])
            ->post('/track', [
                'type' => 'button_click',
                'name' => 'hero_cta_audit',
                'metadata' => ['location' => 'home_hero'],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, Event::count());
        $e = Event::first();
        $this->assertSame('button_click', $e->type);
        $this->assertSame('hero_cta_audit', $e->name);
        $this->assertSame('home_hero', $e->metadata['location']);
    }

    public function test_ip_is_hashed_in_events(): void
    {
        $this->post('/track', ['type' => 'view', 'name' => 'home']);

        $e = Event::first();
        $this->assertNotNull($e->ip_hash);
        $this->assertSame(64, strlen($e->ip_hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $e->ip_hash);
    }

    public function test_invalid_type_or_name_is_rejected(): void
    {
        $this->post('/track', ['type' => '', 'name' => 'x'])->assertSessionHasErrors('type');
        $this->post('/track', ['type' => 'invalid type with spaces', 'name' => 'x'])->assertSessionHasErrors('type');
        $this->post('/track', ['type' => 'ok', 'name' => ''])->assertSessionHasErrors('name');
        $this->assertSame(0, Event::count());
    }

    public function test_tracking_is_rate_limited(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->post('/track', ['type' => 'x', 'name' => 'y'])->assertOk();
        }
        $this->post('/track', ['type' => 'x', 'name' => 'y'])->assertStatus(429);
    }

    public function test_authenticated_user_id_is_attached(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/track', ['type' => 'view', 'name' => 'profile']);

        $e = Event::first();
        $this->assertSame($user->id, $e->user_id);
    }
}
