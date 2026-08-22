<?php

declare(strict_types=1);

namespace App\Helpers;

class VietnameseHelper
{
    /**
     * Chuyển đổi chuỗi tiếng Việt có dấu sang không dấu (UTF-8 safe),
     * đồng thời chuyển về chữ thường và chuẩn hoá khoảng trắng.
     *
     * Ví dụ: "Tôi Thăng Cấp Một Mình" -> "toi thang cap mot minh"
     *        "Độc Tôn Tam Giới!"     -> "doc ton tam gioi"
     */
    public static function removeAccents(?string $str): string
    {
        if ($str === null || trim($str) === '') {
            return '';
        }

        $str = mb_strtolower(trim($str), 'UTF-8');

        $unicode = [
            'a' => ['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ'],
            'e' => ['è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ'],
            'i' => ['ì', 'í', 'ị', 'ỉ', 'ĩ'],
            'o' => ['ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ'],
            'u' => ['ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ'],
            'y' => ['ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ'],
            'd' => ['đ'],
        ];

        foreach ($unicode as $nonAccent => $accents) {
            $str = str_replace($accents, $nonAccent, $str);
        }

        // Thay thế các ký tự đặc biệt bằng khoảng trắng và gom khoảng trắng thừa
        $str = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $str) ?? $str;
        $str = preg_replace('/\s+/', ' ', $str) ?? $str;

        return trim($str);
    }

    /**
     * Chuẩn hoá từ khoá tìm kiếm: bỏ dấu, bỏ ký tự đặc biệt, chống injection.
     */
    public static function normalizeSearchQuery(?string $query): string
    {
        return self::removeAccents($query);
    }
}
