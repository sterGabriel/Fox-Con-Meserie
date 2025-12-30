<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Baseline data required for the panel to function.
        $this->call([
            EncodeProfileSeeder::class,
            VideoCategorySeeder::class,
        ]);

        // Optional default admin account for fresh installs.
        // To enable, set IPTV_ADMIN_NAME, IPTV_ADMIN_EMAIL and IPTV_ADMIN_PASSWORD in .env
        $name = (string) env('IPTV_ADMIN_NAME', '');
        $email = (string) env('IPTV_ADMIN_EMAIL', '');
        $password = (string) env('IPTV_ADMIN_PASSWORD', '');

        if ($name !== '' && $email !== '' && $password !== '') {
            User::updateOrCreate(
                ['name' => $name],
                ['email' => $email, 'password' => Hash::make($password)]
            );
        }
    }
}
