<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductFile;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Creating test product files...\n";

// Get first product
$product = Product::first();

if (!$product) {
    echo "No products found. Please create products first.\n";
    exit;
}

echo "Adding files for product: {$product->name} (ID: {$product->id})\n";

// Create test files
$testFiles = [
    [
        'display_name' => 'Product Cut Sheet - ' . $product->name,
        'description' => 'Technical specifications and dimensions for ' . $product->name,
        'file_category' => 'specs',
        'is_featured' => true,
    ],
    [
        'display_name' => 'User Manual - ' . $product->name,
        'description' => 'Complete user manual with setup and maintenance instructions',
        'file_category' => 'manual',
        'is_featured' => false,
    ],
    [
        'display_name' => 'Product Catalog - ' . $product->name,
        'description' => 'Beautiful product catalog with high-quality images',
        'file_category' => 'catalog',
        'is_featured' => false,
    ]
];

foreach ($testFiles as $index => $fileData) {
    $fileName = 'test_file_' . $product->id . '_' . ($index + 1) . '.pdf';
    
    ProductFile::create([
        'product_id' => $product->id,
        'file_name' => $fileName,
        'display_name' => $fileData['display_name'],
        'description' => $fileData['description'],
        'file_path' => 'products/files/' . $fileName,
        'file_size' => rand(500000, 2000000), // 500KB to 2MB
        'file_type' => 'application/pdf',
        'mime_type' => 'application/pdf',
        'file_category' => $fileData['file_category'],
        'is_active' => true,
        'is_featured' => $fileData['is_featured'],
        'download_count' => rand(5, 50),
        'sort_order' => $index + 1,
        'metadata' => json_encode([
            'original_name' => $fileData['display_name'] . '.pdf',
            'uploaded_by' => 1,
            'file_version' => '1.0'
        ]),
    ]);
    
    echo "✅ Created: {$fileData['display_name']}\n";
}

$totalFiles = ProductFile::where('product_id', $product->id)->count();
echo "\n🎉 Successfully created {$totalFiles} files for product: {$product->name}\n";

// Show all files for this product
echo "\n📋 Files for product {$product->name}:\n";
$files = ProductFile::where('product_id', $product->id)->get();
foreach ($files as $file) {
    $featured = $file->is_featured ? ' (Featured)' : '';
    echo "  - {$file->display_name}{$featured}\n";
}

echo "\nDone!\n";
