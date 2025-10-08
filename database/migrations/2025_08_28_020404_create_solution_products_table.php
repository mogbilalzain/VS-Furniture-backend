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
        Schema::create('solution_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solution_id')->constrained()->onDelete('cascade'); // مرتبط بجدول solutions
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // مرتبط بجدول products
            $table->timestamps();
            
            // منع التكرار - كل منتج يمكن أن يرتبط بحل واحد فقط
            $table->unique(['solution_id', 'product_id']);
            
            // فهارس لتحسين الأداء
            $table->index('solution_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solution_products');
    }
};