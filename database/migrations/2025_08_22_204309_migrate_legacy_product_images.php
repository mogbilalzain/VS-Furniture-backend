<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductImage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migration to move legacy product images from 'image' field to 'product_images' table
        $products = Product::whereNotNull('image')
            ->where('image', '!=', '')
            ->get();

        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($products as $product) {
            // Check if this product already has images in the new table
            $existingImages = ProductImage::where('product_id', $product->id)->count();
            
            if ($existingImages === 0) {
                // Create a new product image record from the legacy image field
                try {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $product->image,
                        'alt_text' => $product->name . ' - Product Image',
                        'title' => $product->name,
                        'sort_order' => 1,
                        'is_primary' => true,
                        'is_active' => true,
                        'is_featured' => false,
                        'image_type' => 'product',
                        'metadata' => [
                            'migrated_from_legacy' => true,
                            'migration_date' => now()->toISOString(),
                        ]
                    ]);
                    $migratedCount++;
                } catch (\Exception $e) {
                    \Log::error("Failed to migrate image for product {$product->id}: " . $e->getMessage());
                    $skippedCount++;
                }
            } else {
                $skippedCount++;
            }
        }

        // Log migration results
        \Log::info("Legacy image migration completed. Migrated: {$migratedCount}, Skipped: {$skippedCount}");
        
        // Optionally, you can comment out the line below if you want to keep legacy images as backup
        // Schema::table('products', function (Blueprint $table) {
        //     $table->dropColumn('image');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated images that were created from legacy data
        ProductImage::whereJsonContains('metadata->migrated_from_legacy', true)->delete();
        
        // If you dropped the 'image' column in up(), uncomment the line below to restore it
        // Schema::table('products', function (Blueprint $table) {
        //     $table->string('image')->nullable()->after('category_id');
        // });
    }
};