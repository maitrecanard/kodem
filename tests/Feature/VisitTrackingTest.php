<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_visit_is_tracked(): void
    {
        $this->get('/prestations')->assertOk();
        $this->assertSame(1, PageVisit::count());
        $this->assertSame('prestations', PageVisit::first()->url);
    }

    public function test_admin_routes_are_not_tracked(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user)->get('/admin/2fa/setup');

        $this->assertSame(0, PageVisit::where('url', 'like', 'admin%')->count());
    }

    public function test_ip_is_hashed_not_stored_plain(): void
    {
        $this->get('/')->assertOk();

        $visit = PageVisit::first();
        $this->assertNotNull($visit);
        $this->assertSame(64, strlen($visit->ip_hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $visit->ip_hash);
    }
}
