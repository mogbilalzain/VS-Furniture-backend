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
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('name'); // "Metal Colors", "Veneers", "Laminates"
            $table->string('slug')->unique(); // "metal-colors", "veneers", "laminates"
            $table->text('description')->nullable(); // Category description
            
            // Display & Status
            $table->integer('sort_order')->default(0); // For ordering categories
            $table->boolean('is_active')->default(true); // Enable/disable category
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['is_active', 'sort_order']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_categories');
    }
};