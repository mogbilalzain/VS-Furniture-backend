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
        Schema::create('product_materials', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            
            // Relationship properties
            $table->boolean('is_default')->default(false); // Is this the default material for this product?
            $table->integer('sort_order')->default(0); // Order of materials for this product
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_default']);
            $table->index(['material_id']);
            
            // Unique constraint to prevent duplicate material assignments
            $table->unique(['product_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_materials');
    }
};