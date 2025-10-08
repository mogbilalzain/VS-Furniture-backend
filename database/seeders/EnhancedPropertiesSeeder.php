<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\CategoryProperty;
use App\Models\PropertyValue;

class EnhancedPropertiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Enhanced properties for existing and new categories
        $categoriesData = [
            'Office Desks' => [
                'Material' => [
                    'Solid Wood', 'Engineered Wood', 'Metal Frame', 'Glass Top', 
                    'Laminated Wood', 'Bamboo', 'Reclaimed Wood', 'Steel'
                ],
                'Size' => [
                    'Small (120cm)', 'Medium (140cm)', 'Large (160cm)', 'Extra Large (180cm)',
                    'Compact (100cm)', 'Executive (200cm)'
                ],
                'Height Adjustment' => [
                    'Fixed Height', 'Manual Adjustment', 'Electric Adjustment', 
                    'Pneumatic Adjustment', 'Standing Desk'
                ],
                'Storage' => [
                    'No Storage', 'Single Drawer', 'Multiple Drawers', 'Side Cabinet',
                    'Built-in Shelves', 'File Cabinet', 'CPU Holder'
                ],
                'Style' => [
                    'Modern', 'Traditional', 'Industrial', 'Scandinavian', 
                    'Minimalist', 'Executive', 'Contemporary'
                ],
                'Color' => [
                    'White', 'Black', 'Brown', 'Oak', 'Walnut', 'Gray', 'Beige', 'Cherry'
                ]
            ],
            
            'Meeting Tables' => [
                'Shape' => [
                    'Rectangular', 'Oval', 'Round', 'Square', 'Boat Shape', 
                    'Racetrack', 'Modular', 'U-Shape'
                ],
                'Seating Capacity' => [
                    '4 People', '6 People', '8 People', '10 People', '12 People', 
                    '14 People', '16 People', '20+ People'
                ],
                'Technology Features' => [
                    'No Technology', 'Power Outlets', 'USB Ports', 'HDMI Connections',
                    'Wireless Charging', 'Cable Management', 'AV Integration', 'Smart Board Ready'
                ],
                'Base Type' => [
                    'Single Pedestal', 'Double Pedestal', 'Four Legs', 'Trestle Base',
                    'Metal Frame', 'Wooden Frame', 'Chrome Base'
                ],
                'Finish' => [
                    'High Gloss', 'Matte', 'Wood Veneer', 'Laminate', 
                    'Glass', 'Metal', 'Leather Inlay'
                ]
            ],
            
            'Office Chairs' => [
                'Chair Type' => [
                    'Executive Chair', 'Task Chair', 'Conference Chair', 'Ergonomic Chair',
                    'Gaming Chair', 'Visitor Chair', 'Drafting Chair', 'Mesh Chair'
                ],
                'Back Support' => [
                    'High Back', 'Mid Back', 'Low Back', 'Lumbar Support',
                    'Adjustable Lumbar', 'Memory Foam', 'Mesh Back'
                ],
                'Armrests' => [
                    'No Armrests', 'Fixed Armrests', 'Adjustable Height', '4D Adjustable',
                    'Padded Armrests', 'Leather Armrests', 'Flip-up Armrests'
                ],
                'Mobility' => [
                    'Fixed Base', 'Swivel Base', 'Casters', 'Glides',
                    'Locking Casters', 'Carpet Casters', 'Hard Floor Casters'
                ],
                'Upholstery' => [
                    'Fabric', 'Leather', 'Faux Leather', 'Mesh', 
                    'Vinyl', 'Microfiber', 'Breathable Fabric'
                ],
                'Weight Capacity' => [
                    'Up to 120kg', 'Up to 150kg', 'Up to 180kg', 'Up to 200kg',
                    'Heavy Duty 250kg+', 'Bariatric 300kg+'
                ]
            ],
            
            'Storage Solutions' => [
                'Storage Type' => [
                    'Filing Cabinet', 'Bookshelf', 'Storage Cabinet', 'Locker',
                    'Mobile Pedestal', 'Credenza', 'Wardrobe', 'Display Cabinet'
                ],
                'Lock Type' => [
                    'No Lock', 'Key Lock', 'Digital Lock', 'Combination Lock',
                    'RFID Lock', 'Biometric Lock', 'Central Locking'
                ],
                'Number of Compartments' => [
                    '1 Compartment', '2 Compartments', '3 Compartments', '4 Compartments',
                    '5+ Compartments', 'Adjustable Shelves', 'Mixed Compartments'
                ],
                'Door Type' => [
                    'Hinged Doors', 'Sliding Doors', 'Roll-up Doors', 'Glass Doors',
                    'Mesh Doors', 'Open Shelving', 'Tambour Doors'
                ]
            ],
            
            'Reception Furniture' => [
                'Furniture Type' => [
                    'Reception Desk', 'Waiting Chairs', 'Coffee Table', 'Magazine Rack',
                    'Reception Counter', 'Sofa Set', 'Side Table', 'Plant Stand'
                ],
                'Design Style' => [
                    'Modern', 'Classic', 'Contemporary', 'Luxury', 
                    'Minimalist', 'Traditional', 'Industrial'
                ],
                'Seating Arrangement' => [
                    'Single Seater', '2-Seater Sofa', '3-Seater Sofa', 'Modular Seating',
                    'Bench Seating', 'Individual Chairs', 'Corner Seating'
                ],
                'Reception Features' => [
                    'Built-in Lighting', 'Storage Drawers', 'Cable Management', 'Display Area',
                    'Branding Space', 'Visitor Management', 'Wheelchair Accessible'
                ]
            ],
            
            'Lighting Solutions' => [
                'Light Type' => [
                    'Desk Lamp', 'Floor Lamp', 'Ceiling Light', 'Pendant Light',
                    'Track Lighting', 'LED Panel', 'Task Lighting', 'Ambient Lighting'
                ],
                'Power Source' => [
                    'Plug-in', 'Battery Powered', 'USB Rechargeable', 'Solar Powered',
                    'Hardwired', 'Wireless Charging', 'Hybrid Power'
                ],
                'Light Control' => [
                    'On/Off Switch', 'Dimmer Control', 'Touch Control', 'Remote Control',
                    'Smart Control', 'Motion Sensor', 'Timer Function'
                ],
                'Color Temperature' => [
                    'Warm White (3000K)', 'Natural White (4000K)', 'Cool White (6000K)',
                    'Adjustable Temperature', 'RGB Color', 'Daylight Simulation'
                ],
                'Mounting Type' => [
                    'Desktop', 'Clamp-on', 'Floor Standing', 'Wall Mounted',
                    'Ceiling Mounted', 'Suspended', 'Magnetic Mount'
                ]
            ]
        ];

        foreach ($categoriesData as $categoryName => $properties) {
            // Find or create category
            $category = Category::where('name', $categoryName)->first();
            
            if (!$category) {
                $category = Category::create([
                    'name' => $categoryName,
                    'slug' => \Str::slug($categoryName),
                    'description' => "Professional {$categoryName} for modern offices",
                    'is_active' => true,
                    'sort_order' => Category::count() + 1
                ]);
                echo "✅ Created category: {$categoryName}\n";
            }

            foreach ($properties as $propertyName => $values) {
                // Create or update property
                $property = CategoryProperty::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $propertyName
                    ],
                    [
                        'description' => "Select {$propertyName} options",
                        'display_name' => $propertyName,
                        'input_type' => in_array($propertyName, ['Size', 'Seating Capacity', 'Weight Capacity']) ? 'select' : 'checkbox',
                        'is_required' => in_array($propertyName, ['Material', 'Size', 'Chair Type', 'Storage Type', 'Light Type']),
                        'is_active' => true,
                        'is_filterable' => true,
                        'sort_order' => 1
                    ]
                );

                // Add property values
                foreach ($values as $index => $value) {
                    PropertyValue::updateOrCreate(
                        [
                            'category_property_id' => $property->id,
                            'value' => $value
                        ],
                        [
                            'display_name' => $value,
                            'is_active' => true,
                            'sort_order' => $index + 1,
                            'product_count' => 0
                        ]
                    );
                }

                echo "  ⚙️ Added property: {$propertyName} with " . count($values) . " values\n";
            }
        }

        echo "\n🎉 Enhanced Properties Seeder completed successfully!\n";
        echo "📊 Summary:\n";
        echo "   Categories: " . Category::count() . "\n";
        echo "   Properties: " . CategoryProperty::count() . "\n";
        echo "   Property Values: " . PropertyValue::count() . "\n";
    }
}