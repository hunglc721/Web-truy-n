<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Thêm độ tuổi và cảnh báo nội dung 18+ vào bảng comics
        Schema::table('comics', function (Blueprint $table) {
            $table->boolean('is_mature')->default(false)->after('status');
            $table->string('age_rating', 10)->default('all')->after('is_mature'); // all, 13+, 16+, 18+
            $table->string('content_warning')->nullable()->after('age_rating');
            $table->index('is_mature');
        });

        // 2. Bảng tiếp nhận khiếu nại bản quyền DMCA
        Schema::create('dmca_reports', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('company_name')->nullable();
            $table->string('work_title'); // Tên tác phẩm gốc bị vi phạm
            $table->text('infringing_url'); // Link truyện trên web
            $table->text('original_work_proof'); // Bằng chứng / link tác phẩm gốc
            $table->text('details')->nullable();
            $table->boolean('good_faith_statement')->default(true);
            $table->string('status', 20)->default('pending'); // pending, investigating, resolved, rejected
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dmca_reports');

        Schema::table('comics', function (Blueprint $table) {
            $table->dropIndex(['is_mature']);
            $table->dropColumn(['is_mature', 'age_rating', 'content_warning']);
        });
    }
};
