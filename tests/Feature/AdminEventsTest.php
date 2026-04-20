<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\PageVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_surfaces_events_and_funnel(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'google2fa_enabled' => true,
            'google2fa_secret' => 'JBSWY3DPEHPK3PXP',
        ]);

        // visites
        PageVisit::create(['url' => '/', 'ip_hash' => str_repeat('a', 64), 'created_at' => now()]);
        PageVisit::create(['url' => '/audit', 'ip_hash' => str_repeat('b', 64), 'created_at' => now()]);

        // funnel
        Event::create(['type' => 'audit.submitted', 'name' => 'a', 'created_at' => now()]);
        Event::create(['type' => 'audit.submitted', 'name' => 'b', 'created_at' => now()]);
        Event::create(['type' => 'audit.paid', 'name' => 'a', 'created_at' => now()]);
        Event::create(['type' => 'button_click', 'name' => 'hero_cta_audit', 'created_at' => now()]);
        Event::create(['type' => 'button_click', 'name' => 'hero_cta_audit', 'created_at' => now()]);
        Event::create(['type' => 'button_click', 'name' => 'hero_cta_audit', 'created_at' => now()]);

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get('/admin/events')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Events')
                ->where('stats.events_30d', 6)
                ->has('funnel', 6)
                ->where('funnel.0.step', 'visits')
                ->where('funnel.0.count', 2)
                ->where('funnel.1.step', 'audit_started')
                ->where('funnel.1.count', 2)
                ->where('funnel.2.count', 1)
                ->has('topEvents')
                ->has('recent')
            );
    }

    public function test_admin_events_requires_auth_and_2fa(): void
    {
        $this->get('/admin/events')->assertRedirect('/login');

        $admin = User::factory()->create(['is_admin' => true]);
        // sans 2fa_verified
        $this->actingAs($admin)
            ->get('/admin/events')
            ->assertRedirect(route('admin.2fa.setup'));
    }
}
