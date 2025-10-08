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
            // Add missing fields for enhanced properties
            if (!Schema::hasColumn('category_properties', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('category_properties', 'input_type')) {
                $table->string('input_type')->default('text')->after('description');
            }
            if (!Schema::hasColumn('category_properties', 'is_required')) {
                $table->boolean('is_required')->default(false)->after('input_type');
            }
            if (!Schema::hasColumn('category_properties', 'is_filterable')) {
                $table->boolean('is_filterable')->default(true)->after('is_required');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_properties', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'input_type',
                'is_required',
                'is_filterable'
            ]);
        });
    }
};