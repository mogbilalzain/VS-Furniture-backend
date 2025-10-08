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
        Schema::create('filter_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filter_category_id')->constrained()->onDelete('cascade');
            $table->string('value'); // e.g., 'mobile', 'fixed_height', 'shift+'
            $table->string('display_name'); // e.g., 'Mobile', 'Fixed height', 'Shift+'
            $table->string('display_name_ar')->nullable(); // Arabic display name
            $table->integer('sort_order')->default(0); // For ordering options within category
            $table->integer('product_count')->default(0); // Number of products with this filter
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Composite index for better performance
            $table->index(['filter_category_id', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filter_options');
    }
};