<?php

namespace Tests\Unit;

use App\Helpers\CommentFormatter;
use Tests\TestCase;

class CommentSpoilerAndMentionTest extends TestCase
{
    public function test_comment_formatter_wraps_spoiler_tags(): void
    {
        $raw = 'Nhân vật chính sẽ biến hình ở chương sau: [spoiler]Anh ấy thức tỉnh thần cấp![/spoiler] Quá hay.';
        $formatted = CommentFormatter::format($raw);

        $this->assertStringContainsString('class="spoiler-tag"', $formatted);
        $this->assertStringContainsString('Anh ấy thức tỉnh thần cấp!', $formatted);
    }

    public function test_comment_formatter_extracts_mentions(): void
    {
        $raw = 'Cảm ơn bạn @admin và @hunglc721 đã dịch bộ truyện này!';
        $mentions = CommentFormatter::extractMentions($raw);

        $this->assertContains('admin', $mentions);
        $this->assertContains('hunglc721', $mentions);
        $this->assertCount(2, $mentions);
    }
}
