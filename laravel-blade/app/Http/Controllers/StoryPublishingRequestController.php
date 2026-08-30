<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Genre;
use App\Models\StoryPublishingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoryPublishingRequestController extends Controller
{
    /**
     * Hiển thị form đăng ký gửi thông tin người đăng & truyện.
     */
    public function create()
    {
        $genres = Genre::orderBy('name')->get();
        $user = Auth::user();

        return view('publish.create', compact('genres', 'user'));
    }

    /**
     * Xử lý tiếp nhận đơn đăng ký gửi truyện.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'creator_name'         => 'required|string|max:150',
            'email'                => 'required|email|max:150',
            'phone_or_social'      => 'required|string|max:150',
            'team_name'            => 'nullable|string|max:150',
            'experience_level'     => 'required|string|in:beginner,experienced,professional,group',
            'story_title'          => 'required|string|max:200',
            'story_original_title' => 'nullable|string|max:200',
            'story_type'           => 'required|string|in:translation,original,novel',
            'genres'               => 'nullable|array',
            'genres.*'             => 'string|max:50',
            'story_status'         => 'required|string|in:ongoing,completed,translating',
            'summary'              => 'required|string|min:20|max:3000',
            'sample_link'          => 'nullable|url|max:500',
            'cover_image'          => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'sample_file'          => 'nullable|file|mimes:zip,rar,pdf,doc,docx,txt|max:20480',
            'note'                 => 'nullable|string|max:1000',
            'terms_agreed'         => 'accepted',
        ], [
            'creator_name.required'     => 'Vui lòng nhập họ tên tác giả hoặc người đại diện.',
            'email.required'            => 'Vui lòng cung cấp email liên hệ chính xác.',
            'email.email'               => 'Địa chỉ email không đúng định dạng.',
            'phone_or_social.required'  => 'Vui lòng nhập số điện thoại hoặc Zalo/Telegram/Facebook liên hệ.',
            'experience_level.required' => 'Vui lòng chọn mức độ kinh nghiệm sáng tác/dịch thuật.',
            'story_title.required'      => 'Vui lòng nhập tên tác phẩm dự kiến đăng tải.',
            'story_type.required'       => 'Vui lòng chọn loại hình tác phẩm.',
            'story_status.required'     => 'Vui lòng chọn tình trạng bản thảo.',
            'summary.required'          => 'Vui lòng tóm tắt nội dung cốt truyện.',
            'summary.min'               => 'Tóm tắt nội dung cốt truyện phải có ít nhất 20 ký tự.',
            'cover_image.image'         => 'File ảnh bìa mẫu phải là hình ảnh hợp lệ (JPG, PNG, WEBP).',
            'cover_image.max'           => 'Dung lượng ảnh bìa không được vượt quá 5MB.',
            'sample_file.max'           => 'Dung lượng file bản thảo đính kèm không được vượt quá 20MB.',
            'sample_file.mimes'         => 'File đính kèm chỉ chấp nhận định dạng: zip, rar, pdf, doc, docx, txt.',
            'terms_agreed.accepted'     => 'Bạn cần đồng ý với điều khoản đăng truyện và bản quyền nội dung.',
        ]);

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('story_requests/covers', 'public');
        }

        $sampleFilePath = null;
        if ($request->hasFile('sample_file')) {
            $sampleFilePath = $request->file('sample_file')->store('story_requests/samples', 'public');
        }

        $storyRequest = StoryPublishingRequest::create([
            'user_id'              => Auth::id(),
            'creator_name'         => $validated['creator_name'],
            'email'                => $validated['email'],
            'phone_or_social'      => $validated['phone_or_social'],
            'team_name'            => $validated['team_name'] ?? null,
            'experience_level'     => $validated['experience_level'],
            'story_title'          => $validated['story_title'],
            'story_original_title' => $validated['story_original_title'] ?? null,
            'story_type'           => $validated['story_type'],
            'genres'               => $validated['genres'] ?? [],
            'story_status'         => $validated['story_status'],
            'summary'              => $validated['summary'],
            'sample_link'          => $validated['sample_link'] ?? null,
            'cover_image_path'     => $coverImagePath,
            'sample_file_path'     => $sampleFilePath,
            'note'                 => $validated['note'] ?? null,
            'status'               => 'pending',
            'ip_address'           => $request->ip(),
        ]);

        if (Auth::check()) {
            ActivityLog::record('story_request.created', $storyRequest, [
                'user_id'     => Auth::id(),
                'story_title' => $storyRequest->story_title,
                'story_type'  => $storyRequest->story_type,
            ]);
        }

        $successMsg = 'Đơn đăng ký đăng truyện "' . $storyRequest->story_title . '" đã được gửi thành công! Ban Quản Trị sẽ thẩm định và phản hồi qua email hoặc thông báo tài khoản trong vòng 24–48h.';

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $successMsg,
                'data'    => $storyRequest,
            ]);
        }

        if (Auth::check()) {
            return redirect()->route('user.publishingRequests')->with('success', $successMsg);
        }

        return redirect()->route('publish.create')->with('success', $successMsg);
    }

    /**
     * Danh sách đơn đăng ký của tài khoản người dùng hiện tại.
     */
    public function myRequests()
    {
        $requests = StoryPublishingRequest::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('user.publishing_requests', compact('requests'));
    }
}
