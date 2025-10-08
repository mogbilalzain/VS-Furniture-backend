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
        // Recreate the table without unique constraint on name
        Schema::dropIfExists('category_properties_temp');
        
        // Create new table with correct structure
        Schema::create('category_properties_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('name'); // No unique constraint here
            $table->string('display_name');
            $table->string('display_name_ar')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->enum('input_type', ['checkbox', 'radio', 'range', 'select'])->default('checkbox');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add composite unique constraint instead
            $table->unique(['category_id', 'name']);
            $table->index(['category_id', 'is_active']);
        });

        // Copy data from old table if it exists and has data
        if (Schema::hasTable('category_properties')) {
            $oldData = DB::table('category_properties')->get();
            foreach ($oldData as $row) {
                DB::table('category_properties_temp')->insert((array) $row);
            }
        }

        // Drop old table and rename new one
        Schema::dropIfExists('category_properties');
        Schema::rename('category_properties_temp', 'category_properties');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate with original structure if needed
        Schema::dropIfExists('category_properties_temp');
        
        Schema::create('category_properties_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('display_name_ar')->nullable();
            $table->integer('sort_order')->default(0);
            $table->enum('input_type', ['checkbox', 'radio', 'range', 'select'])->default('checkbox');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $oldData = DB::table('category_properties')->get();
        foreach ($oldData as $row) {
            try {
                DB::table('category_properties_temp')->insert((array) $row);
            } catch (\Exception $e) {
                // Skip duplicates
            }
        }

        Schema::dropIfExists('category_properties');
        Schema::rename('category_properties_temp', 'category_properties');
    }
};