<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Solution;
use App\Models\SolutionImage;
use App\Models\Product;

class SolutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الحلول
        $solutions = [
            [
                'title' => 'Modern Office Workspace',
                'description' => 'Create a contemporary office environment that promotes productivity and collaboration. Our modern office workspace solutions combine ergonomic furniture with innovative design to enhance employee well-being and efficiency. Features include height-adjustable desks, ergonomic seating, collaborative meeting spaces, and integrated technology solutions.',
                'cover_image' => '/images/solutions/covers/modern-office.jpg',
                'is_active' => true,
                'images' => [
                    ['image_path' => '/images/solutions/gallery/office-1.jpg', 'alt_text' => 'Modern office desk setup', 'sort_order' => 1],
                    ['image_path' => '/images/solutions/gallery/office-2.jpg', 'alt_text' => 'Collaborative workspace', 'sort_order' => 2],
                    ['image_path' => '/images/solutions/gallery/office-3.jpg', 'alt_text' => 'Meeting room setup', 'sort_order' => 3],
                ]
            ],
            [
                'title' => 'Interactive Learning Environment',
                'description' => 'Transform traditional classrooms into dynamic learning spaces that engage students and support modern teaching methods. Our interactive learning solutions include flexible seating arrangements, collaborative workstations, interactive whiteboards, and storage solutions that adapt to different learning activities.',
                'cover_image' => '/images/solutions/covers/classroom.jpg',
                'is_active' => true,
                'images' => [
                    ['image_path' => '/images/solutions/gallery/classroom-1.jpg', 'alt_text' => 'Interactive classroom setup', 'sort_order' => 1],
                    ['image_path' => '/images/solutions/gallery/classroom-2.jpg', 'alt_text' => 'Student collaboration area', 'sort_order' => 2],
                    ['image_path' => '/images/solutions/gallery/classroom-3.jpg', 'alt_text' => 'Teacher workstation', 'sort_order' => 3],
                ]
            ],
            [
                'title' => 'Executive Conference Room',
                'description' => 'Design impressive conference rooms that facilitate important business meetings and presentations. Our executive conference solutions feature premium materials, advanced AV integration, comfortable seating for extended meetings, and sophisticated lighting systems that create the perfect atmosphere for decision-making.',
                'cover_image' => '/images/solutions/covers/conference.jpg',
                'is_active' => true,
                'images' => [
                    ['image_path' => '/images/solutions/gallery/conference-1.jpg', 'alt_text' => 'Executive conference table', 'sort_order' => 1],
                    ['image_path' => '/images/solutions/gallery/conference-2.jpg', 'alt_text' => 'Premium seating arrangement', 'sort_order' => 2],
                    ['image_path' => '/images/solutions/gallery/conference-3.jpg', 'alt_text' => 'AV integration setup', 'sort_order' => 3],
                ]
            ],
            [
                'title' => 'Flexible Co-working Space',
                'description' => 'Create adaptable co-working environments that cater to diverse work styles and team sizes. Our flexible solutions include modular furniture systems, privacy screens, mobile storage units, and versatile seating options that can be easily reconfigured to meet changing needs throughout the day.',
                'cover_image' => '/images/solutions/covers/coworking.jpg',
                'is_active' => true,
                'images' => [
                    ['image_path' => '/images/solutions/gallery/coworking-1.jpg', 'alt_text' => 'Flexible workspace layout', 'sort_order' => 1],
                    ['image_path' => '/images/solutions/gallery/coworking-2.jpg', 'alt_text' => 'Modular furniture system', 'sort_order' => 2],
                    ['image_path' => '/images/solutions/gallery/coworking-3.jpg', 'alt_text' => 'Privacy and collaboration zones', 'sort_order' => 3],
                ]
            ],
            [
                'title' => 'Healthcare Facility Setup',
                'description' => 'Design healthcare environments that prioritize patient comfort, staff efficiency, and infection control. Our healthcare solutions include antimicrobial surfaces, easy-to-clean materials, ergonomic workstations for medical staff, comfortable patient seating, and specialized storage for medical equipment.',
                'cover_image' => '/images/solutions/covers/healthcare.jpg',
                'is_active' => true,
                'images' => [
                    ['image_path' => '/images/solutions/gallery/healthcare-1.jpg', 'alt_text' => 'Patient waiting area', 'sort_order' => 1],
                    ['image_path' => '/images/solutions/gallery/healthcare-2.jpg', 'alt_text' => 'Medical workstation', 'sort_order' => 2],
                    ['image_path' => '/images/solutions/gallery/healthcare-3.jpg', 'alt_text' => 'Treatment room setup', 'sort_order' => 3],
                ]
            ]
        ];

        foreach ($solutions as $solutionData) {
            // إنشاء الحل
            $solution = Solution::create([
                'title' => $solutionData['title'],
                'description' => $solutionData['description'],
                'cover_image' => $solutionData['cover_image'],
                'is_active' => $solutionData['is_active']
            ]);

            // إضافة الصور
            foreach ($solutionData['images'] as $imageData) {
                SolutionImage::create([
                    'solution_id' => $solution->id,
                    'image_path' => $imageData['image_path'],
                    'alt_text' => $imageData['alt_text'],
                    'sort_order' => $imageData['sort_order']
                ]);
            }

            // ربط المنتجات (عشوائي من المنتجات الموجودة)
            $availableProducts = Product::active()->limit(10)->get();
            if ($availableProducts->count() > 0) {
                // ربط 3-6 منتجات عشوائياً لكل حل
                $randomProducts = $availableProducts->random(min(rand(3, 6), $availableProducts->count()));
                $solution->products()->attach($randomProducts->pluck('id')->toArray());
            }
        }

        $this->command->info('✅ Solutions seeded successfully with ' . count($solutions) . ' solutions');
    }
}