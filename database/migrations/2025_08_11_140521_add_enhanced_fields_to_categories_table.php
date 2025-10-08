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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon')->default('fas fa-cube')->after('slug');
            $table->string('color')->default('#3d5c4d')->after('icon');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('color');
            $table->decimal('revenue', 12, 2)->default(0)->after('status');
            $table->integer('orders_count')->default(0)->after('revenue');
            
            // Drop the old is_active column since we're using status now
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color', 'status', 'revenue', 'orders_count']);
            $table->boolean('is_active')->default(true)->after('slug');
        });
    }
};