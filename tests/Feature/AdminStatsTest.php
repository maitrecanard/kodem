<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\PageVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'google2fa_enabled' => true,
            'google2fa_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
    }

    public function test_stats_requires_auth_and_2fa(): void
    {
        $this->get('/admin/stats')->assertRedirect('/login');

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->get('/admin/stats')
            ->assertRedirect(route('admin.2fa.setup'));
    }

    public function test_stats_aggregates_visits_provenance_and_clicks(): void
    {
        $admin = $this->admin();

        PageVisit::create(['url' => '/', 'ip_hash' => str_repeat('a', 64), 'referer' => null, 'created_at' => now()]);
        PageVisit::create(['url' => '/notes', 'ip_hash' => str_repeat('b', 64), 'referer' => 'https://www.google.com/search?q=kodem', 'created_at' => now()]);
        PageVisit::create(['url' => '/prestations', 'ip_hash' => str_repeat('c', 64), 'referer' => 'https://www.linkedin.com/feed', 'created_at' => now()]);

        Event::create(['type' => 'button_click', 'name' => 'hero_cta', 'url' => '/', 'created_at' => now()]);
        Event::create(['type' => 'button_click', 'name' => 'hero_cta', 'url' => '/', 'created_at' => now()]);
        Event::create(['type' => 'button_click', 'name' => 'note_read', 'url' => '/notes', 'created_at' => now()]);

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get('/admin/stats?period=day')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Stats')
                ->where('period', 'day')
                ->where('stats.visits', 3)
                ->where('stats.uniques', 3)
                ->where('stats.clicks', 3)
                ->has('visitsByBucket', 1)
                ->has('provenance')
                ->has('topReferers')
                ->has('topClicks', 2)
                ->has('clicksByPage', 2)
            );
    }

    public function test_period_defaults_to_day_when_invalid(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get('/admin/stats?period=bogus')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('period', 'day'));
    }

    public function test_period_month_and_year_are_accepted(): void
    {
        $admin = $this->admin();

        foreach (['month', 'year'] as $period) {
            $this->actingAs($admin)
                ->withSession(['2fa_verified' => true])
                ->get("/admin/stats?period=$period")
                ->assertOk()
                ->assertInertia(fn ($page) => $page->where('period', $period));
        }
    }
}
