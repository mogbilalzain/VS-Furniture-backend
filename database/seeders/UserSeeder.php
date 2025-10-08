<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'username' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@vsfurniture.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Create regular user
        User::create([
            'username' => 'user',
            'name' => 'Regular User',
            'email' => 'user@vsfurniture.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
        ]);
    }
}
