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
        Schema::create('homepage_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section')->index(); // 'real_spaces', 'hero', 'what_we_do', etc.
            $table->string('type')->default('video'); // 'video', 'image', 'text'
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url')->nullable(); // YouTube URL or uploaded video path
            $table->string('video_id')->nullable(); // YouTube video ID
            $table->string('thumbnail')->nullable(); // Thumbnail image path
            $table->string('link_url')->nullable(); // Optional link
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional data like duration, views, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_contents');
    }
};
