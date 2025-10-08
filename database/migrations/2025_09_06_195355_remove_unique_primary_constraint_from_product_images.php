<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            // Remove the problematic unique constraint
            $table->dropUnique('unique_primary_per_product');
            
            // Add a proper unique constraint only for primary images (where is_primary = 1)
            // We'll handle this logic in the application code instead
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            // Restore the original constraint if needed
            $table->unique(['product_id', 'is_primary'], 'unique_primary_per_product');
        });
    }
};