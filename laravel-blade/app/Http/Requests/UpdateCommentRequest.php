<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    /**
     * Kiểm tra quyền sửa bình luận qua CommentPolicy::update().
     * Policy tự động từ chối nếu:
     *   - User không phải chủ bình luận → 403
     *   - Đã quá edit_window_minutes kể từ khi đăng → 403
     *   - Admin luôn được phép
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment');
        return $this->user()->can('update', $comment);
    }

    /**
     * Trim nội dung trước khi validate để tránh bình luận toàn khoảng trắng.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'content' => is_string($this->input('content'))
                ? trim($this->input('content'))
                : $this->input('content'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxLength = config('comments.max_length', 1000);
        $minLength = config('comments.min_length', 1);

        return [
            'content' => "required|string|min:{$minLength}|max:{$maxLength}",
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxLength = config('comments.max_length', 1000);
        $minLength = config('comments.min_length', 1);

        return [
            'content.required' => 'Nội dung bình luận không được để trống.',
            'content.min'      => "Bình luận phải có ít nhất {$minLength} ký tự.",
            'content.max'      => "Bình luận không được vượt quá {$maxLength} ký tự.",
        ];
    }
}
