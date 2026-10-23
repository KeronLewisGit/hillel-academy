<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe: adds missing email templates, the published fee table and the
     * school's 2026–27 calendar (each only into an empty table) and the first administrator when ADMIN_EMAIL and
     * ADMIN_PASSWORD are set in .env.
     */
    public function run(): void
    {
        $this->call([TemplateSeeder::class, FeeSeeder::class, CalendarSeeder::class]);

        $email = config('hillel.admin.email');
        $password = config('hillel.admin.password');

        if ($email && $password) {
            User::firstOrCreate(['email' => strtolower($email)], [
                'name' => config('hillel.admin.name'),
                'password' => Hash::make($password),
                'role' => 'admin',
                'active' => true,
            ]);
        }
    }
}
