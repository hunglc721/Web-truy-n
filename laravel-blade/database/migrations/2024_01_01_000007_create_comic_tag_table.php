<?php
// database/migrations/2024_01_01_000007_create_comic_tag_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table: comics <──nhiều-nhiều──> tags
     *
     * Ví dụ từ giao diện:
     *   Solo Leveling → HOT, ORIGINAL, EDITOR_PICK
     *   Lore Olympus  → POPULAR
     */
    public function up(): void
    {
        Schema::create('comic_tag', function (Blueprint $table) {
            $table->foreignId('comic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('tag_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->primary(['comic_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comic_tag');
    }
};
