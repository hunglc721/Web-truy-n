<?php

namespace Tests\Unit;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureImageUploadTest extends TestCase
{
    public function test_image_service_validates_real_mime_types(): void
    {
        Storage::fake('public');
        $imageService = new ImageService('public');

        // Ảnh hợp lệ
        $validFile = UploadedFile::fake()->image('test.jpg');
        $this->assertTrue($imageService->validateRealMime($validFile));

        // File giả mạo (đuôi .jpg nhưng nội dung text)
        $fakeFile = UploadedFile::fake()->create('malicious.jpg', 10, 'text/plain');
        $this->assertFalse($imageService->validateRealMime($fakeFile));
    }

    public function test_image_service_rejects_fake_image_during_upload(): void
    {
        Storage::fake('public');
        $imageService = new ImageService('public');

        $fakeFile = UploadedFile::fake()->create('virus.jpg', 10, 'text/plain');

        $this->expectException(\InvalidArgumentException::class);
        $imageService->uploadSingle($fakeFile, 'test/folder');
    }
}
