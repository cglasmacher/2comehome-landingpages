<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email} {--name=Administrator}';

    protected $description = 'Create or update an administrator account for the valuation dashboard';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name')) ?: 'Administrator';
        $password = (string) $this->secret('Passwort');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Bitte eine gültige E-Mail-Adresse angeben.');

            return self::FAILURE;
        }

        if (mb_strlen($password) < 12) {
            $this->error('Das Passwort muss mindestens 12 Zeichen lang sein.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'is_admin' => true],
        );

        $this->info('Admin-Benutzer '.$user->email.' wurde gespeichert.');

        return self::SUCCESS;
    }
}
