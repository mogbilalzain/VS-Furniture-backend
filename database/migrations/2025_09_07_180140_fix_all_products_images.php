<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Available real images in NextJS public folder
        $availableImages = [
            '/images/products/executive-leather-chair.jpg',
            '/images/products/executive-oak-desk.jpg', 
            '/images/products/round-conference-table.jpg',
            '/images/products/student-adjustable-desk.jpg',
        ];
        
        // Get all active products
        $products = DB::table('products')->where('status', 'active')->get();
        
        foreach ($products as $product) {
            // Skip product 54 as it's already fixed
            if ($product->id == 54) {
                continue;
            }
            
            // Check if product has images
            $existingImages = DB::table('product_images')->where('product_id', $product->id)->count();
            
            if ($existingImages == 0) {
                // Create 2-3 random images for each product
                $numImages = rand(2, 3);
                $selectedImages = array_slice($availableImages, 0, $numImages);
                
                foreach ($selectedImages as $index => $imagePath) {
                    DB::table('product_images')->insert([
                        'product_id' => $product->id,
                        'image_url' => $imagePath,
                        'alt_text' => $product->name . " - Image " . ($index + 1),
                        'title' => $product->name . " - Image " . ($index + 1),
                        'sort_order' => $index,
                        'is_primary' => $index === 0 ? 1 : 0,
                        'is_active' => 1,
                        'is_featured' => $index < 2 ? 1 : 0,
                        'image_type' => 'product',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Update existing images to use correct NextJS paths
                $images = DB::table('product_images')->where('product_id', $product->id)->get();
                foreach ($images as $image) {
                    $newUrl = $image->image_url;
                    
                    // Fix URLs that don't point to NextJS images
                    if (!str_starts_with($newUrl, '/images/') && !str_starts_with($newUrl, 'http://localhost:3000')) {
                        $randomImage = $availableImages[array_rand($availableImages)];
                        
                        DB::table('product_images')
                            ->where('id', $image->id)
                            ->update([
                                'image_url' => $randomImage,
                                'alt_text' => $product->name . " - Image",
                                'updated_at' => now()
                            ]);
                    }
                }
            }
            
            // Update main product image field
            if ($product->image && !str_starts_with($product->image, '/images/')) {
                $randomImage = str_replace('/', '', $availableImages[array_rand($availableImages)]);
                
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'image' => $randomImage,
                        'updated_at' => now()
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
