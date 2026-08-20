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
     *   - Đã quá 15 phút kể từ khi đăng → 403
     *   - Admin luôn được phép
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment');
        return $this->user()->can('update', $comment);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:1000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Nội dung bình luận không được để trống.',
            'content.min'      => 'Bình luận phải có ít nhất 1 ký tự.',
            'content.max'      => 'Bình luận không được vượt quá 1000 ký tự.',
        ];
    }
}
