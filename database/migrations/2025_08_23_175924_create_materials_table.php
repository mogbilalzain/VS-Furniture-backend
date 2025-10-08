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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to material_groups
            $table->foreignId('group_id')->constrained('material_groups')->onDelete('cascade');
            
            // Material identification
            $table->string('code', 50)->unique(); // "M030", "F010", "L027", etc.
            $table->string('name'); // "terra grey", "natural beech", etc.
            $table->text('description')->nullable(); // Material description/specifications
            
            // Visual representation - either color or image (or both)
            $table->string('color_hex', 7)->nullable(); // "#8B8680" - for solid colors
            $table->string('image_url', 500)->nullable(); // "/images/materials/m030.jpg" - for textures/patterns
            
            // Display & Status
            $table->integer('sort_order')->default(0); // For ordering materials within group
            $table->boolean('is_active')->default(true); // Enable/disable material
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['group_id', 'is_active', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
            $table->index('code'); // For quick code lookups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};