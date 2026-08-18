<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Library;
use App\Models\ReadingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    /**
     * Trang Tủ sách cá nhân & Lịch sử đọc của User
     * URL: /user/library
     */
    public function index()
    {
        $user = Auth::user();

        // 1. Lấy danh sách truyện đã Theo dõi (Bookmark) trong Thư viện
        $libraries = Library::with(['comic.latestChapter', 'lastReadChapter'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12, ['*'], 'library_page');

        // 2. Lấy danh sách Lịch sử đọc gần đây
        $readingHistories = ReadingHistory::with(['comic', 'chapter'])
            ->where('user_id', $user->id)
            ->orderBy('last_read_at', 'desc')
            ->take(20)
            ->get();

        return view('user.library', compact('libraries', 'readingHistories'));
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

        // Kiểm tra xem truyện đã có trong thư viện của user chưa
        $libraryItem = Library::where('user_id', $user->id)
            ->where('comic_id', $comic->id)
            ->first();

        if ($libraryItem) {
            // Đã có -> Thực hiện BỎ THEO DÕI (Xóa record)
            $libraryItem->delete();
            $isFollowed = false;
            $message = 'Đã bỏ theo dõi bộ truyện "' . $comic->title . '" khỏi tủ sách.';
        } else {
            // Chưa có -> Thực hiện THEO DÕI (Thêm record mới)
            Library::create([
                'user_id'  => $user->id,
                'comic_id' => $comic->id,
                'added_at' => now(),
            ]);
            $isFollowed = true;
            $message = 'Đã thêm bộ truyện "' . $comic->title . '" vào tủ sách cá nhân!';
        }

        // Đếm tổng số lượt theo dõi truyện
        $totalFollowers = Library::where('comic_id', $comic->id)->count();

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'status'          => 'success',
                'is_followed'     => $isFollowed,
                'message'         => $message,
                'total_followers' => $totalFollowers,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Xóa toàn bộ Lịch sử đọc của User
     * URL: DELETE /user/history/clear
     */
    public function clearHistory(Request $request)
    {
        $user = Auth::user();
        ReadingHistory::where('user_id', $user->id)->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Đã xóa toàn bộ lịch sử đọc thành công!',
            ]);
        }

        return back()->with('success', 'Đã xóa toàn bộ lịch sử đọc!');
    }
}
