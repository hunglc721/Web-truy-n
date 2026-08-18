<?php
// database/migrations/2024_01_01_000006_create_comic_genre_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table: comics <──nhiều-nhiều──> genres
     *
     * Ví dụ từ giao diện:
     *   Solo Leveling → Action · Fantasy
     *   Lore Olympus  → Romance · Mythology
     */
    public function up(): void
    {
        Schema::create('comic_genre', function (Blueprint $table) {
            $table->foreignId('comic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('genre_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Thể loại chính (hiển thị đầu tiên trên card)
            $table->boolean('is_primary')->default(false);

            $table->primary(['comic_id', 'genre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comic_genre');
    }
};
