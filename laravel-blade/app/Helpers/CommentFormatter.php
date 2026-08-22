<?php

namespace App\Helpers;

use App\Models\User;

class CommentFormatter
{
    /**
     * Parse thẻ [spoiler]...[/spoiler] và @username thành HTML an toàn.
     */
    public static function format(string $content): string
    {
        // 1. Thoát HTML độc hại trước để chống XSS
        $escaped = e($content);

        // 2. Chuyển [spoiler]...[/spoiler] thành span có thể bấm để xem
        $escaped = preg_replace(
            '/\[spoiler\](.*?)\[\/spoiler\]/is',
            '<span class="spoiler-tag" onclick="this.classList.toggle(\'revealed\')" title="Bấm để xem nội dung ẩn">$1</span>',
            $escaped
        );

        // 3. Chuyển @username thành tag highlight
        $escaped = preg_replace(
            '/(?<=^|\s)@([a-zA-Z0-9_\.\p{L}]+)/u',
            '<span class="mention-tag" style="color: #60a5fa; font-weight: 700;">@$1</span>',
            $escaped
        );

        return nl2br($escaped);
    }

    /**
     * Trích xuất danh sách tên người dùng được @mention trong nội dung bình luận.
     *
     * @return array<string>
     */
    public static function extractMentions(string $content): array
    {
        preg_match_all('/(?<=^|\s)@([a-zA-Z0-9_\.\p{L}]+)/u', $content, $matches);
        return array_unique($matches[1] ?? []);
    }
}
