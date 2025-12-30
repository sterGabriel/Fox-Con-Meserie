<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdminUser extends Command
{
    protected $signature = 'user:ensure-admin
        {--name= : Admin username (maps to users.name)}
        {--email= : Admin email}
        {--password= : Admin password (or set IPTV_ADMIN_PASSWORD env)}
        {--force-reset : Reset password if user already exists}';

    protected $description = 'Ensures an admin user exists. Creates user if missing; optionally resets password.';

    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: env('IPTV_ADMIN_NAME', '')));
        $email = trim((string) ($this->option('email') ?: env('IPTV_ADMIN_EMAIL', '')));
        $password = (string) ($this->option('password') ?: env('IPTV_ADMIN_PASSWORD', ''));
        $forceReset = (bool) $this->option('force-reset');

        if ($name === '') {
            $this->error('Name cannot be empty.');
            return 1;
        }
        if ($email === '') {
            $this->error('Email cannot be empty.');
            return 1;
        }
        if ($password === '') {
            $this->error('Password cannot be empty. Pass --password or set IPTV_ADMIN_PASSWORD.');
            return 1;
        }

        $user = User::query()
            ->where('name', $name)
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->info("Created admin user: name={$user->name} email={$user->email}");
            return 0;
        }

        if ($forceReset) {
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ])->save();

            $this->info("Updated admin user: id={$user->id} name={$user->name} email={$user->email} (password reset)");
            return 0;
        }

        $this->warn("Admin user already exists: id={$user->id} name={$user->name} email={$user->email}");
        $this->line('Re-run with --force-reset to reset password.');
        return 0;
    }
}
