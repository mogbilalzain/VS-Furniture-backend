<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Office Chairs',
                'description' => 'Ergonomic office chairs for comfortable work environment',
                'slug' => 'office-chairs',
                'icon' => 'fas fa-chair',
                'color' => '#3b82f6',
                'status' => 'active',
                'revenue' => 15750.00,
                'orders_count' => 23,
            ],
            [
                'name' => 'Office Desks',
                'description' => 'Modern office desks for productivity',
                'slug' => 'office-desks',
                'icon' => 'fas fa-table',
                'color' => '#10b981',
                'status' => 'active',
                'revenue' => 28950.00,
                'orders_count' => 18,
            ],
            [
                'name' => 'Storage Solutions',
                'description' => 'Filing cabinets and storage units',
                'slug' => 'storage-solutions',
                'icon' => 'fas fa-archive',
                'color' => '#f59e0b',
                'status' => 'active',
                'revenue' => 8420.00,
                'orders_count' => 12,
            ],
            [
                'name' => 'Meeting Tables',
                'description' => 'Conference and meeting room tables',
                'slug' => 'meeting-tables',
                'icon' => 'fas fa-users',
                'color' => '#ef4444',
                'status' => 'active',
                'revenue' => 45200.00,
                'orders_count' => 7,
            ],
            [
                'name' => 'Reception Furniture',
                'description' => 'Reception area furniture and seating',
                'slug' => 'reception-furniture',
                'icon' => 'fas fa-couch',
                'color' => '#8b5cf6',
                'status' => 'active',
                'revenue' => 12300.00,
                'orders_count' => 9,
            ],
            [
                'name' => 'Lighting Solutions',
                'description' => 'Modern LED lighting for educational spaces',
                'slug' => 'lighting-solutions',
                'icon' => 'fas fa-lightbulb',
                'color' => '#f97316',
                'status' => 'inactive',
                'revenue' => 0.00,
                'orders_count' => 0,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
