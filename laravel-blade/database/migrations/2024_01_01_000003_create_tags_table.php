<?php
// database/migrations/2024_01_01_000003_create_tags_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            // Các nhãn xuất hiện trên giao diện: HOT, POPULAR, ORIGINAL, EDITOR_PICK, NEW
            $table->string('name');                    // "HOT", "POPULAR", "ORIGINAL"
            $table->string('slug')->unique();           // "hot", "popular", "original"
            $table->string('color')->nullable();        // Màu badge: "#FF5E36"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
