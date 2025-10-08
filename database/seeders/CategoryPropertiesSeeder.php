<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\CategoryProperty;
use App\Models\PropertyValue;

class CategoryPropertiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        PropertyValue::truncate();
        CategoryProperty::truncate();

        // Define properties for each category
        $categoryProperties = [
            'Office Desks' => [
                'Type' => [
                    'display_name' => 'النوع',
                    'input_type' => 'checkbox',
                    'is_required' => true,
                    'values' => [
                        ['value' => 'student_desks', 'display_name' => 'Student tables/desks', 'display_name_ar' => 'طاولات الطلاب'],
                        ['value' => 'teacher_desks', 'display_name' => 'Teacher tables/desks', 'display_name_ar' => 'طاولات المعلمين'],
                        ['value' => 'executive_desks', 'display_name' => 'Executive desks', 'display_name_ar' => 'مكاتب تنفيذية'],
                        ['value' => 'computer_desks', 'display_name' => 'Computer desks', 'display_name_ar' => 'مكاتب الكمبيوتر'],
                    ]
                ],
                'Height Adjustment' => [
                    'display_name' => 'تعديل الارتفاع',
                    'input_type' => 'radio',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'fixed_height', 'display_name' => 'Fixed height', 'display_name_ar' => 'ارتفاع ثابت'],
                        ['value' => 'height_adjustable', 'display_name' => 'Height adjustable', 'display_name_ar' => 'قابل لتعديل الارتفاع'],
                    ]
                ],
                'Material' => [
                    'display_name' => 'المادة',
                    'input_type' => 'checkbox',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'wood', 'display_name' => 'Wood', 'display_name_ar' => 'خشب'],
                        ['value' => 'metal', 'display_name' => 'Metal', 'display_name_ar' => 'معدن'],
                        ['value' => 'glass', 'display_name' => 'Glass', 'display_name_ar' => 'زجاج'],
                        ['value' => 'composite', 'display_name' => 'Composite', 'display_name_ar' => 'مركب'],
                    ]
                ],
                'Mobility' => [
                    'display_name' => 'الحركة',
                    'input_type' => 'radio',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'fixed', 'display_name' => 'Fixed', 'display_name_ar' => 'ثابت'],
                        ['value' => 'mobile', 'display_name' => 'Mobile (with wheels)', 'display_name_ar' => 'متحرك (بعجلات)'],
                    ]
                ],
            ],

            'Meeting Tables' => [
                'Shape' => [
                    'display_name' => 'الشكل',
                    'input_type' => 'radio',
                    'is_required' => true,
                    'values' => [
                        ['value' => 'rectangular', 'display_name' => 'Rectangular', 'display_name_ar' => 'مستطيل'],
                        ['value' => 'round', 'display_name' => 'Round', 'display_name_ar' => 'دائري'],
                        ['value' => 'oval', 'display_name' => 'Oval', 'display_name_ar' => 'بيضاوي'],
                        ['value' => 'modular', 'display_name' => 'Modular', 'display_name_ar' => 'معياري'],
                    ]
                ],
                'Capacity' => [
                    'display_name' => 'السعة',
                    'input_type' => 'radio',
                    'is_required' => true,
                    'values' => [
                        ['value' => '2_4_people', 'display_name' => '2-4 people', 'display_name_ar' => '2-4 أشخاص'],
                        ['value' => '5_8_people', 'display_name' => '5-8 people', 'display_name_ar' => '5-8 أشخاص'],
                        ['value' => '9_12_people', 'display_name' => '9-12 people', 'display_name_ar' => '9-12 شخص'],
                        ['value' => '12_plus_people', 'display_name' => '12+ people', 'display_name_ar' => '12+ شخص'],
                    ]
                ],
                'Features' => [
                    'display_name' => 'المميزات',
                    'input_type' => 'checkbox',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'basic', 'display_name' => 'Basic table', 'display_name_ar' => 'طاولة أساسية'],
                        ['value' => 'power_outlets', 'display_name' => 'With power outlets', 'display_name_ar' => 'مع مقابس كهربائية'],
                        ['value' => 'av_equipment', 'display_name' => 'With AV equipment', 'display_name_ar' => 'مع معدات سمعية بصرية'],
                        ['value' => 'wireless_charging', 'display_name' => 'Wireless charging', 'display_name_ar' => 'شحن لاسلكي'],
                    ]
                ],
            ],

            'Office Chairs' => [
                'Type' => [
                    'display_name' => 'النوع',
                    'input_type' => 'checkbox',
                    'is_required' => true,
                    'values' => [
                        ['value' => 'task_chairs', 'display_name' => 'Task chairs', 'display_name_ar' => 'كراسي العمل'],
                        ['value' => 'executive_chairs', 'display_name' => 'Executive chairs', 'display_name_ar' => 'كراسي تنفيذية'],
                        ['value' => 'conference_chairs', 'display_name' => 'Conference chairs', 'display_name_ar' => 'كراسي المؤتمرات'],
                        ['value' => 'visitor_chairs', 'display_name' => 'Visitor chairs', 'display_name_ar' => 'كراسي الزوار'],
                    ]
                ],
                'Back Support' => [
                    'display_name' => 'دعم الظهر',
                    'input_type' => 'radio',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'low_back', 'display_name' => 'Low back', 'display_name_ar' => 'ظهر منخفض'],
                        ['value' => 'mid_back', 'display_name' => 'Mid back', 'display_name_ar' => 'ظهر متوسط'],
                        ['value' => 'high_back', 'display_name' => 'High back', 'display_name_ar' => 'ظهر عالي'],
                        ['value' => 'ergonomic', 'display_name' => 'Ergonomic', 'display_name_ar' => 'مريح'],
                    ]
                ],
                'Material' => [
                    'display_name' => 'المادة',
                    'input_type' => 'checkbox',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'fabric', 'display_name' => 'Fabric', 'display_name_ar' => 'قماش'],
                        ['value' => 'leather', 'display_name' => 'Leather', 'display_name_ar' => 'جلد'],
                        ['value' => 'mesh', 'display_name' => 'Mesh', 'display_name_ar' => 'شبكي'],
                        ['value' => 'vinyl', 'display_name' => 'Vinyl', 'display_name_ar' => 'فينيل'],
                    ]
                ],
                'Adjustability' => [
                    'display_name' => 'القابلية للتعديل',
                    'input_type' => 'checkbox',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'height_adjustable', 'display_name' => 'Height adjustable', 'display_name_ar' => 'قابل لتعديل الارتفاع'],
                        ['value' => 'armrest_adjustable', 'display_name' => 'Armrest adjustable', 'display_name_ar' => 'مساند ذراع قابلة للتعديل'],
                        ['value' => 'lumbar_support', 'display_name' => 'Lumbar support', 'display_name_ar' => 'دعم أسفل الظهر'],
                        ['value' => 'tilt_mechanism', 'display_name' => 'Tilt mechanism', 'display_name_ar' => 'آلية الإمالة'],
                    ]
                ],
            ],

            'Storage Solutions' => [
                'Type' => [
                    'display_name' => 'النوع',
                    'input_type' => 'checkbox',
                    'is_required' => true,
                    'values' => [
                        ['value' => 'filing_cabinets', 'display_name' => 'Filing cabinets', 'display_name_ar' => 'خزائن الملفات'],
                        ['value' => 'bookcases', 'display_name' => 'Bookcases', 'display_name_ar' => 'أرفف الكتب'],
                        ['value' => 'lockers', 'display_name' => 'Lockers', 'display_name_ar' => 'خزائن شخصية'],
                        ['value' => 'storage_cabinets', 'display_name' => 'Storage cabinets', 'display_name_ar' => 'خزائن التخزين'],
                    ]
                ],
                'Size' => [
                    'display_name' => 'الحجم',
                    'input_type' => 'radio',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'small', 'display_name' => 'Small (under 1m)', 'display_name_ar' => 'صغير (أقل من متر)'],
                        ['value' => 'medium', 'display_name' => 'Medium (1-2m)', 'display_name_ar' => 'متوسط (1-2 متر)'],
                        ['value' => 'large', 'display_name' => 'Large (over 2m)', 'display_name_ar' => 'كبير (أكثر من 2 متر)'],
                    ]
                ],
                'Security' => [
                    'display_name' => 'الأمان',
                    'input_type' => 'checkbox',
                    'is_required' => false,
                    'values' => [
                        ['value' => 'key_lock', 'display_name' => 'Key lock', 'display_name_ar' => 'قفل بمفتاح'],
                        ['value' => 'combination_lock', 'display_name' => 'Combination lock', 'display_name_ar' => 'قفل رقمي'],
                        ['value' => 'digital_lock', 'display_name' => 'Digital lock', 'display_name_ar' => 'قفل رقمي'],
                        ['value' => 'no_lock', 'display_name' => 'No lock', 'display_name_ar' => 'بدون قفل'],
                    ]
                ],
            ],
        ];

        $sortOrder = 0;
        foreach ($categoryProperties as $categoryName => $properties) {
            $category = Category::where('name', $categoryName)->first();
            
            if (!$category) {
                echo "Category '{$categoryName}' not found. Skipping...\n";
                continue;
            }

            foreach ($properties as $propertyName => $propertyData) {
                $sortOrder++;
                
                // Check if property already exists for this category
                $existingProperty = CategoryProperty::where('category_id', $category->id)
                    ->where('name', $propertyName)
                    ->first();
                
                if ($existingProperty) {
                    $property = $existingProperty;
                    echo "Property '{$propertyName}' already exists for category '{$categoryName}'. Skipping...\n";
                    continue;
                }

                $property = CategoryProperty::create([
                    'category_id' => $category->id,
                    'name' => $propertyName,
                    'display_name' => $propertyData['display_name'],
                    'display_name_ar' => $propertyData['display_name'],
                    'description' => "خاصية {$propertyData['display_name']} للفئة {$category->name}",
                    'input_type' => $propertyData['input_type'],
                    'is_required' => $propertyData['is_required'],
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ]);

                $valueSortOrder = 0;
                foreach ($propertyData['values'] as $valueData) {
                    $valueSortOrder++;
                    
                    PropertyValue::create([
                        'category_property_id' => $property->id,
                        'value' => $valueData['value'],
                        'display_name' => $valueData['display_name'],
                        'display_name_ar' => $valueData['display_name_ar'],
                        'sort_order' => $valueSortOrder,
                        'product_count' => 0, // Will be updated when products are seeded
                        'is_active' => true,
                    ]);
                }

                echo "Created property '{$propertyName}' for category '{$categoryName}' with " . count($propertyData['values']) . " values.\n";
            }
        }

        echo "✅ Properties and values seeded successfully!\n";
    }
}