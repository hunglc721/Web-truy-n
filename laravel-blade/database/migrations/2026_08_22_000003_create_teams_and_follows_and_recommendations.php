<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bảng Nhóm dịch (Scanlation Teams)
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->string('website')->nullable();
            $table->string('facebook')->nullable();
            $table->string('discord')->nullable();
            $table->unsignedInteger('members_count')->default(1);
            $table->timestamps();
        });

        // 2. Pivot Comic - Team
        Schema::create('comic_team', function (Blueprint $table) {
            $table->foreignId('comic_id')->constrained('comics')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['comic_id', 'team_id']);
        });

        // 3. Follow Tác giả (Author Follows)
        Schema::create('author_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('authors')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'author_id']);
            $table->index('author_id');
        });

        // 4. Follow Nhóm dịch (Team Follows)
        Schema::create('team_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'team_id']);
            $table->index('team_id');
        });

        // 5. Ma trận Gợi ý Truyện (Precomputed Item-based Collaborative Recommendations)
        Schema::create('comic_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->constrained('comics')->cascadeOnDelete();
            $table->foreignId('recommended_comic_id')->constrained('comics')->cascadeOnDelete();
            $table->float('score')->default(0); // Jaccard similarity score
            $table->timestamps();
            $table->unique(['comic_id', 'recommended_comic_id']);
            $table->index(['comic_id', 'score']);
        });

        // 6. Đăng ký Web Push Notifications
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->default('aesgcm');
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('comic_recommendations');
        Schema::dropIfExists('team_follows');
        Schema::dropIfExists('author_follows');
        Schema::dropIfExists('comic_team');
        Schema::dropIfExists('teams');
    }
};
