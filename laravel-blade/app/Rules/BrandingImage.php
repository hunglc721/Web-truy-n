<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class BrandingImage implements ValidationRule
{
    public function __construct(private readonly bool $favicon = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail('Ảnh tải lên không hợp lệ.');
            return;
        }

        $mime = $value->getMimeType();
        $allowed = $this->favicon ? ['image/png', 'image/webp'] : ['image/png', 'image/jpeg', 'image/webp'];
        if (in_array($mime, $allowed, true) && @getimagesize($value->getRealPath()) !== false) {
            return;
        }

        if ($this->favicon && in_array($mime, ['image/x-icon', 'image/vnd.microsoft.icon'], true) && $this->validIcon($value)) {
            return;
        }

        $fail('Ảnh không đúng định dạng hoặc bị hỏng. Không hỗ trợ SVG.');
    }

    private function validIcon(UploadedFile $file): bool
    {
        // ICO has a six-byte header followed by bounded image directory entries.
        $bytes = file_get_contents($file->getRealPath());
        $length = strlen($bytes);
        if ($length < 22 || substr($bytes, 0, 4) !== "\x00\x00\x01\x00") {
            return false;
        }
        $count = unpack('v', substr($bytes, 4, 2))[1];
        $directoryEnd = 6 + $count * 16;
        if ($count === 0 || $directoryEnd > $length) {
            return false;
        }
        for ($index = 0; $index < $count; $index++) {
            $entry = unpack('Vsize/Voffset', substr($bytes, 6 + $index * 16 + 8, 8));
            if ($entry['offset'] < $directoryEnd || $entry['size'] < 8 || $entry['offset'] + $entry['size'] > $length) {
                return false;
            }
            $image = substr($bytes, $entry['offset'], $entry['size']);
            $png = str_starts_with($image, "\x89PNG\r\n\x1a\n") && @getimagesizefromstring($image) !== false;
            $dib = strlen($image) >= 40 && unpack('V', substr($image, 0, 4))[1] === 40;
            if (!$png && !$dib) {
                return false;
            }
        }

        return true;
    }
}
