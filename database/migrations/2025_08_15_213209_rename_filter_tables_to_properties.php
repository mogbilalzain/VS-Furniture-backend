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
        // إعادة تسمية الجداول من نظام الفلاتر إلى نظام الخصائص
        
        // 1. إعادة تسمية filter_categories إلى category_properties
        Schema::rename('filter_categories', 'category_properties');
        
        // 2. إعادة تسمية filter_options إلى property_values
        Schema::rename('filter_options', 'property_values');
        
        // 3. إعادة تسمية product_filter_options إلى product_property_values
        Schema::rename('product_filter_options', 'product_property_values');
        
        // 4. تحديث الأعمدة في property_values
        Schema::table('property_values', function (Blueprint $table) {
            // إعادة تسمية filter_category_id إلى category_property_id
            $table->renameColumn('filter_category_id', 'category_property_id');
        });
        
        // 5. تحديث الأعمدة في product_property_values
        Schema::table('product_property_values', function (Blueprint $table) {
            // إعادة تسمية filter_option_id إلى property_value_id
            $table->renameColumn('filter_option_id', 'property_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // العكس - إرجاع الأسماء القديمة
        
        // 1. إرجاع الأعمدة في product_property_values
        Schema::table('product_property_values', function (Blueprint $table) {
            $table->renameColumn('property_value_id', 'filter_option_id');
        });
        
        // 2. إرجاع الأعمدة في property_values
        Schema::table('property_values', function (Blueprint $table) {
            $table->renameColumn('category_property_id', 'filter_category_id');
        });
        
        // 3. إرجاع أسماء الجداول
        Schema::rename('product_property_values', 'product_filter_options');
        Schema::rename('property_values', 'filter_options');
        Schema::rename('category_properties', 'filter_categories');
    }
};