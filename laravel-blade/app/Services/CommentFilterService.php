<?php

namespace App\Services;

class CommentFilterService
{
    /**
     * Bảng ánh xạ ký tự đại diện cho Tiếng Việt & Teencode
     */
    protected array $charMap = [
        'a' => '[aáàảãạăắằẳẵặâấầẩẫậ4@]',
        'b' => '[b8]',
        'c' => '[c4k]',
        'd' => '[dđ]',
        'đ' => '[dđ]',
        'e' => '[eéèẻẽẹêếềểễệ3]',
        'i' => '[iíìỉĩị1!|]',
        'o' => '[oóòỏõọôốồổỗộơớờởỡợ0]',
        'u' => '[uúùủũụưứừửữự]',
        'y' => '[yýỳỷỹỵ]',
        's' => '[s5$]',
        'g' => '[g9]',
        't' => '[t7+]',
    ];

    /**
     * Lọc và thay thế các từ cấm / từ tục tĩu (bao gồm teencode, chèn dấu đ.ị.t, l-ồ-n, f.u.c.k) thành ***
     *
     * @param string $content
     * @return string
     */
    public function filterText(string $content): string
    {
        $badWords = config('badwords.words', []);

        if (empty($badWords) || empty(trim($content))) {
            return $content;
        }

        foreach ($badWords as $word) {
            if (empty($word)) continue;

            $regexPattern = $this->buildEvasionRegex($word);

            if (!empty($regexPattern)) {
                $content = preg_replace($regexPattern, '***', $content);
            }
        }

        return $content;
    }

    /**
     * Xây dựng chuỗi Regex linh hoạt tự động bắt các chiêu trò chèn dấu ., -, _, khoảng trắng giữa các chữ
     * Ví dụ: "địt" -> /d[dđ][\s\._\-\*]*[iíìỉĩị1!|][\s\._\-\*]*[t7+]/iu
     */
    protected function buildEvasionRegex(string $word): string
    {
        $chars = mb_str_split(mb_strtolower($word, 'UTF-8'));
        $regexParts = [];

        foreach ($chars as $char) {
            if (trim($char) === '') {
                $regexParts[] = '[\s\._\-\*]+';
            } else {
                $mapped = $this->charMap[$char] ?? preg_quote($char, '/');
                $regexParts[] = $mapped;
            }
        }

        // Cho phép các ký tự phân cách rác [\s\._\-\*]* giữa từng chữ cái
        $pattern = implode('[\s\._\-\*]*', $regexParts);

        return '/' . $pattern . '/iu';
    }

    /**
     * Kiểm tra nội dung bình luận có chứa Link Spam, số điện thoại hoặc quảng cáo Zalo/Telegram không
     *
     * @param string $content
     * @return bool
     */
    public function containsSpamLink(string $content): bool
    {
        $patterns = config('badwords.spam_patterns', []);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Xử lý toàn bộ logic phân loại và làm sạch bình luận
     *
     * @param string $content
     * @return array
     */
    public function process(string $content): array
    {
        // 1. Kiểm tra Spam Link / Số điện thoại / Zalo
        if ($this->containsSpamLink($content)) {
            return [
                'content'      => $content,
                'status'       => 'spam',
                'is_spam'      => true,
                'has_bad_word' => false,
            ];
        }

        // 2. Tự động lọc từ nhạy cảm (teencode + chèn dấu)
        $cleanContent = $this->filterText($content);
        $hasBadWord   = ($cleanContent !== $content);

        return [
            'content'      => $cleanContent,
            'status'       => 'approved',
            'is_spam'      => false,
            'has_bad_word' => $hasBadWord,
        ];
    }
}
