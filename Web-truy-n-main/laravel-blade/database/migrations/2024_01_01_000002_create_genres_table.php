<?php
// database/migrations/2024_01_01_000002_create_genres_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');                   // "Fantasy", "Romance", "Action"...
            $table->string('slug')->unique();          // "fantasy", "romance", "action"
            $table->string('icon')->nullable();        // Emoji hoặc icon class: "⚔️"
            $table->text('description')->nullable();   // Mô tả thể loại
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
