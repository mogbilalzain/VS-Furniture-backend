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
        Schema::create('product_filter_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('filter_option_id')->constrained()->onDelete('cascade');
            $table->string('custom_value')->nullable(); // For range filters like dimensions
            $table->timestamps();
            
            // Prevent duplicate entries
            $table->unique(['product_id', 'filter_option_id']);
            
            // Indexes for better performance
            $table->index('product_id');
            $table->index('filter_option_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_filter_options');
    }
};