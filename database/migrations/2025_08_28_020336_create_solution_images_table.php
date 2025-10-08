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
        Schema::create('solution_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solution_id')->constrained()->onDelete('cascade'); // مرتبط بجدول solutions
            $table->string('image_path'); // مسار الصورة
            $table->string('alt_text')->nullable(); // نص بديل للصورة
            $table->integer('sort_order')->default(0); // ترتيب الصور
            $table->timestamps();
            
            // فهرس لتحسين الأداء
            $table->index(['solution_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solution_images');
    }
};