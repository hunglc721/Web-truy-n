<?php
// database/migrations/2024_01_01_000014_add_admin_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột is_admin và banned_at vào bảng users.
     * - is_admin: phân biệt Admin và User thường
     * - banned_at: timestamp khóa tài khoản (null = không bị khóa)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm sau cột email
            $table->boolean('is_admin')->default(false)->after('email');
            $table->timestamp('banned_at')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'banned_at']);
        });
    }
};
