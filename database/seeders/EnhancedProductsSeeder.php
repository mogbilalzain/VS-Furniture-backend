<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\CategoryProperty;
use App\Models\PropertyValue;
use App\Models\ProductPropertyValue;

class EnhancedProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productsData = [
            'Office Desks' => [
                [
                    'name' => 'Executive Mahogany Desk',
                    'description' => 'Luxurious executive desk crafted from premium mahogany wood with leather inlay and brass hardware.',
                    'short_description' => 'Premium mahogany executive desk with leather inlay',
                    'model' => 'EMD-2024-LUX',
                    'specifications' => [
                        'dimensions' => '180cm x 90cm x 75cm',
                        'weight' => '85kg',
                        'finish' => 'High-gloss mahogany',
                        'hardware' => 'Brass handles and locks'
                    ],
                    'properties' => [
                        'Material' => ['Solid Wood'],
                        'Size' => ['Extra Large (180cm)'],
                        'Storage' => ['Multiple Drawers', 'File Cabinet'],
                        'Style' => ['Executive', 'Traditional'],
                        'Color' => ['Brown']
                    ]
                ],
                [
                    'name' => 'Modern Glass Standing Desk',
                    'description' => 'Contemporary standing desk with tempered glass top and electric height adjustment.',
                    'short_description' => 'Electric standing desk with glass top',
                    'model' => 'MGSD-2024-ELEC',
                    'specifications' => [
                        'dimensions' => '140cm x 70cm x 65-130cm',
                        'weight' => '45kg',
                        'adjustment_range' => '65cm to 130cm',
                        'motor' => 'Dual motor system'
                    ],
                    'properties' => [
                        'Material' => ['Glass Top', 'Metal Frame'],
                        'Size' => ['Medium (140cm)'],
                        'Height Adjustment' => ['Electric Adjustment', 'Standing Desk'],
                        'Storage' => ['No Storage'],
                        'Style' => ['Modern', 'Contemporary'],
                        'Color' => ['Black', 'Gray']
                    ]
                ],
                [
                    'name' => 'Compact Home Office Desk',
                    'description' => 'Space-saving desk perfect for home offices with built-in cable management.',
                    'short_description' => 'Compact desk with cable management',
                    'model' => 'CHOD-2024-COMP',
                    'specifications' => [
                        'dimensions' => '100cm x 60cm x 75cm',
                        'weight' => '25kg',
                        'cable_management' => 'Built-in grommets',
                        'assembly' => 'Easy assembly'
                    ],
                    'properties' => [
                        'Material' => ['Engineered Wood'],
                        'Size' => ['Compact (100cm)'],
                        'Height Adjustment' => ['Fixed Height'],
                        'Storage' => ['Single Drawer'],
                        'Style' => ['Modern', 'Minimalist'],
                        'Color' => ['White', 'Oak']
                    ]
                ]
            ],
            
            'Meeting Tables' => [
                [
                    'name' => 'Conference Room Oval Table',
                    'description' => 'Large oval conference table with integrated power and data connectivity.',
                    'short_description' => 'Oval conference table with power outlets',
                    'model' => 'CROT-2024-TECH',
                    'specifications' => [
                        'dimensions' => '300cm x 150cm x 75cm',
                        'weight' => '120kg',
                        'power_outlets' => '8 outlets',
                        'data_ports' => '4 HDMI, 8 USB'
                    ],
                    'properties' => [
                        'Shape' => ['Oval'],
                        'Seating Capacity' => ['12 People'],
                        'Technology Features' => ['Power Outlets', 'USB Ports', 'HDMI Connections', 'Cable Management'],
                        'Base Type' => ['Double Pedestal'],
                        'Finish' => ['Wood Veneer']
                    ]
                ],
                [
                    'name' => 'Modular Meeting System',
                    'description' => 'Flexible modular table system that can be configured for different meeting sizes.',
                    'short_description' => 'Configurable modular meeting table',
                    'model' => 'MMS-2024-FLEX',
                    'specifications' => [
                        'base_unit' => '120cm x 60cm x 75cm',
                        'weight_per_unit' => '35kg',
                        'configurations' => 'Multiple layouts possible',
                        'connectivity' => 'Magnetic connectors'
                    ],
                    'properties' => [
                        'Shape' => ['Modular', 'Rectangular'],
                        'Seating Capacity' => ['4 People', '6 People', '8 People'],
                        'Technology Features' => ['Power Outlets', 'Cable Management'],
                        'Base Type' => ['Four Legs'],
                        'Finish' => ['Laminate', 'High Gloss']
                    ]
                ]
            ],
            
            'Office Chairs' => [
                [
                    'name' => 'Ergonomic Mesh Executive Chair',
                    'description' => 'Premium ergonomic chair with breathable mesh back and advanced lumbar support.',
                    'short_description' => 'Ergonomic mesh chair with lumbar support',
                    'model' => 'EMEC-2024-PRO',
                    'specifications' => [
                        'dimensions' => '70cm x 70cm x 110-120cm',
                        'weight' => '28kg',
                        'weight_capacity' => '150kg',
                        'adjustments' => '12 adjustment points'
                    ],
                    'properties' => [
                        'Chair Type' => ['Executive Chair', 'Ergonomic Chair'],
                        'Back Support' => ['High Back', 'Adjustable Lumbar', 'Mesh Back'],
                        'Armrests' => ['4D Adjustable'],
                        'Mobility' => ['Swivel Base', 'Casters'],
                        'Upholstery' => ['Mesh', 'Fabric'],
                        'Weight Capacity' => ['Up to 150kg']
                    ]
                ],
                [
                    'name' => 'Leather Conference Chair',
                    'description' => 'Elegant leather conference chair with chrome base and medium back support.',
                    'short_description' => 'Leather conference chair with chrome base',
                    'model' => 'LCC-2024-CHROME',
                    'specifications' => [
                        'dimensions' => '65cm x 65cm x 95cm',
                        'weight' => '22kg',
                        'leather_type' => 'Top-grain leather',
                        'base_finish' => 'Polished chrome'
                    ],
                    'properties' => [
                        'Chair Type' => ['Conference Chair'],
                        'Back Support' => ['Mid Back'],
                        'Armrests' => ['Fixed Armrests', 'Leather Armrests'],
                        'Mobility' => ['Swivel Base'],
                        'Upholstery' => ['Leather'],
                        'Weight Capacity' => ['Up to 120kg']
                    ]
                ]
            ],
            
            'Storage Solutions' => [
                [
                    'name' => 'Secure Filing Cabinet',
                    'description' => 'Heavy-duty filing cabinet with advanced security features and fire resistance.',
                    'short_description' => 'Secure filing cabinet with fire resistance',
                    'model' => 'SFC-2024-SEC',
                    'specifications' => [
                        'dimensions' => '40cm x 60cm x 132cm',
                        'weight' => '95kg',
                        'fire_rating' => '1-hour fire resistance',
                        'security' => 'Digital lock with audit trail'
                    ],
                    'properties' => [
                        'Storage Type' => ['Filing Cabinet'],
                        'Lock Type' => ['Digital Lock'],
                        'Number of Compartments' => ['4 Compartments'],
                        'Door Type' => ['Hinged Doors']
                    ]
                ],
                [
                    'name' => 'Mobile Storage Pedestal',
                    'description' => 'Compact mobile storage unit that fits under most desks with soft-close drawers.',
                    'short_description' => 'Mobile pedestal with soft-close drawers',
                    'model' => 'MSP-2024-MOBILE',
                    'specifications' => [
                        'dimensions' => '40cm x 60cm x 65cm',
                        'weight' => '25kg',
                        'drawer_slides' => 'Soft-close mechanism',
                        'mobility' => 'Locking casters'
                    ],
                    'properties' => [
                        'Storage Type' => ['Mobile Pedestal'],
                        'Lock Type' => ['Key Lock'],
                        'Number of Compartments' => ['3 Compartments'],
                        'Door Type' => ['Hinged Doors']
                    ]
                ]
            ],
            
            'Reception Furniture' => [
                [
                    'name' => 'Modern Reception Desk',
                    'description' => 'Sleek reception desk with integrated LED lighting and visitor management system.',
                    'short_description' => 'Modern reception desk with LED lighting',
                    'model' => 'MRD-2024-LED',
                    'specifications' => [
                        'dimensions' => '200cm x 80cm x 110cm',
                        'weight' => '75kg',
                        'lighting' => 'RGB LED strips',
                        'features' => 'Visitor management integration'
                    ],
                    'properties' => [
                        'Furniture Type' => ['Reception Desk'],
                        'Design Style' => ['Modern', 'Contemporary'],
                        'Reception Features' => ['Built-in Lighting', 'Visitor Management', 'Cable Management']
                    ]
                ],
                [
                    'name' => 'Executive Waiting Lounge Set',
                    'description' => 'Luxurious 3-piece waiting area set with premium leather upholstery.',
                    'short_description' => 'Premium leather waiting lounge set',
                    'model' => 'EWLS-2024-LUX',
                    'specifications' => [
                        'set_includes' => '3-seater sofa, 2 armchairs, coffee table',
                        'upholstery' => 'Italian leather',
                        'frame' => 'Hardwood construction',
                        'cushioning' => 'High-density foam'
                    ],
                    'properties' => [
                        'Furniture Type' => ['Sofa Set', 'Coffee Table'],
                        'Design Style' => ['Luxury', 'Contemporary'],
                        'Seating Arrangement' => ['3-Seater Sofa', 'Individual Chairs']
                    ]
                ]
            ],
            
            'Lighting Solutions' => [
                [
                    'name' => 'Smart LED Desk Lamp',
                    'description' => 'Intelligent desk lamp with app control, wireless charging, and circadian rhythm support.',
                    'short_description' => 'Smart LED lamp with wireless charging',
                    'model' => 'SLDL-2024-SMART',
                    'specifications' => [
                        'power' => '15W LED',
                        'brightness' => '1000 lumens max',
                        'color_range' => '2700K-6500K',
                        'wireless_charging' => '10W Qi charging pad'
                    ],
                    'properties' => [
                        'Light Type' => ['Desk Lamp', 'Task Lighting'],
                        'Power Source' => ['Plug-in', 'Wireless Charging'],
                        'Light Control' => ['Smart Control', 'Touch Control', 'Dimmer Control'],
                        'Color Temperature' => ['Adjustable Temperature', 'Daylight Simulation'],
                        'Mounting Type' => ['Desktop']
                    ]
                ],
                [
                    'name' => 'Professional Track Lighting System',
                    'description' => 'Modular track lighting system for office spaces with adjustable spotlights.',
                    'short_description' => 'Modular track lighting with adjustable spots',
                    'model' => 'PTLS-2024-MOD',
                    'specifications' => [
                        'track_length' => '2 meters (expandable)',
                        'spots_included' => '6 adjustable spotlights',
                        'power_per_spot' => '12W LED',
                        'beam_angle' => '15°-60° adjustable'
                    ],
                    'properties' => [
                        'Light Type' => ['Track Lighting', 'Ambient Lighting'],
                        'Power Source' => ['Hardwired'],
                        'Light Control' => ['Dimmer Control'],
                        'Color Temperature' => ['Cool White (6000K)'],
                        'Mounting Type' => ['Ceiling Mounted']
                    ]
                ]
            ]
        ];

        $totalProductsCreated = 0;

        foreach ($productsData as $categoryName => $products) {
            $category = Category::where('name', $categoryName)->first();
            
            if (!$category) {
                echo "❌ Category '{$categoryName}' not found. Skipping...\n";
                continue;
            }

            echo "📂 Adding products to category: {$categoryName}\n";

            foreach ($products as $productData) {
                // Create product
                $product = Product::create([
                    'name' => $productData['name'],
                    'slug' => \Str::slug($productData['name']) . '-' . time() . rand(100, 999),
                    'description' => $productData['description'],
                    'short_description' => $productData['short_description'],
                    'specifications' => $productData['specifications'],
                    'model' => $productData['model'],
                    'sku' => $this->generateSKU($productData['model']),
                    'category_id' => $category->id,
                    'image' => $this->getProductImage($categoryName),
                    'status' => 'active',
                    'is_featured' => rand(1, 10) <= 3, // 30% chance of being featured
                    'sort_order' => $totalProductsCreated + 1,
                    'views_count' => rand(10, 500),
                    'created_at' => now()->subDays(rand(1, 60)),
                    'updated_at' => now()->subDays(rand(0, 10))
                ]);

                // Attach properties
                if (isset($productData['properties'])) {
                    foreach ($productData['properties'] as $propertyName => $values) {
                        $property = CategoryProperty::where('category_id', $category->id)
                            ->where('name', $propertyName)
                            ->first();

                        if ($property) {
                            foreach ($values as $valueName) {
                                $propertyValue = PropertyValue::where('category_property_id', $property->id)
                                    ->where('value', $valueName)
                                    ->first();

                                if ($propertyValue) {
                                    ProductPropertyValue::create([
                                        'product_id' => $product->id,
                                        'property_value_id' => $propertyValue->id,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]);

                                    // Update product count for this property value
                                    $propertyValue->increment('product_count');
                                }
                            }
                        }
                    }
                }

                $totalProductsCreated++;
                echo "  📦 {$productData['name']} (SKU: {$product->sku})\n";
            }
            
            echo "  ✅ Added " . count($products) . " products to {$categoryName}\n\n";
        }

        echo "🎉 Enhanced Products Seeder completed successfully!\n";
        echo "📊 Summary:\n";
        echo "   Total Products Created: {$totalProductsCreated}\n";
        echo "   Categories Used: " . count($productsData) . "\n";
        echo "   Total Products in DB: " . Product::count() . "\n";
        echo "   Featured Products: " . Product::where('is_featured', true)->count() . "\n";
        echo "   Product-Property Relationships: " . ProductPropertyValue::count() . "\n";
    }

    /**
     * Generate SKU from model number
     */
    private function generateSKU($model): string
    {
        return strtoupper($model) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get appropriate image path for category
     */
    private function getProductImage($categoryName): string
    {
        $imageMap = [
            'Office Desks' => '/images/products/desk-',
            'Meeting Tables' => '/images/products/table-',
            'Office Chairs' => '/images/products/chair-',
            'Storage Solutions' => '/images/products/storage-',
            'Reception Furniture' => '/images/products/reception-',
            'Lighting Solutions' => '/images/products/lighting-'
        ];

        $basePath = $imageMap[$categoryName] ?? '/images/products/product-';
        return $basePath . rand(1, 5) . '.jpg';
    }
}