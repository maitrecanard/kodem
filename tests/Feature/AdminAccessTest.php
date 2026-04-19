<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_non_admin_user_gets_403(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get('/admin')->assertStatus(403);
    }

    public function test_admin_without_2fa_is_forced_to_setup(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('admin.2fa.setup'));
    }

    public function test_admin_with_2fa_verified_reaches_dashboard(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $admin = User::factory()->create([
            'is_admin' => true,
            'google2fa_secret' => $secret,
            'google2fa_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get('/admin')
            ->assertOk();
    }

    public function test_2fa_challenge_accepts_valid_code_and_rejects_invalid(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $admin = User::factory()->create([
            'is_admin' => true,
            'google2fa_secret' => $secret,
            'google2fa_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/2fa/verify', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $validCode = $google2fa->getCurrentOtp($secret);
        $this->actingAs($admin)
            ->post('/admin/2fa/verify', ['code' => $validCode])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_2fa_setup_generates_secret_and_enable_verifies(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/2fa/setup')->assertOk();

        $admin->refresh();
        $this->assertNotEmpty($admin->google2fa_secret);

        $google2fa = new Google2FA;
        $validCode = $google2fa->getCurrentOtp($admin->google2fa_secret);

        $this->actingAs($admin)
            ->post('/admin/2fa/enable', ['code' => $validCode])
            ->assertRedirect(route('admin.dashboard'));

        $admin->refresh();
        $this->assertTrue((bool) $admin->google2fa_enabled);
    }
}
