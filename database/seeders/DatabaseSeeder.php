<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            // New Properties System
            CategoryPropertiesSeeder::class,
            ProductsWithPropertiesSeeder::class,
            // Legacy Filter System (for backward compatibility)
            // FilterSeeder::class,
            // ProductSeeder::class,
        ]);
    }
}
