<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaterialCategory;
use App\Models\MaterialGroup;
use App\Models\Material;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Material Categories
        $metalColors = MaterialCategory::create([
            'name' => 'Metal Colors',
            'slug' => 'metal-colors',
            'description' => 'Metal parts are mainly used for table frames, chair legs, and structural elements. They provide durability and modern aesthetic.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $veneers = MaterialCategory::create([
            'name' => 'Veneers',
            'slug' => 'veneers',
            'description' => 'Natural wood veneers offer authentic wood texture and warmth to furniture surfaces.',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $laminates = MaterialCategory::create([
            'name' => 'Laminates',
            'slug' => 'laminates',
            'description' => 'High-quality laminates provide durable, easy-to-clean surfaces with various colors and patterns.',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create Material Groups
        
        // Metal Colors Groups
        $metalGroup1 = MaterialGroup::create([
            'category_id' => $metalColors->id,
            'name' => 'Group M1 Metals',
            'description' => 'Basic metal finishes for standard furniture pieces',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $metalGroup2 = MaterialGroup::create([
            'category_id' => $metalColors->id,
            'name' => 'Group M2 Premium Metals',
            'description' => 'Premium metal finishes for high-end furniture',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Veneers Groups
        $veneerGroup1 = MaterialGroup::create([
            'category_id' => $veneers->id,
            'name' => 'Group F1 Veneer',
            'description' => 'Standard wood veneers',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $veneerGroup2 = MaterialGroup::create([
            'category_id' => $veneers->id,
            'name' => 'Group F2 Premium Veneer',
            'description' => 'Premium wood veneers with unique grain patterns',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Laminates Groups
        $laminateGroup1 = MaterialGroup::create([
            'category_id' => $laminates->id,
            'name' => 'Group L1 Laminate',
            'description' => 'Standard laminate finishes',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Create Materials

        // Metal Colors - Group M1
        $materials = [
            // Group M1 Metals
            [
                'group_id' => $metalGroup1->id,
                'code' => 'M030',
                'name' => 'terra grey',
                'description' => 'Sophisticated grey metal finish',
                'color_hex' => '#8B8680',
                'image_url' => null,
                'sort_order' => 1,
            ],
            [
                'group_id' => $metalGroup1->id,
                'code' => 'M040',
                'name' => 'anthracite',
                'description' => 'Deep charcoal metal finish',
                'color_hex' => '#2C2C2C',
                'image_url' => null,
                'sort_order' => 2,
            ],
            [
                'group_id' => $metalGroup1->id,
                'code' => 'M050',
                'name' => 'white aluminum',
                'description' => 'Clean white metal finish',
                'color_hex' => '#F8F8F8',
                'image_url' => null,
                'sort_order' => 3,
            ],
            [
                'group_id' => $metalGroup1->id,
                'code' => 'M060',
                'name' => 'black steel',
                'description' => 'Matte black metal finish',
                'color_hex' => '#1A1A1A',
                'image_url' => null,
                'sort_order' => 4,
            ],

            // Group M2 Premium Metals
            [
                'group_id' => $metalGroup2->id,
                'code' => 'M070',
                'name' => 'brushed steel',
                'description' => 'Brushed stainless steel finish',
                'color_hex' => '#C0C0C0',
                'image_url' => null,
                'sort_order' => 1,
            ],
            [
                'group_id' => $metalGroup2->id,
                'code' => 'M080',
                'name' => 'bronze',
                'description' => 'Warm bronze metal finish',
                'color_hex' => '#CD7F32',
                'image_url' => null,
                'sort_order' => 2,
            ],

            // Group F1 Veneer
            [
                'group_id' => $veneerGroup1->id,
                'code' => 'F010',
                'name' => 'natural beech',
                'description' => 'Light, natural beech wood veneer',
                'color_hex' => '#F5DEB3',
                'image_url' => null,
                'sort_order' => 1,
            ],
            [
                'group_id' => $veneerGroup1->id,
                'code' => 'F020',
                'name' => 'natural oak',
                'description' => 'Classic oak wood veneer',
                'color_hex' => '#DEB887',
                'image_url' => null,
                'sort_order' => 2,
            ],
            [
                'group_id' => $veneerGroup1->id,
                'code' => 'F030',
                'name' => 'walnut',
                'description' => 'Rich walnut wood veneer',
                'color_hex' => '#8B4513',
                'image_url' => null,
                'sort_order' => 3,
            ],

            // Group F2 Premium Veneer
            [
                'group_id' => $veneerGroup2->id,
                'code' => 'F040',
                'name' => 'ebony',
                'description' => 'Deep black ebony wood veneer',
                'color_hex' => '#2C1810',
                'image_url' => null,
                'sort_order' => 1,
            ],
            [
                'group_id' => $veneerGroup2->id,
                'code' => 'F050',
                'name' => 'cherry',
                'description' => 'Rich cherry wood veneer',
                'color_hex' => '#8B3A3A',
                'image_url' => null,
                'sort_order' => 2,
            ],

            // Group L1 Laminate
            [
                'group_id' => $laminateGroup1->id,
                'code' => 'L010',
                'name' => 'white matte',
                'description' => 'Clean white matte laminate',
                'color_hex' => '#FFFFFF',
                'image_url' => null,
                'sort_order' => 1,
            ],
            [
                'group_id' => $laminateGroup1->id,
                'code' => 'L020',
                'name' => 'light grey',
                'description' => 'Soft light grey laminate',
                'color_hex' => '#D3D3D3',
                'image_url' => null,
                'sort_order' => 2,
            ],
            [
                'group_id' => $laminateGroup1->id,
                'code' => 'L030',
                'name' => 'dark grey',
                'description' => 'Modern dark grey laminate',
                'color_hex' => '#696969',
                'image_url' => null,
                'sort_order' => 3,
            ],
            [
                'group_id' => $laminateGroup1->id,
                'code' => 'L040',
                'name' => 'black',
                'description' => 'Deep black laminate finish',
                'color_hex' => '#000000',
                'image_url' => null,
                'sort_order' => 4,
            ],
        ];

        foreach ($materials as $materialData) {
            Material::create($materialData);
        }

        $this->command->info('Materials seeded successfully!');
        $this->command->info('Created 3 categories, 5 groups, and ' . count($materials) . ' materials.');
    }
}