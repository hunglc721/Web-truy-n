<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');
        if ($task instanceof \App\Models\UploadTask) {
            return $this->user()?->isAdmin() && $task->user_id === $this->user()->id;
        }
        // Đã bảo vệ bởi AdminMiddleware + permission:chapters.create ở route level.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->filled('bulk_action')) {
            return $this->bulkRules((string) $this->input('bulk_action'));
        }

        return [
            'chapter_number' => ['required', 'numeric', 'min:0', 'max:9999999999', 'regex:/^\d+(?:\.\d+)?$/'],
            'title'          => 'nullable|string|max:255',
            'is_free'        => 'nullable|boolean',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'zip_file'       => 'nullable|file|mimes:zip|max:102400',
            'pages_raw'      => 'nullable|string',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bulkRules(string $action): array
    {
        $base = [
            'bulk_action' => 'required|string|in:start,chunk,finalize,complete,check_existing',
        ];

        return match ($action) {
            'start' => $base,
            'check_existing' => $base + [
                'chapter_numbers'   => 'required|array|min:1|max:500',
                'chapter_numbers.*' => ['required', 'string', 'max:32', 'regex:/^\d+(?:\.\d+)?$/'],
            ],
            'chunk' => $base + [
                'session' => 'required|uuid',
                'chapter_key' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
                'files' => 'required|array|min:1|max:20',
                'files.*' => 'required|file|max:20480',
                'page_indexes' => 'required|array|min:1|max:20',
                'page_indexes.*' => 'required|integer|min:0|max:1999',
                'checksums' => 'required|array|min:1|max:20',
                'checksums.*' => ['required', 'string', 'regex:/^[a-fA-F0-9]{64}$/'],
            ],
            'finalize' => $base + [
                'session' => 'required|uuid',
                'chapter_key' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
                'chapter_number' => ['required', 'numeric', 'min:0', 'max:9999999999', 'regex:/^\d+(?:\.\d+)?$/'],
                'title' => 'nullable|string|max:255',
                'page_count' => 'required|integer|min:1|max:2000',
            ],
            'complete' => $base + [
                'session' => 'required|uuid',
            ],
            default => $base,
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'chapter_number.required' => 'Vui lòng nhập số chương.',
            'chapter_number.numeric'  => 'Số chương phải là dạng số.',
            'chapter_number.regex'    => 'Số chương chỉ chấp nhận định dạng số nguyên hoặc số thập phân hợp lệ (ví dụ: 140 hoặc 187.5).',
            'chapter_number.min'      => 'Số chương phải >= 0.',
            'chapter_number.max'      => 'Số chương không được vượt quá 9999999999.',
            'images.*.image'          => 'File tải lên phải là hình ảnh hợp lệ.',
            'images.*.mimes'          => 'Chấp nhận các định dạng: JPEG, PNG, JPG, WEBP, GIF.',
            'images.*.max'            => 'Kích thước mỗi ảnh tối đa là 5MB.',
            'zip_file.mimes'          => 'File nén phải có định dạng .ZIP.',
            'zip_file.max'            => 'Kích thước file ZIP tối đa là 100MB.',
            'files.*.max'             => 'Mỗi ảnh trong folder upload tối đa 20MB.',
            'files.max'               => 'Mỗi batch chỉ gửi tối đa 20 ảnh để tránh vượt giới hạn request.',
            'page_count.max'          => 'Mỗi chapter tối đa 2.000 trang.',
            'checksums.*.regex'       => 'Checksum SHA-256 không hợp lệ.',
        ];
    }

    /**
     * Sau validation chuẩn: kiểm tra phải có ít nhất 1 ảnh, 1 file ZIP hoặc 1 URL.
     * Gọi trong Controller trước khi xử lý tiếp.
     */
    public function hasContent(): bool
    {
        return $this->hasFile('images') || $this->hasFile('zip_file') || !empty(trim($this->input('pages_raw', '')));
    }
}
