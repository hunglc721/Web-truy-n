<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChapterRequest extends FormRequest
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
            'chapter_number'  => 'required|numeric|min:0',
            'title'           => 'nullable|string|max:255',
            'is_free'         => 'nullable|boolean',
            'new_images'      => 'nullable|array',
            'new_images.*'    => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'existing_pages'  => 'nullable|array',
            'existing_pages.*'=> 'nullable|string',
            'removed_pages'   => 'nullable|array',
            'removed_pages.*' => 'nullable|string',
            'add_urls'        => 'nullable|string',
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
            'new_images.*.image'      => 'File tải lên phải là hình ảnh hợp lệ.',
            'new_images.*.mimes'      => 'Chấp nhận các định dạng: JPEG, PNG, JPG, WEBP, GIF.',
            'new_images.*.max'        => 'Kích thước mỗi ảnh tối đa là 5MB.',
        ];
    }
}
