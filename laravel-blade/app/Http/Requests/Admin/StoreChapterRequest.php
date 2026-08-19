<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Đã bảo vệ bởi AdminMiddleware ở route level
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chapter_number' => 'required|numeric|min:0',
            'title'          => 'nullable|string|max:255',
            'is_free'        => 'nullable|boolean',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'pages_raw'      => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'chapter_number.required' => 'Vui lòng nhập số chương.',
            'chapter_number.numeric'  => 'Số chương phải là dạng số.',
            'chapter_number.min'      => 'Số chương phải >= 0.',
            'images.*.image'          => 'File tải lên phải là hình ảnh hợp lệ.',
            'images.*.mimes'          => 'Chấp nhận các định dạng: JPEG, PNG, JPG, WEBP, GIF.',
            'images.*.max'            => 'Kích thước mỗi ảnh tối đa là 5MB.',
        ];
    }

    /**
     * Sau validation chuẩn: kiểm tra phải có ít nhất 1 ảnh hoặc 1 URL.
     * Gọi trong Controller trước khi xử lý tiếp.
     */
    public function hasContent(): bool
    {
        return $this->hasFile('images') || !empty(trim($this->input('pages_raw', '')));
    }
}
