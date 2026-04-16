<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_properties', function (Blueprint $table) {
            $table->foreignId('property_group_id')->nullable()->after('category_id')
                ->constrained('property_groups')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('category_properties', function (Blueprint $table) {
            $table->dropForeign(['property_group_id']);
            $table->dropColumn('property_group_id');
        });
    }
};
