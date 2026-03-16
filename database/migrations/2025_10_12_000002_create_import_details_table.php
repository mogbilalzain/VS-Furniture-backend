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
        Schema::create('import_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_log_id')->constrained()->onDelete('cascade');
            $table->integer('row_number');
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->enum('status', ['success', 'failed', 'skipped'])->default('failed');
            $table->text('error_message')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('images_uploaded')->default(0);
            $table->json('matched_images')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('import_log_id');
            $table->index('status');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_details');
    }
};

