<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing categories to avoid duplicates
        Category::truncate();
        
        $categories = [
            [
                'name' => 'Collections',
                'description' => 'Three people collaborating at adjustable desks',
                'slug' => 'collections',
                'image' => '/products/8ffd4442eac7540d9e40306b504c8d664bc16fc6.jpg',
                'alt_text' => 'Three people collaborating at adjustable desks',

            ],
            [
                'name' => 'Seating',
                'description' => 'Illustration of yellow chairs with abstract figures',
                'slug' => 'seating',
                'image' => '/products/6dc110aef589e2ee723f2e76aacff47388bcf2b1.jpg',
                'alt_text' => 'Illustration of yellow chairs with abstract figures',


            ],
            [
                'name' => 'Soft Seating',
                'description' => 'Green and gray modular sofa',
                'slug' => 'soft-seating',
                'image' => '/products/fffc899afa42622e7f314fd62c1e4b78582f94b4.jpg',
                'alt_text' => 'Green and gray modular sofa',


            ],
            [
                'name' => 'Tables & Desks',
                'description' => 'Illustration of a desk with adjustable features and abstract figures',
                'slug' => 'tables-desks',
                'image' => '/products/30d20705cf9ed2b83a5045e2dbe72ef9e7ee550f.jpg',
                'alt_text' => 'Illustration of a desk with adjustable features and abstract figures',


            ],
            [
                'name' => 'Storage',
                'description' => 'White storage unit with green shelves on wheels',
                'slug' => 'storage',
                'image' => '/products/1964dd6348ef29eee0892a717a6a22d6200252f3.jpg',
                'alt_text' => 'White storage unit with green shelves on wheels',


            ],
            [
                'name' => 'Floor-Level Learning',
                'description' => 'Hanging pod-like chair with yellow cushion',
                'slug' => 'floor-level-learning',
                'image' => '/products/bd38f61b2f37eeab76c3d79c6430f51f2f8e6fc5.jpg',
                'alt_text' => 'Hanging pod-like chair with yellow cushion',


            ],
            [
                'name' => 'Room Dividers',
                'description' => 'Two people using a mobile room divider whiteboard',
                'slug' => 'room-dividers',
                'image' => '/products/55edf64cb17a7db8b928bfd9265562c54f68e195.jpg',
                'alt_text' => 'Two people using a mobile room divider whiteboard',


            ],
            [
                'name' => 'Display Boards & Wall Panels',
                'description' => 'Green whiteboard on a stand',
                'slug' => 'display-boards-wall-panels',
                'image' => '/products/3e0e5c762c9b57d6e4741f8f036691d769fc5dce.jpg',
                'alt_text' => 'Green whiteboard on a stand',


            ],
            [
                'name' => 'QuickShip Program',
                'description' => 'Bright green chair or stool',
                'slug' => 'quickship-program',
                'image' => '/products/0765d0045f50b677905621765471213bae908e09.jpg',
                'alt_text' => 'Bright green chair or stool',


            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Product categories seeded successfully!');
    }
}