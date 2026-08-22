<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Danh sách đọc tuỳ chỉnh (Custom Reading Lists)
        Schema::create('reading_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();
            $table->index(['is_public', 'created_at']);
        });

        // 2. Pivot ReadingList - Comic
        Schema::create('comic_reading_list', function (Blueprint $table) {
            $table->foreignId('reading_list_id')->constrained('reading_lists')->cascadeOnDelete();
            $table->foreignId('comic_id')->constrained('comics')->cascadeOnDelete();
            $table->unsignedInteger('order_position')->default(0);
            $table->timestamps();
            $table->primary(['reading_list_id', 'comic_id']);
        });

        // 3. Like Reading List
        Schema::create('reading_list_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reading_list_id')->constrained('reading_lists')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'reading_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_list_likes');
        Schema::dropIfExists('comic_reading_list');
        Schema::dropIfExists('reading_lists');
    }
};
