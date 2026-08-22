<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bảng ví coin người dùng
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0); // Số dư coin khả dụng
            $table->unsignedBigInteger('locked_balance')->default(0); // Coin tạm khóa (nếu có)
            $table->timestamps();
        });

        // 2. Bảng Sổ Cái Giao Dịch Bất Biến (Immutable Financial Ledger)
        // Tuyệt đối không bao giờ update/delete trên bảng này
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('type', 30); // deposit, chapter_unlock, refund, vip_purchase, author_royalty
            $table->bigInteger('amount'); // Dương (+) nếu nạp/hoàn tiền, Âm (-) nếu tiêu coin
            $table->unsignedBigInteger('balance_before'); // Số dư trước giao dịch
            $table->unsignedBigInteger('balance_after');  // Số dư sau giao dịch
            $table->string('description');
            $table->string('reference_type')->nullable(); // Comic, Chapter, Subscription, Order...
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // 3. Bảng Mở khóa chương truyện (Chapter Unlocks)
        Schema::create('chapter_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->unsignedInteger('coins_paid')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'chapter_id']);
        });

        // 4. Bảng Gói Hội Viên VIP / Tắt quảng cáo
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('plan', 50); // vip_monthly, vip_quarterly, vip_yearly
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('active'); // active, expired, cancelled
            $table->timestamps();
            $table->index(['user_id', 'status', 'expires_at']);
        });

        // 5. Thêm cột giá coin và thời gian đọc sớm vào chapters
        Schema::table('chapters', function (Blueprint $table) {
            $table->unsignedInteger('coin_price')->default(0)->after('is_free');
            $table->timestamp('early_access_until')->nullable()->after('coin_price');
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn(['coin_price', 'early_access_until']);
        });

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('chapter_unlocks');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');
    }
};
