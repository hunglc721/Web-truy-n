<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    /**
     * Authorization chuyển từ Controller vào đây.
     * CommentPolicy::create() từ chối user bị ban → 403 tự động.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Comment::class);
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
     * Validation rules — tập trung tại một nơi duy nhất.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $comicId   = $this->input('comic_id');
        $chapterId = $this->input('chapter_id');

        $maxLength = config('comments.max_length', 1000);
        $minLength = config('comments.min_length', 1);
        $maxDepth  = config('comments.max_depth', 1);

        return [
            'comic_id' => 'required|exists:comics,id',

            // chapter_id phải thuộc đúng comic_id đang gửi
            'chapter_id' => [
                'nullable',
                Rule::exists('chapters', 'id')->where('comic_id', $comicId),
            ],

            // parent_id phải:
            //   (a) thuộc cùng comic
            //   (b) thuộc cùng chapter nếu có
            //   (c) không phải là reply của reply (depth ≤ max_depth)
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($comicId, $chapterId, $maxDepth) {
                    if (is_null($value)) {
                        return;
                    }

                    $parent = Comment::find($value);

                    if (!$parent) {
                        $fail('Bình luận cha không tồn tại.');
                        return;
                    }

                    if ((int) $parent->comic_id !== (int) $comicId) {
                        $fail('Bình luận cha không thuộc truyện này.');
                        return;
                    }

                    if (!is_null($chapterId) && (int) $parent->chapter_id !== (int) $chapterId) {
                        $fail('Bình luận cha không thuộc chương này.');
                        return;
                    }

                    // Giới hạn độ sâu reply: parent phải là bình luận gốc (không có parent_id)
                    // Khi max_depth = 1: chỉ cho phép reply trực tiếp vào bình luận gốc
                    if ($maxDepth === 1 && !is_null($parent->parent_id)) {
                        $fail('Chỉ được phép phản hồi trực tiếp bình luận gốc, không thể phản hồi một phản hồi khác.');
                    }
                },
            ],

            'content' => "required|string|min:{$minLength}|max:{$maxLength}",
            '_hp_website_title' => 'nullable|max:0', // Honeypot field: must be empty
        ];
    }

    /**
     * Thông báo lỗi tiếng Việt.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxLength = config('comments.max_length', 1000);
        $minLength = config('comments.min_length', 1);

        return [
            'comic_id.required'  => 'Thiếu thông tin bộ truyện.',
            'comic_id.exists'    => 'Bộ truyện không tồn tại.',
            'chapter_id.exists'  => 'Chương không thuộc bộ truyện này.',
            'content.required'   => 'Nội dung bình luận không được để trống.',
            'content.min'        => "Bình luận phải có ít nhất {$minLength} ký tự.",
            'content.max'        => "Bình luận không được vượt quá {$maxLength} ký tự.",
        ];
    }
}
