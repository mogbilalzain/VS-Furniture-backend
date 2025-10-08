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
        // Create some sample storage images for testing
        $storageImages = [
            '/storage/products/1757183319_VSIMC_concepts_media_76688_inspo-007_3x2.webp',
            '/storage/products/1757183320_executive-leather-chair.jpg',
            '/storage/products/1757183321_executive-oak-desk.jpg',
            '/storage/products/1757183322_round-conference-table.jpg',
            '/storage/products/1757183323_student-adjustable-desk.jpg',
        ];
        
        // Update all product images to use Laravel storage paths
        $products = DB::table('products')->where('status', 'active')->get();
        
        foreach ($products as $product) {
            // Update product_images table
            $images = DB::table('product_images')->where('product_id', $product->id)->get();
            
            foreach ($images as $index => $image) {
                // Select a random storage image
                $randomStorageImage = $storageImages[$index % count($storageImages)];
                
                DB::table('product_images')
                    ->where('id', $image->id)
                    ->update([
                        'image_url' => $randomStorageImage,
                        'alt_text' => $product->name . " - Image " . ($index + 1),
                        'updated_at' => now()
                    ]);
            }
            
            // Update main product image field
            if ($product->image) {
                $randomStorageImage = str_replace('/storage/', '', $storageImages[0]);
                
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'image' => $randomStorageImage,
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
        // Revert back to NextJS images if needed
        $nextjsImages = [
            '/images/products/executive-leather-chair.jpg',
            '/images/products/executive-oak-desk.jpg',
            '/images/products/round-conference-table.jpg',
            '/images/products/student-adjustable-desk.jpg',
        ];
        
        $products = DB::table('products')->where('status', 'active')->get();
        
        foreach ($products as $product) {
            $images = DB::table('product_images')->where('product_id', $product->id)->get();
            
            foreach ($images as $index => $image) {
                $randomNextjsImage = $nextjsImages[$index % count($nextjsImages)];
                
                DB::table('product_images')
                    ->where('id', $image->id)
                    ->update([
                        'image_url' => $randomNextjsImage,
                        'updated_at' => now()
                    ]);
            }
        }
    }
};
