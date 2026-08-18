<?php
// database/migrations/2024_01_01_000001_create_authors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // Tên tác giả / studio
            $table->string('slug')->unique();             // URL-friendly: "chugong"
            $table->text('bio')->nullable();              // Tiểu sử
            $table->string('avatar')->nullable();         // Ảnh đại diện
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
