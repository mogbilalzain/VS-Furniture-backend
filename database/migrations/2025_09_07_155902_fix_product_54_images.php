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
        // Fix product 54 images - ensure they have unique URLs
        $productId = 54;
        
        // Get all images for product 54
        $images = DB::table('product_images')->where('product_id', $productId)->get();
        
        if ($images->count() > 0) {
            // Check if all images have the same URL
            $urls = $images->pluck('image_url')->unique();
            
            if ($urls->count() === 1 && $images->count() > 1) {
                // All images have the same URL, fix them
                $baseUrl = $urls->first();
                $pathInfo = pathinfo($baseUrl);
                
                foreach ($images as $index => $image) {
                    $newUrl = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . ($index + 1) . '.' . $pathInfo['extension'];
                    
                    DB::table('product_images')
                        ->where('id', $image->id)
                        ->update([
                            'image_url' => $newUrl,
                            'alt_text' => "Product Image " . ($index + 1),
                            'title' => "Product Image " . ($index + 1),
                            'sort_order' => $index,
                            'updated_at' => now(),
                        ]);
                }
            }
        } else {
            // No images exist, create sample images
            $sampleImages = [
                'images/products/desk-1.jpg',
                'images/products/desk-2.jpg',
                'images/products/desk-3.jpg',
                'images/products/desk-4.jpg',
                'images/products/desk-5.jpg',
                'images/products/desk-6.jpg',
            ];
            
            foreach ($sampleImages as $index => $imagePath) {
                DB::table('product_images')->insert([
                    'product_id' => $productId,
                    'image_url' => $imagePath,
                    'alt_text' => "Product Image " . ($index + 1),
                    'title' => "Product Image " . ($index + 1),
                    'sort_order' => $index,
                    'is_primary' => $index === 0 ? 1 : 0,
                    'is_active' => 1,
                    'is_featured' => $index < 3 ? 1 : 0,
                    'image_type' => 'product',
                    'created_at' => now(),
                    'updated_at' => now(),
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
