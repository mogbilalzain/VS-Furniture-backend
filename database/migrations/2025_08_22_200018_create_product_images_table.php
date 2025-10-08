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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            
            // ربط مع المنتج
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            // معلومات الصورة
            $table->string('image_url'); // مسار الصورة
            $table->string('alt_text')->nullable(); // النص البديل للصورة (SEO & Accessibility)
            $table->string('title')->nullable(); // عنوان الصورة
            
            // ترتيب وأولوية
            $table->integer('sort_order')->default(0); // ترتيب عرض الصور
            $table->boolean('is_primary')->default(false); // هل هي الصورة الأساسية؟
            
            // حالة الصورة
            $table->boolean('is_active')->default(true); // هل الصورة متاحة؟
            $table->boolean('is_featured')->default(false); // هل الصورة مميزة؟
            
            // معلومات إضافية
            $table->string('image_type')->default('product'); // نوع الصورة (product, variant, detail, etc.)
            $table->json('metadata')->nullable(); // معلومات إضافية (أبعاد، حجم، إلخ)
            
            // طوابع زمنية
            $table->timestamps();
            
            // فهارس للأداء
            $table->index(['product_id', 'sort_order']); // للترتيب السريع
            $table->index(['product_id', 'is_primary']); // للعثور على الصورة الأساسية
            $table->index(['product_id', 'is_active']); // للصور النشطة
            $table->index(['is_featured']); // للصور المميزة
            
            // قيد فريد: منتج واحد = صورة أساسية واحدة
            $table->unique(['product_id', 'is_primary'], 'unique_primary_per_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};