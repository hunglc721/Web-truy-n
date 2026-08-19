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
     * Validation rules – giữ nguyên logic scoped từ CommentController,
     * nhưng tập trung tại một nơi duy nhất.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $comicId   = $this->input('comic_id');
        $chapterId = $this->input('chapter_id');

        return [
            'comic_id'   => 'required|exists:comics,id',

            // Fix #2: chapter_id phải thuộc đúng comic_id đang gửi
            'chapter_id' => [
                'nullable',
                Rule::exists('chapters', 'id')->where('comic_id', $comicId),
            ],

            // Fix #4: parent_id phải thuộc cùng comic (+ cùng chapter nếu có)
            'parent_id'  => [
                'nullable',
                function ($attribute, $value, $fail) use ($comicId, $chapterId) {
                    if (is_null($value)) return;

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
                    }
                },
            ],

            'content' => 'required|string|max:1000',
        ];
    }

    /**
     * Thông báo lỗi tiếng Việt.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comic_id.required' => 'Thiếu thông tin bộ truyện.',
            'comic_id.exists'   => 'Bộ truyện không tồn tại.',
            'chapter_id.exists' => 'Chương không thuộc bộ truyện này.',
            'content.required'  => 'Nội dung bình luận không được để trống.',
            'content.max'       => 'Bình luận không được vượt quá 1000 ký tự.',
        ];
    }
}
