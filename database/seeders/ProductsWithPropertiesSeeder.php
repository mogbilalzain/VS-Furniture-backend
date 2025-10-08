<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\PropertyValue;
use App\Models\ProductPropertyValue;

class ProductsWithPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing product data
        ProductPropertyValue::truncate();
        Product::truncate();

        $productsData = [
            'Office Desks' => [
                [
                    'name' => 'Executive Oak Desk',
                    'description' => 'Premium executive desk made from solid oak wood',
                    'short_description' => 'Premium oak executive desk',
                    'model' => 'EOD-2024',
                    'sku' => 'DESK-EOD-001',
                    'specifications' => ['dimensions' => '180x90x75 cm', 'weight' => '45 kg'],
                    'image' => '/images/products/executive-oak-desk.jpg',
                    'is_featured' => true,
                    'sort_order' => 1,
                    'properties' => [
                        'Type' => ['executive_desks'],
                        'Height Adjustment' => ['fixed_height'],
                        'Material' => ['wood'],
                        'Mobility' => ['fixed'],
                    ]
                ],
                [
                    'name' => 'Student Adjustable Desk',
                    'description' => 'Height-adjustable desk perfect for students',
                    'short_description' => 'Adjustable student desk',
                    'model' => 'SAD-2024',
                    'sku' => 'DESK-SAD-002',
                    'specifications' => ['dimensions' => '120x60x65-85 cm', 'weight' => '25 kg'],
                    'image' => '/images/products/student-adjustable-desk.jpg',
                    'is_featured' => false,
                    'sort_order' => 2,
                    'properties' => [
                        'Type' => ['student_desks'],
                        'Height Adjustment' => ['height_adjustable'],
                        'Material' => ['metal', 'composite'],
                        'Mobility' => ['mobile'],
                    ]
                ],
            ],
            'Meeting Tables' => [
                [
                    'name' => 'Round Conference Table',
                    'description' => 'Large round conference table for 8 people',
                    'short_description' => 'Round conference table for 8',
                    'model' => 'RCT-2024',
                    'sku' => 'TABLE-RCT-001',
                    'specifications' => ['dimensions' => '200x200x75 cm', 'capacity' => '8 people'],
                    'image' => '/images/products/round-conference-table.jpg',
                    'is_featured' => true,
                    'sort_order' => 1,
                    'properties' => [
                        'Shape' => ['round'],
                        'Capacity' => ['5_8_people'],
                        'Features' => ['basic'],
                    ]
                ],
            ],
            'Office Chairs' => [
                [
                    'name' => 'Executive Leather Chair',
                    'description' => 'Premium executive chair with genuine leather',
                    'short_description' => 'Premium executive leather chair',
                    'model' => 'ELC-2024',
                    'sku' => 'CHAIR-ELC-001',
                    'specifications' => ['dimensions' => '70x70x120 cm', 'weight' => '28 kg'],
                    'image' => '/images/products/executive-leather-chair.jpg',
                    'is_featured' => true,
                    'sort_order' => 1,
                    'properties' => [
                        'Type' => ['executive_chairs'],
                        'Back Support' => ['high_back'],
                        'Material' => ['leather'],
                        'Adjustability' => ['height_adjustable', 'lumbar_support'],
                    ]
                ],
            ],
        ];

        foreach ($productsData as $categoryName => $products) {
            $category = Category::where('name', $categoryName)->first();
            
            if (!$category) {
                echo "Category '{$categoryName}' not found. Skipping...\n";
                continue;
            }

            foreach ($products as $productData) {
                $product = Product::create([
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'short_description' => $productData['short_description'],
                    'specifications' => $productData['specifications'],
                    'model' => $productData['model'],
                    'sku' => $productData['sku'],
                    'category_id' => $category->id,
                    'image' => $productData['image'],
                    'status' => 'active',
                    'is_featured' => $productData['is_featured'],
                    'sort_order' => $productData['sort_order'],
                    'views_count' => rand(10, 500),
                ]);

                $propertyValueIds = [];
                foreach ($productData['properties'] as $propertyName => $values) {
                    foreach ($values as $value) {
                        $propertyValue = PropertyValue::whereHas('categoryProperty', function($q) use ($propertyName, $category) {
                            $q->where('name', $propertyName)->where('category_id', $category->id);
                        })->where('value', $value)->first();

                        if ($propertyValue) {
                            $propertyValueIds[] = $propertyValue->id;
                        }
                    }
                }

                if (!empty($propertyValueIds)) {
                    $product->attachProperties($propertyValueIds);
                }

                echo "Created product '{$productData['name']}'.\n";
            }
        }

        PropertyValue::all()->each(function($value) {
            $value->updateProductCount();
        });

        echo "✅ Products with properties seeded successfully!\n";
    }
}