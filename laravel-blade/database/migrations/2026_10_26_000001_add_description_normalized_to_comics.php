<?php

use App\Helpers\VietnameseHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('comics', 'description_normalized')) {
            Schema::table('comics', function (Blueprint $table) {
                $table->text('description_normalized')->nullable()->after('description');
            });
        }

        // Backfill existing rows so accent-insensitive description search works
        // without waiting for each comic to be saved again.
        DB::table('comics')
            ->whereNotNull('description')
            ->select(['id', 'description'])
            ->orderBy('id')
            ->chunkById(500, function ($comics): void {
                foreach ($comics as $comic) {
                    DB::table('comics')
                        ->where('id', $comic->id)
                        ->update([
                            'description_normalized' => VietnameseHelper::removeAccents((string) $comic->description),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('comics', 'description_normalized')) {
            Schema::table('comics', function (Blueprint $table) {
                $table->dropColumn('description_normalized');
            });
        }
    }
};
