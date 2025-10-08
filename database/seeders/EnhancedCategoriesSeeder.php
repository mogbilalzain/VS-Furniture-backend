<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class EnhancedCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Office Desks',
                'slug' => 'office-desks',
                'description' => 'Professional office desks for modern workspaces. From executive desks to standing desks, find the perfect workspace solution.',
                'image' => '/images/categories/office-desks.jpg',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'meta_title' => 'Office Desks - Professional Workspace Furniture',
                'meta_description' => 'Discover our collection of office desks including executive, standing, and computer desks for modern workspaces.',
                'meta_keywords' => 'office desk, executive desk, standing desk, computer desk, workspace furniture'
            ],
            [
                'name' => 'Meeting Tables',
                'slug' => 'meeting-tables',
                'description' => 'Conference and meeting tables for collaborative workspaces. Available in various sizes and styles to suit your meeting room needs.',
                'image' => '/images/categories/meeting-tables.jpg',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'meta_title' => 'Meeting Tables - Conference Room Furniture',
                'meta_description' => 'Professional meeting tables and conference tables for modern office spaces and collaborative environments.',
                'meta_keywords' => 'meeting table, conference table, boardroom table, office furniture'
            ],
            [
                'name' => 'Office Chairs',
                'slug' => 'office-chairs',
                'description' => 'Ergonomic office chairs designed for comfort and productivity. From executive chairs to task chairs, find your perfect seating solution.',
                'image' => '/images/categories/office-chairs.jpg',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'meta_title' => 'Office Chairs - Ergonomic Seating Solutions',
                'meta_description' => 'Comfortable and ergonomic office chairs including executive, task, and ergonomic chairs for workplace comfort.',
                'meta_keywords' => 'office chair, ergonomic chair, executive chair, task chair, desk chair'
            ],
            [
                'name' => 'Storage Solutions',
                'slug' => 'storage-solutions',
                'description' => 'Office storage furniture including filing cabinets, bookcases, and storage units to keep your workspace organized.',
                'image' => '/images/categories/storage-solutions.jpg',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'meta_title' => 'Office Storage Solutions - Filing Cabinets & More',
                'meta_description' => 'Organize your office with our storage solutions including filing cabinets, bookcases, and storage units.',
                'meta_keywords' => 'office storage, filing cabinet, bookcase, storage unit, office organization'
            ],
            [
                'name' => 'Reception Furniture',
                'slug' => 'reception-furniture',
                'description' => 'Professional reception furniture to create a welcoming first impression. Reception desks, seating, and accessories.',
                'image' => '/images/categories/reception-furniture.jpg',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
                'meta_title' => 'Reception Furniture - Professional Welcome Areas',
                'meta_description' => 'Create impressive reception areas with our professional reception desks, seating, and furniture.',
                'meta_keywords' => 'reception desk, reception furniture, lobby furniture, waiting area furniture'
            ],
            [
                'name' => 'Workstations',
                'slug' => 'workstations',
                'description' => 'Modular workstations and cubicles for efficient office layouts. Create productive work environments with our workstation systems.',
                'image' => '/images/categories/workstations.jpg',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
                'meta_title' => 'Office Workstations - Modular Workspace Solutions',
                'meta_description' => 'Efficient office workstations and cubicle systems for modern workplace productivity and collaboration.',
                'meta_keywords' => 'workstation, cubicle, modular office, workspace system, office partition'
            ],
            [
                'name' => 'Lounge Furniture',
                'slug' => 'lounge-furniture',
                'description' => 'Comfortable lounge furniture for break areas and informal meeting spaces. Sofas, coffee tables, and relaxation furniture.',
                'image' => '/images/categories/lounge-furniture.jpg',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 7,
                'meta_title' => 'Office Lounge Furniture - Break Area Solutions',
                'meta_description' => 'Create comfortable break areas with our lounge furniture including sofas, coffee tables, and relaxation seating.',
                'meta_keywords' => 'lounge furniture, office sofa, coffee table, break area furniture, relaxation furniture'
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Office accessories and ergonomic add-ons to enhance your workspace. Monitor arms, keyboard trays, and desk accessories.',
                'image' => '/images/categories/accessories.jpg',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 8,
                'meta_title' => 'Office Accessories - Workspace Enhancement',
                'meta_description' => 'Enhance your workspace with our office accessories including monitor arms, keyboard trays, and desk organizers.',
                'meta_keywords' => 'office accessories, monitor arm, keyboard tray, desk organizer, ergonomic accessories'
            ]
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        $this->command->info('Enhanced categories created successfully!');
    }
}