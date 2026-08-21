<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComicRequest extends FormRequest
{
    /**
     * Bảo vệ đã được thực hiện ở route level bởi AdminMiddleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tự động tạo slug từ title nếu không được cung cấp.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->input('slug')) && $this->input('title')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->input('title')),
            ]);
        }

        // Ép kiểu boolean cho checkbox fields (HTML form gửi "on" hoặc không có)
        $this->merge([
            'is_original' => $this->boolean('is_original'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Thông tin cơ bản
            'title'       => 'required|string|max:255|unique:comics,title',
            'slug'        => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                'unique:comics,slug',
            ],
            'description' => 'nullable|string|max:5000',
            'status'      => ['required', Rule::in(['ongoing', 'completed', 'hiatus', 'cancelled'])],
            'is_original' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date',

            // Ảnh bìa
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // Quan hệ nhiều-nhiều — validate từng phần tử riêng lẻ
            'genre_ids'   => 'required|array|min:1',
            'genre_ids.*' => 'integer|exists:genres,id',

            'tag_ids'     => 'nullable|array',
            'tag_ids.*'   => 'integer|exists:tags,id',

            'author_ids'   => 'nullable|array',
            'author_ids.*' => 'integer|exists:authors,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required'       => 'Vui lòng nhập tiêu đề bộ truyện.',
            'title.max'            => 'Tiêu đề không được vượt quá 255 ký tự.',
            'title.unique'         => 'Tiêu đề bộ truyện này đã tồn tại.',
            'slug.regex'           => 'Slug chỉ được chứa chữ thường a-z, số 0-9 và dấu gạch ngang.',
            'slug.unique'          => 'Slug này đã được sử dụng bởi bộ truyện khác.',
            'description.max'      => 'Mô tả không được vượt quá 5000 ký tự.',
            'status.required'      => 'Vui lòng chọn trạng thái bộ truyện.',
            'status.in'            => 'Trạng thái không hợp lệ. Chọn một trong: ongoing, completed, hiatus, cancelled.',
            'published_at.date'    => 'Ngày phát hành không đúng định dạng.',
            'cover_image.image'    => 'File ảnh bìa không hợp lệ.',
            'cover_image.mimes'    => 'Ảnh bìa chỉ chấp nhận định dạng: JPEG, PNG, JPG, WEBP.',
            'cover_image.max'      => 'Ảnh bìa không được lớn hơn 2MB.',
            'genre_ids.required'   => 'Vui lòng chọn ít nhất một thể loại.',
            'genre_ids.min'        => 'Vui lòng chọn ít nhất một thể loại.',
            'genre_ids.*.integer'  => 'ID thể loại không hợp lệ.',
            'genre_ids.*.exists'   => 'Thể loại #:input không tồn tại trong hệ thống.',
            'tag_ids.*.integer'    => 'ID tag không hợp lệ.',
            'tag_ids.*.exists'     => 'Tag #:input không tồn tại trong hệ thống.',
            'author_ids.*.integer' => 'ID tác giả không hợp lệ.',
            'author_ids.*.exists'  => 'Tác giả #:input không tồn tại trong hệ thống.',
        ];
    }
}
