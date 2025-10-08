<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Database configuration
$capsule = new DB;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'vs_furniture',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    echo "🔧 Fixing product image paths...\n";
    
    // Update product images to use correct paths
    $updates = [
        'Executive Oak Desk' => '/images/products/executive-oak-desk.jpg',
        'Student Adjustable Desk' => '/images/products/student-adjustable-desk.jpg', 
        'Round Conference Table' => '/images/products/round-conference-table.jpg',
        'Executive Leather Chair' => '/images/products/executive-leather-chair.jpg'
    ];
    
    foreach ($updates as $productName => $imagePath) {
        $updated = DB::table('products')
            ->where('name', $productName)
            ->update(['image' => $imagePath]);
            
        if ($updated) {
            echo "✅ Updated: $productName -> $imagePath\n";
        } else {
            echo "⚠️  Product not found: $productName\n";
        }
    }
    
    echo "\n🔧 Fixing category image paths...\n";
    
    // Update category images to use placeholder
    $categoryUpdated = DB::table('categories')
        ->whereNull('image')
        ->orWhere('image', '')
        ->update(['image' => '/products/default-category.jpg']);
        
    echo "✅ Updated $categoryUpdated categories with default image\n";
    
    echo "\n🎉 Image paths fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}