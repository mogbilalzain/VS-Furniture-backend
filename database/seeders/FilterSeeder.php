<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FilterCategory;
use App\Models\FilterOption;

class FilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        FilterOption::truncate();
        FilterCategory::truncate();

        // Define filter categories and their options
        $filterData = [
            [
                'name' => 'type',
                'display_name' => 'Type',
                'display_name_ar' => null,
                'sort_order' => 1,
                'input_type' => 'checkbox',
                'options' => [
                    ['value' => 'student_tables', 'display_name' => 'Student tables/desks', 'display_name_ar' => null, 'product_count' => 251],
                    ['value' => 'multipurpose_tables', 'display_name' => 'Multipurpose tables', 'display_name_ar' => null, 'product_count' => 394],
                    ['value' => 'teacher_tables', 'display_name' => 'Teacher tables/desks', 'display_name_ar' => null, 'product_count' => 35],
                    ['value' => 'specialty_tables', 'display_name' => 'Specialty and computer tables', 'display_name_ar' => null, 'product_count' => 29],
                    ['value' => 'occasional_tables', 'display_name' => 'Occasional tables', 'display_name_ar' => null, 'product_count' => 19],
                    ['value' => 'office_systems', 'display_name' => 'Office table/desk systems', 'display_name_ar' => null, 'product_count' => 274],
                ]
            ],
            [
                'name' => 'collections',
                'display_name' => 'Collections',
                'display_name_ar' => null,
                'sort_order' => 2,
                'input_type' => 'checkbox',
                'options' => [
                    ['value' => 'shift_plus', 'display_name' => 'Shift+', 'display_name_ar' => null, 'product_count' => 11],
                    ['value' => 'tano', 'display_name' => 'Tano', 'display_name_ar' => null, 'product_count' => 2],
                    ['value' => 'jumper', 'display_name' => 'JUMPER', 'display_name_ar' => null, 'product_count' => 4],
                    ['value' => 'lite_table', 'display_name' => 'LiteTable', 'display_name_ar' => null, 'product_count' => 22],
                    ['value' => 'uno', 'display_name' => 'Uno', 'display_name_ar' => null, 'product_count' => 23],
                ]
            ],
            [
                'name' => 'mobility',
                'display_name' => 'Mobility',
                'display_name_ar' => null,
                'sort_order' => 3,
                'input_type' => 'radio',
                'options' => [
                    ['value' => 'mobile', 'display_name' => 'Mobile', 'display_name_ar' => null, 'product_count' => 365],
                ]
            ],
            [
                'name' => 'height_adjustment',
                'display_name' => 'Height adjustment',
                'display_name_ar' => null,
                'sort_order' => 4,
                'input_type' => 'radio',
                'options' => [
                    ['value' => 'fixed_height', 'display_name' => 'Fixed height', 'display_name_ar' => null, 'product_count' => 519],
                    ['value' => 'height_adjustable', 'display_name' => 'Height adjustable', 'display_name_ar' => null, 'product_count' => 207],
                ]
            ],
            [
                'name' => 'function',
                'display_name' => 'Function',
                'display_name_ar' => null,
                'sort_order' => 5,
                'input_type' => 'checkbox',
                'options' => [
                    ['value' => 'folding_tabletop', 'display_name' => 'Folding tabletop', 'display_name_ar' => null, 'product_count' => 49],
                    ['value' => 'stackable', 'display_name' => 'Stackable', 'display_name_ar' => null, 'product_count' => 34],
                    ['value' => 'tilting_tabletop', 'display_name' => 'Tilting tabletop', 'display_name_ar' => null, 'product_count' => 6],
                ]
            ],
            [
                'name' => 'under_table_attachments',
                'display_name' => 'Under-table attachments',
                'display_name_ar' => null,
                'sort_order' => 6,
                'input_type' => 'checkbox',
                'options' => [
                    ['value' => 'bookshelf', 'display_name' => 'Bookshelf', 'display_name_ar' => null, 'product_count' => 56],
                    ['value' => 'chair_suspension', 'display_name' => 'Chair suspension', 'display_name_ar' => null, 'product_count' => 47],
                    ['value' => 'plastic_box', 'display_name' => 'Plastic box', 'display_name_ar' => null, 'product_count' => 29],
                ]
            ],
            [
                'name' => 'table_width',
                'display_name' => 'Table width',
                'display_name_ar' => null,
                'sort_order' => 7,
                'input_type' => 'checkbox',
                'options' => [
                    ['value' => '15_23', 'display_name' => '15" - 23"', 'display_name_ar' => null, 'product_count' => 11],
                    ['value' => '24_35', 'display_name' => '24" - 35"', 'display_name_ar' => null, 'product_count' => 145],
                    ['value' => '36_47', 'display_name' => '36" - 47"', 'display_name_ar' => null, 'product_count' => 101],
                    ['value' => '48_59', 'display_name' => '48" - 59"', 'display_name_ar' => null, 'product_count' => 135],
                    ['value' => '60_71', 'display_name' => '60" - 71"', 'display_name_ar' => null, 'product_count' => 162],
                    ['value' => '72_83', 'display_name' => '72" - 83"', 'display_name_ar' => null, 'product_count' => 68],
                    ['value' => '84_95', 'display_name' => '84" - 95"', 'display_name_ar' => null, 'product_count' => 46],
                    ['value' => 'over_96', 'display_name' => '> 96"', 'display_name_ar' => null, 'product_count' => 2],
                ]
            ],
            [
                'name' => 'table_depth',
                'display_name' => 'Table depth',
                'display_name_ar' => null,
                'sort_order' => 8,
                'input_type' => 'checkbox',
                'options' => [
                    ['value' => '19_23', 'display_name' => '19" - 23"', 'display_name_ar' => null, 'product_count' => 99],
                    ['value' => '24_29', 'display_name' => '24" - 29"', 'display_name_ar' => null, 'product_count' => 171],
                    ['value' => '30_35', 'display_name' => '30" - 35"', 'display_name_ar' => null, 'product_count' => 259],
                    ['value' => '36_39', 'display_name' => '36" - 39"', 'display_name_ar' => null, 'product_count' => 64],
                    ['value' => '40_47', 'display_name' => '40" - 47"', 'display_name_ar' => null, 'product_count' => 16],
                    ['value' => 'over_48', 'display_name' => '> 48"', 'display_name_ar' => null, 'product_count' => 5],
                ]
            ],
            [
                'name' => 'tabletop_shape',
                'display_name' => 'Tabletop shape',
                'display_name_ar' => null,
                'sort_order' => 9,
                'input_type' => 'checkbox',
                'options' => [
                    ['value' => 'rectangle', 'display_name' => 'Rectangle', 'display_name_ar' => null, 'product_count' => 479],
                    ['value' => 'half_round', 'display_name' => 'Half-round / Round', 'display_name_ar' => null, 'product_count' => 67],
                    ['value' => 'triangle', 'display_name' => 'Triangle', 'display_name_ar' => null, 'product_count' => 9],
                    ['value' => 'other_shapes', 'display_name' => 'Other shapes', 'display_name_ar' => null, 'product_count' => 65],
                ]
            ],
            [
                'name' => 'frame',
                'display_name' => 'Frame',
                'display_name_ar' => null,
                'sort_order' => 10,
                'input_type' => 'checkbox',
                'options' => [
                    ['value' => 'column', 'display_name' => 'Column', 'display_name_ar' => null, 'product_count' => 76],
                    ['value' => 'legs', 'display_name' => 'Legs', 'display_name_ar' => null, 'product_count' => 398],
                    ['value' => 'skids_c_foot', 'display_name' => 'Skids and C-foot', 'display_name_ar' => null, 'product_count' => 48],
                    ['value' => 'skids_t_foot', 'display_name' => 'Skids and T-foot', 'display_name_ar' => null, 'product_count' => 101],
                ]
            ],
        ];

        // Create filter categories and options
        foreach ($filterData as $categoryData) {
            $options = $categoryData['options'];
            unset($categoryData['options']);

            $category = FilterCategory::create($categoryData);

            foreach ($options as $index => $optionData) {
                $optionData['filter_category_id'] = $category->id;
                $optionData['sort_order'] = $index + 1;
                FilterOption::create($optionData);
            }
        }
    }
}