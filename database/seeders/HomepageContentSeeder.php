<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HomepageContent;

class HomepageContentSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Real Spaces section content
        $realSpacesContent = [
            [
                'section' => 'real_spaces',
                'type' => 'video',
                'title' => 'Modern Learning Spaces',
                'description' => 'Innovative furniture for educational environments',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'sort_order' => 1,
                'is_active' => true,
                'metadata' => [
                    'duration' => '3:45',
                    'views' => '1.2M'
                ]
            ],
            [
                'section' => 'real_spaces',
                'type' => 'video',
                'title' => 'Flexible Classroom Solutions',
                'description' => 'Adaptable furniture for dynamic learning',
                'video_url' => 'https://www.youtube.com/watch?v=9bZkp7q19f0',
                'sort_order' => 2,
                'is_active' => true,
                'metadata' => [
                    'duration' => '2:30',
                    'views' => '850K'
                ]
            ],
            [
                'section' => 'real_spaces',
                'type' => 'video',
                'title' => 'Ergonomic Designs',
                'description' => 'Comfortable and healthy learning environments',
                'video_url' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                'sort_order' => 3,
                'is_active' => true,
                'metadata' => [
                    'duration' => '4:15',
                    'views' => '650K'
                ]
            ],
            [
                'section' => 'real_spaces',
                'type' => 'video',
                'title' => 'Sustainable Furniture',
                'description' => 'Eco-friendly solutions for schools',
                'video_url' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk',
                'sort_order' => 4,
                'is_active' => true,
                'metadata' => [
                    'duration' => '5:20',
                    'views' => '920K'
                ]
            ],
        ];

        foreach ($realSpacesContent as $content) {
            HomepageContent::create($content);
        }

        // Hero section content
        $heroContent = [
            [
                'section' => 'hero',
                'type' => 'text',
                'title' => 'Main Hero Title',
                'description' => 'Transform learning environments with innovative furniture solutions',
                'sort_order' => 1,
                'is_active' => true,
                'metadata' => [
                    'subtitle' => 'Innovative Educational Furniture',
                    'button_text' => 'Explore Solutions'
                ]
            ]
        ];

        foreach ($heroContent as $content) {
            HomepageContent::create($content);
        }

        // What We Do section content
        $whatWeDoContent = [
            [
                'section' => 'what_we_do',
                'type' => 'text',
                'title' => 'What We Do Section',
                'description' => 'We create innovative furniture solutions for educational environments',
                'sort_order' => 1,
                'is_active' => true,
                'metadata' => [
                    'cards' => [
                        [
                            'title' => 'Design Solutions',
                            'description' => 'Custom furniture design for educational spaces',
                            'image' => '/FlipTable_global-hero_3_2.webp',
                            'link' => '/solutions'
                        ],
                        [
                            'title' => 'Our Products',
                            'description' => 'Discover our range of educational furniture',
                            'image' => '/SPACE_global-hero_3_2.webp',
                            'link' => '/products'
                        ],
                        [
                            'title' => 'Get In Touch',
                            'description' => 'Contact us for custom solutions',
                            'image' => '/VSIMC_Stakki_global_collection-hero_3x2.webp',
                            'link' => '/contact'
                        ]
                    ]
                ]
            ]
        ];

        foreach ($whatWeDoContent as $content) {
            HomepageContent::create($content);
        }
    }
}
