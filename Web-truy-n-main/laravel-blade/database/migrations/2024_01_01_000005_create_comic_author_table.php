<?php
// database/migrations/2024_01_01_000005_create_comic_author_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table: comics <──nhiều-nhiều──> authors
     *
     * Ví dụ từ giao diện:
     *   Solo Leveling → Chugong (author) + REDICE Studio (artist)
     */
    public function up(): void
    {
        Schema::create('comic_author', function (Blueprint $table) {
            $table->foreignId('comic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('author_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Vai trò của tác giả trong truyện
            $table->enum('role', [
                'story',    // Tác giả kịch bản (Chugong)
                'art',      // Họa sĩ (REDICE Studio)
                'both',     // Vừa viết vừa vẽ (một mình)
            ])->default('both');

            $table->primary(['comic_id', 'author_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comic_author');
    }
};
