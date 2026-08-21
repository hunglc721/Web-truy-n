<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /**
     * Tiếp nhận báo lỗi (ảnh hỏng, nội dung sai) từ độc giả.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comic_id'    => 'required|exists:comics,id',
            'chapter_id'  => [
                'required',
                Rule::exists('chapters', 'id')->where('comic_id', $request->comic_id),
            ],
            'page_number' => 'required|integer|min:1|max:500',
            'image_url'   => 'nullable|string|max:1000',
            'type'        => 'nullable|string|in:broken_image,content_error,spoiler,other',
            'description' => 'nullable|string|max:500',
        ]);

        $report = Report::create([
            'user_id'     => auth()->id(),
            'comic_id'    => $validated['comic_id'],
            'chapter_id'  => $validated['chapter_id'],
            'page_number' => $validated['page_number'],
            'image_url'   => $validated['image_url'] ?? null,
            'type'        => $validated['type'] ?? 'broken_image',
            'description' => $validated['description'] ?? 'Báo lỗi ảnh hỏng tại trang ' . $validated['page_number'],
            'status'      => 'pending',
            'ip_address'  => $request->ip(),
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => 'Đã gửi báo lỗi thành công! Ban quản trị sẽ kiểm tra và khắc phục sớm nhất.',
            'report_id' => $report->id,
        ], 201);
    }
}
