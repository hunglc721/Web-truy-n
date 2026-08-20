<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Services\LibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    public function __construct(
        protected LibraryService $libraryService
    ) {}

    /**
     * Trang Tủ sách cá nhân & Lịch sử đọc của User
     * URL: /user/library
     */
    public function index()
    {
        $user = Auth::user();

        $libraries = $this->libraryService->getUserLibrary($user, 12);
        $readingHistories = $this->libraryService->getReadingHistory($user, 20);
        $stats = $this->libraryService->getUserReadingStats($user);

        return view('user.library', compact('libraries', 'readingHistories', 'stats'));
    }

    /**
     * API / Route AJAX xử lý nút "Theo dõi" / "Bỏ theo dõi" (Toggle Bookmark)
     * URL: POST /user/library/toggle/{comic}
     */
    public function toggle(Request $request, Comic $comic)
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status'  => 'unauthorized',
                    'message' => 'Vui lòng đăng nhập để thực hiện chức năng Theo dõi truyện.',
                    'redirect'=> route('login'),
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để theo dõi truyện!');
        }

        $result = $this->libraryService->toggle($user, $comic);

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'status'          => 'success',
                'is_followed'     => $result['is_followed'],
                'message'         => $result['message'],
                'total_followers' => $result['total_followers'],
            ]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Xóa toàn bộ Lịch sử đọc của User
     * URL: DELETE /user/history/clear
     */
    public function clearHistory(Request $request)
    {
        $user = Auth::user();
        $this->libraryService->clearUserHistory($user);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Đã xóa toàn bộ lịch sử đọc thành công!',
            ]);
        }

        return back()->with('success', 'Đã xóa toàn bộ lịch sử đọc!');
    }
}
