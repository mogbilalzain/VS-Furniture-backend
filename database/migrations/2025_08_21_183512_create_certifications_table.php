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
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index(); // عنوان الشهادة
            $table->text('description')->nullable(); // تفاصيل الشهادة
            $table->string('image_url')->nullable(); // رابط الصورة/الشعار
            $table->boolean('is_active')->default(true); // حالة الشهادة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
