<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kodem.fr'],
            [
                'name' => 'Administrateur Kodem',
                'password' => Hash::make('KodemAdmin!2026'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
