<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductFile;
use Illuminate\Support\Facades\Storage;

class ProductFilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample PDF files data for different categories
        $filesData = [
            'manual' => [
                'User Manual - Setup Guide',
                'Installation Instructions',
                'Assembly Manual',
                'Quick Start Guide',
                'Maintenance Manual',
                'Troubleshooting Guide'
            ],
            'catalog' => [
                'Product Catalog 2024',
                'Complete Product Range',
                'New Collections Catalog',
                'Premium Series Catalog',
                'Budget Series Catalog'
            ],
            'specification' => [
                'Technical Specifications',
                'Detailed Measurements',
                'Material Specifications',
                'Performance Data Sheet',
                'Compliance Certificates'
            ],
            'warranty' => [
                'Warranty Information',
                'Extended Warranty Terms',
                'Warranty Registration',
                'Service Agreement',
                'Return Policy'
            ],
            'installation' => [
                'Installation Guide',
                'Professional Installation',
                'DIY Installation Steps',
                'Tools Required List',
                'Safety Instructions'
            ]
        ];

        $products = Product::all();
        
        if ($products->isEmpty()) {
            echo "❌ No products found. Please run ProductsSeeder first.\n";
            return;
        }

        $totalFilesCreated = 0;

        foreach ($products as $product) {
            $filesPerProduct = rand(3, 8); // Random number of files per product
            $categoriesUsed = [];
            
            echo "📦 Adding files for product: {$product->name}\n";

            for ($i = 0; $i < $filesPerProduct; $i++) {
                // Select random category, avoid duplicates when possible
                $availableCategories = array_keys($filesData);
                if (count($categoriesUsed) < count($availableCategories)) {
                    $availableCategories = array_diff($availableCategories, $categoriesUsed);
                }
                
                $category = $availableCategories[array_rand($availableCategories)];
                $categoriesUsed[] = $category;
                
                // Select random file name from category
                $fileName = $filesData[$category][array_rand($filesData[$category])];
                $displayName = $fileName . ' - ' . $product->name;
                
                // Generate realistic file properties
                $fileSize = rand(500000, 5000000); // 500KB to 5MB
                $downloadCount = rand(0, 150);
                $isFeatured = rand(1, 10) <= 2; // 20% chance of being featured
                $isActive = rand(1, 10) <= 9; // 90% chance of being active
                
                // Create unique file name
                $uniqueFileName = strtolower(str_replace([' ', '-'], '_', $fileName)) . '_' . $product->id . '_' . time() . $i . '.pdf';
                
                // Create file record
                $productFile = ProductFile::create([
                    'product_id' => $product->id,
                    'file_name' => $uniqueFileName,
                    'display_name' => $displayName,
                    'description' => $this->generateFileDescription($category, $fileName, $product->name),
                    'file_path' => 'products/files/' . $uniqueFileName,
                    'file_size' => $fileSize,
                    'file_type' => 'application/pdf',
                    'mime_type' => 'application/pdf',
                    'file_category' => $category,
                    'is_active' => $isActive,
                    'is_featured' => $isFeatured,
                    'download_count' => $downloadCount,
                    'sort_order' => $i + 1,
                    'metadata' => json_encode([
                        'original_name' => $fileName . '.pdf',
                        'uploaded_by' => 1,
                        'file_version' => '1.0'
                    ]),
                    'created_at' => now()->subDays(rand(1, 30)), // Random creation date within last 30 days
                    'updated_at' => now()->subDays(rand(0, 5))   // Random update date within last 5 days
                ]);

                $totalFilesCreated++;
                echo "  📄 {$displayName} ({$category})\n";
            }
            
            echo "  ✅ Added {$filesPerProduct} files\n\n";
        }

        // Create some additional featured files for popular products
        $popularProducts = Product::inRandomOrder()->limit(3)->get();
        
        foreach ($popularProducts as $product) {
            // Add a special featured catalog
            ProductFile::create([
                'product_id' => $product->id,
                'file_name' => 'featured_catalog_' . $product->id . '_' . time() . '.pdf',
                'display_name' => 'Featured Product Showcase - ' . $product->name,
                'description' => 'Exclusive showcase featuring our premium ' . $product->name . ' with detailed imagery, specifications, and styling options. Perfect for presentations and client meetings.',
                'file_path' => 'products/files/featured_catalog_' . $product->id . '_' . time() . '.pdf',
                'file_size' => rand(2000000, 8000000), // 2MB to 8MB for featured files
                'file_type' => 'application/pdf',
                'mime_type' => 'application/pdf',
                'file_category' => 'catalog',
                'is_active' => true,
                'is_featured' => true,
                'download_count' => rand(50, 200), // Higher download count for featured
                'sort_order' => 0, // Top priority
                'metadata' => json_encode([
                    'original_name' => 'Featured Product Showcase.pdf',
                    'uploaded_by' => 1,
                    'file_version' => '2.0',
                    'is_premium' => true
                ]),
                'created_at' => now()->subDays(rand(1, 7)),
                'updated_at' => now()->subDays(rand(0, 2))
            ]);
            
            $totalFilesCreated++;
            echo "⭐ Added featured file for: {$product->name}\n";
        }

        echo "\n🎉 Product Files Seeder completed successfully!\n";
        echo "📊 Summary:\n";
        echo "   Total Files Created: {$totalFilesCreated}\n";
        echo "   Files per Product: " . round($totalFilesCreated / $products->count(), 1) . " average\n";
        echo "   Categories Used: " . implode(', ', array_keys($filesData)) . "\n";
        
        // Show statistics by category
        echo "\n📈 Files by Category:\n";
        foreach (array_keys($filesData) as $category) {
            $count = ProductFile::where('file_category', $category)->count();
            echo "   {$category}: {$count} files\n";
        }
        
        echo "\n⭐ Featured Files: " . ProductFile::where('is_featured', true)->count() . "\n";
        echo "✅ Active Files: " . ProductFile::where('is_active', true)->count() . "\n";
    }

    /**
     * Generate appropriate description for file based on category
     */
    private function generateFileDescription($category, $fileName, $productName): string
    {
        $descriptions = [
            'manual' => [
                'Comprehensive user manual covering all aspects of your ' . $productName . '. Includes setup instructions, usage guidelines, and maintenance tips.',
                'Step-by-step guide for getting the most out of your ' . $productName . '. Essential reading for all users.',
                'Detailed manual with illustrations and troubleshooting section for ' . $productName . '.',
                'Complete user guide with safety information and best practices for ' . $productName . '.'
            ],
            'catalog' => [
                'Beautiful product catalog showcasing the ' . $productName . ' with high-quality images and detailed specifications.',
                'Comprehensive catalog featuring the complete range and customization options for ' . $productName . '.',
                'Professional product showcase highlighting the key features and benefits of ' . $productName . '.',
                'Detailed catalog with pricing, specifications, and ordering information for ' . $productName . '.'
            ],
            'specification' => [
                'Technical specifications document containing detailed measurements, materials, and performance data for ' . $productName . '.',
                'Comprehensive spec sheet with all technical details, compliance information, and testing results for ' . $productName . '.',
                'Detailed technical documentation including CAD drawings and material specifications for ' . $productName . '.',
                'Professional specification document with performance metrics and quality standards for ' . $productName . '.'
            ],
            'warranty' => [
                'Complete warranty information including terms, conditions, and coverage details for your ' . $productName . '.',
                'Warranty documentation outlining protection coverage and service procedures for ' . $productName . '.',
                'Comprehensive warranty guide with registration instructions and claim procedures for ' . $productName . '.',
                'Detailed warranty terms and service agreement information for ' . $productName . '.'
            ],
            'installation' => [
                'Professional installation guide with step-by-step instructions for setting up your ' . $productName . '.',
                'Complete installation manual including tools required and safety procedures for ' . $productName . '.',
                'Detailed setup guide with illustrations and tips for proper installation of ' . $productName . '.',
                'Comprehensive installation instructions with troubleshooting section for ' . $productName . '.'
            ]
        ];

        $categoryDescriptions = $descriptions[$category] ?? $descriptions['manual'];
        return $categoryDescriptions[array_rand($categoryDescriptions)];
    }
}