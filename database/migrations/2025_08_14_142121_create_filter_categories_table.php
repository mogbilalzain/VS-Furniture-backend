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
        Schema::create('filter_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'type', 'collections', 'mobility'
            $table->string('display_name'); // e.g., 'Type', 'Collections', 'Mobility'
            $table->string('display_name_ar')->nullable(); // Arabic display name
            $table->integer('sort_order')->default(0); // For ordering filters
            $table->enum('input_type', ['checkbox', 'radio', 'range', 'select'])->default('checkbox');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filter_categories');
    }
};