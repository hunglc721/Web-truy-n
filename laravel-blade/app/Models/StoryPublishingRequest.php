<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryPublishingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'creator_name',
        'email',
        'phone_or_social',
        'team_name',
        'experience_level',
        'story_title',
        'story_original_title',
        'story_type',
        'genres',
        'story_status',
        'summary',
        'sample_link',
        'cover_image_path',
        'sample_file_path',
        'note',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'ip_address',
    ];

    protected $casts = [
        'genres'      => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Tài khoản người dùng nộp đơn (nếu có đăng nhập).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Admin/biên tập viên đã thẩm định đơn.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* Scopes */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewing($query)
    {
        return $query->where('status', 'reviewing');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /* Helper Badges and Labels */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Chờ duyệt',
            'reviewing' => 'Đang thẩm định',
            'approved'  => 'Đã phê duyệt',
            'rejected'  => 'Từ chối',
            default     => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'badge-warning',
            'reviewing' => 'badge-info',
            'approved'  => 'badge-success',
            'rejected'  => 'badge-danger',
            default     => 'badge-muted',
        };
    }

    public function getStoryTypeLabelAttribute(): string
    {
        return match ($this->story_type) {
            'original'    => 'Truyện Sáng Tác (Original)',
            'translation' => 'Truyện Dịch (Manga/Manhwa/Manhua)',
            'novel'       => 'Tiểu Thuyết / Truyện Chữ',
            default       => ucfirst($this->story_type),
        };
    }

    public function getExperienceLabelAttribute(): string
    {
        return match ($this->experience_level) {
            'beginner'     => 'Mới bắt đầu / Chưa có kinh nghiệm',
            'experienced'  => 'Đã có tác phẩm / Kinh nghiệm tự do',
            'professional' => 'Tác giả / Họa sĩ chuyên nghiệp',
            'group'        => 'Đại diện Nhóm dịch / Studio',
            default        => ucfirst($this->experience_level),
        };
    }

    public function getStoryStatusLabelAttribute(): string
    {
        return match ($this->story_status) {
            'ongoing'     => 'Đang sáng tác / phát hành',
            'completed'   => 'Đã hoàn thành toàn bộ',
            'translating' => 'Đang tiến hành dịch',
            default       => ucfirst($this->story_status),
        };
    }
}
