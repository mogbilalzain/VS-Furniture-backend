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
        Schema::create('material_groups', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to material_categories
            $table->foreignId('category_id')->constrained('material_categories')->onDelete('cascade');
            
            // Basic information
            $table->string('name'); // "Group M1 Metals", "Group F1 Veneer", etc.
            $table->text('description')->nullable(); // Group description/details
            
            // Display & Status
            $table->integer('sort_order')->default(0); // For ordering groups within category
            $table->boolean('is_active')->default(true); // Enable/disable group
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_groups');
    }
};