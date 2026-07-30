<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
        {--email= : Email de l\'administrateur}
        {--name= : Nom affiché}
        {--password= : Mot de passe (déconseillé en argument : préférez la saisie interactive)}';

    protected $description = 'Crée (ou promeut) un compte administrateur, avec saisie interactive du mot de passe';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email de l\'administrateur');

        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255'],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing && ! $this->confirmPromotion($existing)) {
            $this->warn('Opération annulée.');

            return self::FAILURE;
        }

        $name = $this->option('name')
            ?: ($existing->name ?? null)
            ?: $this->ask('Nom affiché', 'Administrateur Kodem');

        $password = $this->resolvePassword($existing !== null);
        if ($password === null) {
            return self::FAILURE;
        }

        $attributes = [
            'name' => $name,
            'is_admin' => true,
        ];
        if ($password !== '') {
            $attributes['password'] = $password; // haché via le cast 'hashed' du modèle
        }

        $user = User::updateOrCreate(['email' => $email], $attributes);

        // email_verified_at n'est pas dans $fillable : on le force hors mass-assignment.
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->newLine();
        $this->info(($existing ? 'Compte promu administrateur' : 'Administrateur créé').' : '.$user->email);
        $this->line('Prochaine étape : connexion sur /admin puis activation de la 2FA (redirection automatique vers /admin/2fa/setup).');

        return self::SUCCESS;
    }

    protected function confirmPromotion(User $user): bool
    {
        $this->warn("Un utilisateur existe déjà avec cet email (id {$user->id}, admin=".($user->is_admin ? 'oui' : 'non').').');

        return $this->confirm('Le promouvoir administrateur / mettre à jour son mot de passe ?', true);
    }

    /**
     * Retourne le mot de passe en clair, '' pour conserver l'existant lors d'une
     * promotion, ou null en cas d'échec de validation.
     */
    protected function resolvePassword(bool $isExisting): ?string
    {
        $password = $this->option('password');

        if ($password === null || $password === '') {
            if ($isExisting && ! $this->confirm('Définir un nouveau mot de passe ?', false)) {
                return ''; // conserve le mot de passe actuel
            }

            $password = $this->secret('Mot de passe');
            $confirmation = $this->secret('Confirmez le mot de passe');

            if ($password !== $confirmation) {
                $this->error('Les mots de passe ne correspondent pas.');

                return null;
            }
        }

        $validator = Validator::make(['password' => $password], [
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return null;
        }

        return $password;
    }
}
