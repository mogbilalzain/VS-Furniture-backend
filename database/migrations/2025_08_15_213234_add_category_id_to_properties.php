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
        Schema::table('category_properties', function (Blueprint $table) {
            // إضافة category_id لربط الخصائص بالفئات
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->onDelete('cascade');
            
            // إضافة فهرس للأداء
            $table->index(['category_id', 'is_active']);
            
            // إضافة حقول جديدة مفيدة
            $table->boolean('is_required')->default(false)->after('input_type'); // هل الخاصية مطلوبة؟
            $table->text('description')->nullable()->after('display_name_ar'); // وصف الخاصية
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_properties', function (Blueprint $table) {
            // حذف الفهرس أولاً
            $table->dropIndex(['category_id', 'is_active']);
            
            // حذف الأعمدة الجديدة
            $table->dropColumn(['description', 'is_required']);
            
            // حذف المفتاح الخارجي والعمود
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};