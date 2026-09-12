<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('upload_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comic_id')->constrained()->cascadeOnDelete();
            $table->uuid('upload_session_id');
            $table->string('status', 32)->default('preparing');
            $table->unsignedBigInteger('version')->default(0);
            $table->uuid('worker_id')->nullable();
            foreach (['total_chapters', 'uploadable_chapters', 'completed_chapters', 'skipped_chapters', 'total_files', 'uploaded_files', 'current_chapter_index', 'current_batch', 'total_batches'] as $column) {
                $table->unsignedInteger($column)->default(0);
            }
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->unsignedBigInteger('uploaded_bytes')->default(0);
            $table->string('current_chapter_number', 32)->nullable();
            $table->json('manifest');
            $table->text('error_message')->nullable();
            $table->json('error_context')->nullable();
            foreach (['started_at', 'last_heartbeat_at', 'completed_at', 'expires_at'] as $column) {
                $table->timestamp($column)->nullable();
            }
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_tasks');
    }
};
