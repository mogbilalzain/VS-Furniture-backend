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
        // Delete existing images for product 54
        DB::table('product_images')->where('product_id', 54)->delete();
        
        // Real images that exist in public/images/products/
        $realImages = [
            'images/products/executive-leather-chair.jpg',
            'images/products/executive-oak-desk.jpg', 
            'images/products/round-conference-table.jpg',
            'images/products/student-adjustable-desk.jpg',
        ];
        
        // Create 6 images (4 unique + 2 duplicates to make 6 total)
        $allImages = array_merge($realImages, array_slice($realImages, 0, 2));
        
        // Insert images
        foreach ($allImages as $index => $imagePath) {
            DB::table('product_images')->insert([
                'product_id' => 54,
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
