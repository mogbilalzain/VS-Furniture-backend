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
        Schema::create('contact_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained('contact_messages')->onDelete('cascade');
            $table->string('original_name'); // اسم الملف الأصلي
            $table->string('stored_name'); // اسم الملف المحفوظ
            $table->string('file_path'); // مسار الملف
            $table->string('mime_type'); // نوع الملف
            $table->unsignedBigInteger('file_size'); // حجم الملف بالبايت
            $table->string('file_extension', 10); // امتداد الملف
            $table->boolean('is_safe')->default(true); // هل الملف آمن
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('contact_message_id');
            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_files');
    }
};
