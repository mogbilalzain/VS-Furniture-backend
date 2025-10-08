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
        Schema::table('products', function (Blueprint $table) {
            // حذف أعمدة السعر والمخزون
            $table->dropColumn(['price', 'stock_quantity']);
            
            // إضافة حقول جديدة مفيدة
            $table->text('short_description')->nullable()->after('description'); // وصف مختصر
            $table->json('specifications')->nullable()->after('short_description'); // مواصفات تقنية
            $table->string('sku')->unique()->nullable()->after('model'); // رمز المنتج الفريد
            $table->boolean('is_featured')->default(false)->after('status'); // منتج مميز؟
            $table->integer('sort_order')->default(0)->after('is_featured'); // ترتيب العرض
            $table->integer('views_count')->default(0)->after('sort_order'); // عدد المشاهدات
            
            // فهارس للأداء
            $table->index(['category_id', 'status', 'is_featured']);
            $table->index(['status', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // حذف الفهارس
            $table->dropIndex(['category_id', 'status', 'is_featured']);
            $table->dropIndex(['status', 'sort_order']);
            
            // حذف الأعمدة الجديدة
            $table->dropColumn([
                'short_description', 
                'specifications', 
                'sku', 
                'is_featured', 
                'sort_order',
                'views_count'
            ]);
            
            // إرجاع أعمدة السعر والمخزون
            $table->decimal('price', 10, 2)->default(0)->after('model');
            $table->integer('stock_quantity')->default(0)->after('price');
        });
    }
};