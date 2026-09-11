<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Comic;
use App\Services\ReaderImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ReaderE2ESeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('testing')) {
            throw new \RuntimeException('Reader E2E fixtures may only be created in testing.');
        }
        $comic = Comic::factory()->create([
            'slug' => 'reader-performance',
            'title' => 'Hành trình kiểm thử ảnh truyện trên điện thoại và máy tính',
        ]);
        foreach ([1, 2] as $number) {
            $chapter = Chapter::factory()->create([
                'comic_id' => $comic->id, 'chapter_number' => $number,
                'slug' => 'chapter-' . $number, 'title' => 'Chapter kiểm thử ' . $number,
                'pages' => [], 'processing_status' => 'ready', 'published_at' => now()->subDay(),
            ]);
            $pages = [];
            $dimensions = [];
            for ($i = 1; $i <= 14; $i++) {
                $path = "comics/{$comic->id}/chapters/{$chapter->id}/{$i}.png";
                $image = imagecreatetruecolor(1600, 2400);
                imagefill($image, 0, 0, imagecolorallocate($image, 245, 245, 245));
                $ink = imagecolorallocate($image, 30, 30, 30);
                for ($y = 60; $y < 2300; $y += 120) {
                    imagerectangle($image, 60, $y, 1540, $y + 90, $ink);
                    imagestring($image, 5, 100, $y + 30, "Reader page {$i} - chapter {$number}", $ink);
                }
                ob_start();
                imagepng($image);
                $bytes = ob_get_clean();
                imagedestroy($image);
                Storage::disk('public')->put($path, $bytes);
                $pages[] = $path;
                $dimensions[] = ['width' => 1600, 'height' => 2400];
            }
            $chapter->update(['pages' => $pages, 'page_dimensions' => $dimensions]);
            app(ReaderImageService::class)->generate($chapter);
        }
    }
}
