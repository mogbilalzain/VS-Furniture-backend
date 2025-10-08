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
        // Update all product images to use correct NextJS URLs
        DB::table('product_images')
            ->where('product_id', 54)
            ->update([
                'image_url' => DB::raw("REPLACE(image_url, 'images/products/', '/images/products/')"),
                'updated_at' => now()
            ]);
        
        // Also ensure they start with correct path
        $images = DB::table('product_images')->where('product_id', 54)->get();
        
        foreach ($images as $image) {
            $newUrl = $image->image_url;
            
            // Make sure URL starts with /images/ for NextJS
            if (!str_starts_with($newUrl, '/images/')) {
                if (str_starts_with($newUrl, 'images/')) {
                    $newUrl = '/' . $newUrl;
                } else {
                    // Extract filename and create proper NextJS path
                    $filename = basename($newUrl);
                    $newUrl = '/images/products/' . $filename;
                }
                
                DB::table('product_images')
                    ->where('id', $image->id)
                    ->update([
                        'image_url' => $newUrl,
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
