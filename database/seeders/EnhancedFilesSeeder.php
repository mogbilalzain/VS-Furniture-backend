<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductFile;

class EnhancedFilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createProductFiles();
        $this->command->info('Enhanced product files created successfully!');
    }

    private function createProductFiles()
    {
        $products = Product::all();

        foreach ($products as $product) {
            $this->createFilesForProduct($product);
        }
    }

    private function createFilesForProduct($product)
    {
        $baseFiles = [
            [
                'file_name' => strtolower(str_replace(' ', '-', $product->name)) . '-manual.pdf',
                'file_path' => '/storage/product-files/' . strtolower(str_replace(' ', '-', $product->name)) . '-manual.pdf',
                'display_name' => $product->name . ' - User Manual',
                'description' => 'Complete user manual with setup instructions, usage guidelines, and maintenance tips.',
                'file_category' => 'manual',
                'file_type' => 'application/pdf',
                'file_size' => rand(500000, 2000000), // 500KB to 2MB
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'download_count' => rand(5, 50)
            ],
            [
                'file_name' => strtolower(str_replace(' ', '-', $product->name)) . '-specifications.pdf',
                'file_path' => '/storage/product-files/' . strtolower(str_replace(' ', '-', $product->name)) . '-specifications.pdf',
                'display_name' => $product->name . ' - Technical Specifications',
                'description' => 'Detailed technical specifications including dimensions, materials, and performance data.',
                'file_category' => 'specification',
                'file_type' => 'application/pdf',
                'file_size' => rand(200000, 800000), // 200KB to 800KB
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 2,
                'download_count' => rand(10, 30)
            ],
            [
                'file_name' => strtolower(str_replace(' ', '-', $product->name)) . '-warranty.pdf',
                'file_path' => '/storage/product-files/' . strtolower(str_replace(' ', '-', $product->name)) . '-warranty.pdf',
                'display_name' => $product->name . ' - Warranty Information',
                'description' => 'Warranty terms, conditions, and service information for your product.',
                'file_category' => 'warranty',
                'file_type' => 'application/pdf',
                'file_size' => rand(100000, 500000), // 100KB to 500KB
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'download_count' => rand(2, 15)
            ]
        ];

        // Add category-specific files
        $categorySpecificFiles = $this->getCategorySpecificFiles($product);
        $allFiles = array_merge($baseFiles, $categorySpecificFiles);

        foreach ($allFiles as $fileData) {
            $fileData['product_id'] = $product->id;
            ProductFile::create($fileData);
        }
    }

    private function getCategorySpecificFiles($product)
    {
        $categorySlug = $product->category->slug ?? '';
        $files = [];

        switch ($categorySlug) {
            case 'office-desks':
                $files[] = [
                    'file_name' => strtolower(str_replace(' ', '-', $product->name)) . '-assembly.pdf',
                    'file_path' => '/storage/product-files/' . strtolower(str_replace(' ', '-', $product->name)) . '-assembly.pdf',
                    'display_name' => $product->name . ' - Assembly Instructions',
                    'description' => 'Step-by-step assembly instructions with diagrams and hardware list.',
                    'file_category' => 'installation',
                    'file_type' => 'application/pdf',
                    'file_size' => rand(1000000, 3000000), // 1MB to 3MB
                    'is_active' => true,
                    'is_featured' => true,
                    'sort_order' => 4,
                    'download_count' => rand(15, 40)
                ];
                break;

            case 'meeting-tables':
                $files[] = [
                    'file_name' => strtolower(str_replace(' ', '-', $product->name)) . '-setup-guide.pdf',
                    'file_path' => '/storage/product-files/' . strtolower(str_replace(' ', '-', $product->name)) . '-setup-guide.pdf',
                    'display_name' => $product->name . ' - Setup Guide',
                    'description' => 'Conference room setup guide including cable management and technology integration.',
                    'file_category' => 'installation',
                    'file_type' => 'application/pdf',
                    'file_size' => rand(800000, 2500000), // 800KB to 2.5MB
                    'is_active' => true,
                    'is_featured' => false,
                    'sort_order' => 4,
                    'download_count' => rand(8, 25)
                ];
                break;

            case 'office-chairs':
                $files[] = [
                    'file_name' => strtolower(str_replace(' ', '-', $product->name)) . '-ergonomic-guide.pdf',
                    'file_path' => '/storage/product-files/' . strtolower(str_replace(' ', '-', $product->name)) . '-ergonomic-guide.pdf',
                    'display_name' => $product->name . ' - Ergonomic Setup Guide',
                    'description' => 'Guide to proper ergonomic setup and adjustment for maximum comfort and health.',
                    'file_category' => 'manual',
                    'file_type' => 'application/pdf',
                    'file_size' => rand(600000, 1500000), // 600KB to 1.5MB
                    'is_active' => true,
                    'is_featured' => true,
                    'sort_order' => 4,
                    'download_count' => rand(20, 60)
                ];
                break;

            case 'storage-solutions':
                $files[] = [
                    'file_name' => strtolower(str_replace(' ', '-', $product->name)) . '-organization-tips.pdf',
                    'file_path' => '/storage/product-files/' . strtolower(str_replace(' ', '-', $product->name)) . '-organization-tips.pdf',
                    'display_name' => $product->name . ' - Organization Tips',
                    'description' => 'Tips and best practices for organizing your office documents and supplies.',
                    'file_category' => 'other',
                    'file_type' => 'application/pdf',
                    'file_size' => rand(300000, 1000000), // 300KB to 1MB
                    'is_active' => true,
                    'is_featured' => false,
                    'sort_order' => 4,
                    'download_count' => rand(5, 20)
                ];
                break;
        }

        // Add a catalog file for featured products
        if ($product->is_featured) {
            $files[] = [
                'file_name' => strtolower(str_replace(' ', '-', $product->name)) . '-catalog.pdf',
                'file_path' => '/storage/product-files/' . strtolower(str_replace(' ', '-', $product->name)) . '-catalog.pdf',
                'display_name' => $product->name . ' - Product Catalog',
                'description' => 'Complete product catalog with images, features, and available options.',
                'file_category' => 'catalog',
                'file_type' => 'application/pdf',
                'file_size' => rand(2000000, 5000000), // 2MB to 5MB
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 5,
                'download_count' => rand(25, 80)
            ];
        }

        return $files;
    }
}