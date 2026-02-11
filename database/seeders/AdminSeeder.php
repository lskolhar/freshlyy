<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Prevent duplicate admin creation
        if (!User::where('email', 'admin@freshlyy.com')->exists()) {

            User::create([
                'name' => 'Freshlyy Admin',
                'email' => 'admin@freshlyy.com',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
            ]);
        }
    }
}
