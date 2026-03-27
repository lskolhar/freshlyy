<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('ADMIN_PASSWORD');

        if (blank($adminPassword)) {
            return;
        }

        // Prevent duplicate admin creation
        if (! User::where('email', 'admin@freshlyy.com')->exists()) {

            User::create([
                'name' => 'Freshlyy Admin',
                'email' => 'admin@freshlyy.com',
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
            ]);
        }
    }
}
