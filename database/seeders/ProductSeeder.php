<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\FilterOption;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please run CategorySeeder first.');
            return;
        }

        $products = [
            // Student Tables/Desks
            [
                'name' => 'Ergonomic Student Desk Pro',
                'description' => 'Height-adjustable student desk with tilting top and book storage. Perfect for students of all ages.',
                'model' => 'ESD-PRO-001',
                'price' => 320.00,
                'stock_quantity' => 45,
                'category_name' => 'Office Desks',
                'image' => '/images/products/student-desk-pro.jpg',
                'status' => 'active',
                'filters' => ['student_tables', 'height_adjustable', 'tilting_tabletop', 'bookshelf', '36_47', '24_29', 'rectangle', 'legs']
            ],
            [
                'name' => 'Mobile Student Table',
                'description' => 'Lightweight mobile table with folding legs. Easy to reconfigure classroom layouts.',
                'model' => 'MST-001',
                'price' => 280.00,
                'stock_quantity' => 60,
                'category_name' => 'Office Desks',
                'image' => '/images/products/mobile-student-table.jpg',
                'status' => 'active',
                'filters' => ['student_tables', 'mobile', 'fixed_height', 'folding_tabletop', '48_59', '30_35', 'rectangle', 'legs']
            ],
            [
                'name' => 'Collaborative Learning Desk',
                'description' => 'Hexagonal desk perfect for group work and collaboration. Stackable design for easy storage.',
                'model' => 'CLD-HEX-001',
                'price' => 350.00,
                'stock_quantity' => 35,
                'category_name' => 'Office Desks',
                'image' => '/images/products/collaborative-desk.jpg',
                'status' => 'active',
                'filters' => ['student_tables', 'mobile', 'fixed_height', 'stackable', '48_59', '30_35', 'other_shapes', 'legs']
            ],

            // Multipurpose Tables
            [
                'name' => 'Conference Table Pro',
                'description' => 'Premium conference table for professional meetings. Features elegant design with built-in cable management and premium wood finish.',
                'model' => 'CT-PRO-001',
                'price' => 2500.00,
                'stock_quantity' => 15,
                'category_name' => 'Meeting Tables',
                'image' => '/images/products/conference-table-pro.jpg',
                'status' => 'active',
                'filters' => ['multipurpose_tables', 'fixed_height', '84_95', '36_39', 'rectangle', 'column']
            ],
            [
                'name' => 'Round Meeting Table',
                'description' => 'Modern round meeting table perfect for collaborative discussions. Accommodates up to 8 people comfortably.',
                'model' => 'RMT-008',
                'price' => 1800.00,
                'stock_quantity' => 12,
                'category_name' => 'Meeting Tables',
                'image' => '/images/products/round-meeting-table.jpg',
                'status' => 'active',
                'filters' => ['multipurpose_tables', 'mobile', 'fixed_height', '60_71', '60_71', 'half_round', 'column']
            ],
            [
                'name' => 'Shift+ Collaboration Table',
                'description' => 'Modern collaboration table from Shift+ collection with mobile design and folding capability.',
                'model' => 'SCT-001',
                'price' => 1200.00,
                'stock_quantity' => 20,
                'category_name' => 'Meeting Tables',
                'image' => '/images/products/shift-collaboration-table.jpg',
                'status' => 'active',
                'filters' => ['multipurpose_tables', 'shift_plus', 'mobile', 'fixed_height', 'folding_tabletop', '48_59', '30_35', 'rectangle', 'legs']
            ],
            [
                'name' => 'JUMPER Training Table',
                'description' => 'Versatile training table from JUMPER collection with stackable design and mobility features.',
                'model' => 'JTT-001',
                'price' => 850.00,
                'stock_quantity' => 30,
                'category_name' => 'Meeting Tables',
                'image' => '/images/products/jumper-training-table.jpg',
                'status' => 'active',
                'filters' => ['multipurpose_tables', 'jumper', 'mobile', 'fixed_height', 'stackable', '48_59', '24_29', 'rectangle', 'legs']
            ],

            // Teacher Tables/Desks
            [
                'name' => 'Teacher Station Pro',
                'description' => 'Complete teacher workstation with height adjustment, storage drawers, and presentation area.',
                'model' => 'TSP-001',
                'price' => 1500.00,
                'stock_quantity' => 15,
                'category_name' => 'Office Desks',
                'image' => '/images/products/teacher-station-pro.jpg',
                'status' => 'active',
                'filters' => ['teacher_tables', 'height_adjustable', '60_71', '30_35', 'rectangle', 'column']
            ],
            [
                'name' => 'Instructor Podium Desk',
                'description' => 'Standing height instructor desk with tilting top and book storage underneath.',
                'model' => 'IPD-001',
                'price' => 950.00,
                'stock_quantity' => 12,
                'category_name' => 'Office Desks',
                'image' => '/images/products/instructor-podium.jpg',
                'status' => 'active',
                'filters' => ['teacher_tables', 'fixed_height', 'tilting_tabletop', 'bookshelf', '36_47', '24_29', 'rectangle', 'column']
            ],

            // Office Systems
            [
                'name' => 'Uno Office System',
                'description' => 'Modular office system from Uno collection with customizable configurations.',
                'model' => 'UOS-001',
                'price' => 2200.00,
                'stock_quantity' => 18,
                'category_name' => 'Office Desks',
                'image' => '/images/products/uno-office-system.jpg',
                'status' => 'active',
                'filters' => ['office_systems', 'uno', 'height_adjustable', '72_83', '30_35', 'rectangle', 'legs']
            ],
            [
                'name' => 'LiteTable Workstation',
                'description' => 'Lightweight workstation from LiteTable collection with mobile design and easy assembly.',
                'model' => 'LTW-001',
                'price' => 890.00,
                'stock_quantity' => 25,
                'category_name' => 'Office Desks',
                'image' => '/images/products/litetable-workstation.jpg',
                'status' => 'active',
                'filters' => ['office_systems', 'lite_table', 'mobile', 'height_adjustable', 'stackable', '48_59', '24_29', 'rectangle', 'legs']
            ],

            // Specialty and Computer Tables
            [
                'name' => 'Computer Lab Table',
                'description' => 'Specialized computer table with built-in cable management and CPU storage.',
                'model' => 'CLT-001',
                'price' => 750.00,
                'stock_quantity' => 20,
                'category_name' => 'Office Desks',
                'image' => '/images/products/computer-lab-table.jpg',
                'status' => 'active',
                'filters' => ['specialty_tables', 'fixed_height', '48_59', '30_35', 'rectangle', 'legs']
            ],
            [
                'name' => 'Tano Art Table',
                'description' => 'Art table from Tano collection with tilting surface and supply storage.',
                'model' => 'TAT-001',
                'price' => 650.00,
                'stock_quantity' => 15,
                'category_name' => 'Office Desks',
                'image' => '/images/products/tano-art-table.jpg',
                'status' => 'active',
                'filters' => ['specialty_tables', 'tano', 'height_adjustable', 'tilting_tabletop', '36_47', '24_29', 'rectangle', 'legs']
            ],

            // Occasional Tables
            [
                'name' => 'Mobile Coffee Table',
                'description' => 'Stylish mobile coffee table perfect for lounges and break areas.',
                'model' => 'MCT-001',
                'price' => 420.00,
                'stock_quantity' => 18,
                'category_name' => 'Meeting Tables',
                'image' => '/images/products/mobile-coffee-table.jpg',
                'status' => 'active',
                'filters' => ['occasional_tables', 'mobile', 'fixed_height', '36_47', '24_29', 'half_round', 'legs']
            ],
            [
                'name' => 'Side Table Pro',
                'description' => 'Compact side table with storage compartment and mobile design.',
                'model' => 'STP-001',
                'price' => 280.00,
                'stock_quantity' => 25,
                'category_name' => 'Meeting Tables',
                'image' => '/images/products/side-table-pro.jpg',
                'status' => 'active',
                'filters' => ['occasional_tables', 'mobile', 'fixed_height', '24_35', '19_23', 'rectangle', 'legs']
            ],
            [
                'name' => 'Triangle Learning Pod',
                'description' => 'Triangular table perfect for small group learning activities.',
                'model' => 'TLP-001',
                'price' => 380.00,
                'stock_quantity' => 20,
                'category_name' => 'Meeting Tables',
                'image' => '/images/products/triangle-learning-pod.jpg',
                'status' => 'active',
                'filters' => ['occasional_tables', 'mobile', 'fixed_height', 'stackable', '36_47', '30_35', 'triangle', 'legs']
            ],

            // More Office Systems with various collections
            [
                'name' => 'Shift+ Executive Workstation',
                'description' => 'Premium executive workstation from Shift+ collection with height adjustment and premium finishes.',
                'model' => 'SEW-001',
                'price' => 3200.00,
                'stock_quantity' => 12,
                'category_name' => 'Office Desks',
                'image' => '/images/products/shift-executive-workstation.jpg',
                'status' => 'active',
                'filters' => ['office_systems', 'shift_plus', 'height_adjustable', '72_83', '40_47', 'rectangle', 'column']
            ],
            [
                'name' => 'Uno Collaborative Workstation',
                'description' => 'Open collaborative workstation from Uno collection with shared surfaces.',
                'model' => 'UCW-001',
                'price' => 1850.00,
                'stock_quantity' => 16,
                'category_name' => 'Office Desks',
                'image' => '/images/products/uno-collaborative-workstation.jpg',
                'status' => 'active',
                'filters' => ['office_systems', 'uno', 'fixed_height', '84_95', '36_39', 'other_shapes', 'skids_t_foot']
            ],

            // Additional Products for better filter coverage
            [
                'name' => 'Heavy Duty Workbench',
                'description' => 'Industrial strength workbench with storage and chair suspension system.',
                'model' => 'HDW-001',
                'price' => 950.00,
                'stock_quantity' => 14,
                'category_name' => 'Office Desks',
                'image' => '/images/products/heavy-duty-workbench.jpg',
                'status' => 'active',
                'filters' => ['specialty_tables', 'fixed_height', 'chair_suspension', 'plastic_box', '60_71', '30_35', 'rectangle', 'skids_c_foot']
            ],
            [
                'name' => 'Large Executive Conference Table',
                'description' => 'Extra large conference table for boardrooms and major presentations.',
                'model' => 'LECT-001',
                'price' => 5200.00,
                'stock_quantity' => 5,
                'category_name' => 'Meeting Tables',
                'image' => '/images/products/large-executive-conference.jpg',
                'status' => 'active',
                'filters' => ['multipurpose_tables', 'fixed_height', 'over_96', 'over_48', 'rectangle', 'column']
            ]
        ];

        foreach ($products as $productData) {
            $category = $categories->where('name', $productData['category_name'])->first();
            
            if ($category) {
                // Extract filters before creating product
                $filters = $productData['filters'] ?? [];
                unset($productData['category_name'], $productData['filters']);
                $productData['category_id'] = $category->id;
                
                // Create the product
                $product = Product::create($productData);
                $this->command->info("Created product: {$productData['name']}");
                
                // Associate filters with the product
                if (!empty($filters)) {
                    $filterOptionIds = [];
                    foreach ($filters as $filterValue) {
                        $filterOption = FilterOption::where('value', $filterValue)->first();
                        if ($filterOption) {
                            $filterOptionIds[] = $filterOption->id;
                        }
                    }
                    
                    if (!empty($filterOptionIds)) {
                        $product->filterOptions()->sync($filterOptionIds);
                        $this->command->info("  → Attached " . count($filterOptionIds) . " filters to {$productData['name']}");
                    }
                }
            } else {
                $this->command->warn("Category not found for product: {$productData['name']}");
            }
        }
        
        // Update product counts for all filter options
        $this->command->info('Updating filter option product counts...');
        FilterOption::all()->each(function($option) {
            $option->updateProductCount();
        });
        
        $this->command->info('Products seeded successfully!');
    }
}