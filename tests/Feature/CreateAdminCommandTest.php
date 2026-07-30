<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin_with_interactive_password(): void
    {
        $this->artisan('admin:create', ['--email' => 'boss@kodem.fr', '--name' => 'Boss'])
            ->expectsQuestion('Mot de passe', 'S3cret!Passw0rd')
            ->expectsQuestion('Confirmez le mot de passe', 'S3cret!Passw0rd')
            ->assertExitCode(0);

        $user = User::where('email', 'boss@kodem.fr')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_admin);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('S3cret!Passw0rd', $user->password));
    }

    public function test_rejects_mismatched_password_confirmation(): void
    {
        $this->artisan('admin:create', ['--email' => 'boss@kodem.fr'])
            ->expectsQuestion('Nom affiché', 'Boss')
            ->expectsQuestion('Mot de passe', 'S3cret!Passw0rd')
            ->expectsQuestion('Confirmez le mot de passe', 'different')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'boss@kodem.fr']);
    }

    public function test_rejects_weak_password(): void
    {
        $this->artisan('admin:create', ['--email' => 'boss@kodem.fr', '--name' => 'Boss', '--password' => 'weak'])
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'boss@kodem.fr']);
    }

    public function test_rejects_invalid_email(): void
    {
        $this->artisan('admin:create', ['--email' => 'not-an-email', '--password' => 'S3cret!Passw0rd'])
            ->assertExitCode(1);
    }

    public function test_promotes_existing_user_without_changing_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@kodem.fr',
            'password' => Hash::make('OldPassw0rd!'),
            'is_admin' => false,
        ]);

        $this->artisan('admin:create', ['--email' => 'user@kodem.fr'])
            ->expectsConfirmation('Le promouvoir administrateur / mettre à jour son mot de passe ?', 'yes')
            ->expectsConfirmation('Définir un nouveau mot de passe ?', 'no')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('OldPassw0rd!', $user->password));
    }
}
