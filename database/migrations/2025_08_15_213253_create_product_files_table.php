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
        Schema::create('product_files', function (Blueprint $table) {
            $table->id();
            
            // ربط مع المنتج
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            // معلومات الملف
            $table->string('file_name'); // الاسم الأصلي للملف
            $table->string('file_path'); // مسار الملف المحفوظ
            $table->string('file_size'); // حجم الملف بالبايت
            $table->string('file_type')->default('application/pdf'); // نوع الملف
            $table->string('mime_type')->default('application/pdf'); // MIME type
            
            // معلومات العرض
            $table->string('display_name'); // اسم العرض للعميل
            $table->text('description')->nullable(); // وصف الملف
            $table->integer('sort_order')->default(0); // ترتيب العرض
            
            // حالة الملف
            $table->boolean('is_active')->default(true); // هل الملف متاح للتحميل؟
            $table->boolean('is_featured')->default(false); // هل الملف مميز؟
            
            // إحصائيات
            $table->integer('download_count')->default(0); // عدد مرات التحميل
            $table->timestamp('last_downloaded_at')->nullable(); // آخر مرة تحميل
            
            // معلومات إضافية
            $table->string('file_category')->nullable(); // فئة الملف (catalog, manual, specs, etc.)
            $table->json('metadata')->nullable(); // معلومات إضافية (عدد الصفحات، اللغة، إلخ)
            
            $table->timestamps();
            
            // فهارس للأداء
            $table->index(['product_id', 'is_active']);
            $table->index(['product_id', 'is_featured']);
            $table->index(['file_category', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_files');
    }
};