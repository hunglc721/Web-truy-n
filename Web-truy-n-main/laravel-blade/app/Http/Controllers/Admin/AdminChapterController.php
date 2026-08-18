<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminChapterController extends Controller
{
    /**
     * Danh sách tất cả các chapter của một bộ truyện
     */
    public function index(Comic $comic)
    {
        $chapters = $comic->chapters()
            ->orderBy('chapter_number', 'desc')
            ->paginate(20);

        return view('admin.chapters.index', compact('comic', 'chapters'));
    }

    /**
     * Giao diện Đăng tải chương mới (Bulk Image Upload Form)
     */
    public function create(Comic $comic)
    {
        $nextChapterNumber = ($comic->chapters()->max('chapter_number') ?? 0) + 1;

        return view('admin.chapters.create', compact('comic', 'nextChapterNumber'));
    }

    /**
     * Xử lý lưu chương mới với tải ảnh hàng loạt (Bulk Upload)
     */
    public function store(Request $request, Comic $comic)
    {
        $request->validate([
            'chapter_number' => 'required|numeric',
            'title'          => 'nullable|string|max:255',
            'is_free'        => 'nullable|boolean',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120', // Tối đa 5MB / ảnh
            'pages_raw'      => 'nullable|string',
        ], [
            'chapter_number.required' => 'Vui lòng nhập số chương.',
            'chapter_number.numeric'  => 'Số chương phải là dạng số.',
            'images.*.image'          => 'File tải lên phải là hình ảnh hợp lệ.',
            'images.*.mimes'          => 'Chấp nhận các định dạng: JPEG, PNG, JPG, WEBP, GIF.',
            'images.*.max'            => 'Kích thước mỗi ảnh tối đa là 5MB.',
        ]);

        // Đảm bảo phải có ít nhất 1 ảnh (file upload hoặc link URL)
        if (!$request->hasFile('images') && empty(trim($request->input('pages_raw', '')))) {
            return back()->withInput()->withErrors([
                'images' => 'Bạn phải chọn ít nhất 1 file ảnh tải lên hoặc dán danh sách đường dẫn URL ảnh.'
            ]);
        }

        DB::beginTransaction();
        try {
            // 1. Tạo bản ghi Chapter trước để lấy $chapter->id
            $chapterNumber = $request->input('chapter_number');
            $chapter = Chapter::create([
                'comic_id'       => $comic->id,
                'chapter_number' => $chapterNumber,
                'title'          => $request->input('title') ?: 'Chapter ' . $chapterNumber,
                'slug'           => 'chapter-' . Str::slug($chapterNumber),
                'pages'          => [],
                'views'          => 0,
                'published_at'   => now(),
                'is_free'        => $request->boolean('is_free', true),
            ]);

            $pages = [];
            $storageFolder = "comics/{$comic->id}/chapters/{$chapter->id}";

            // 2. Xử lý File Ảnh Upload (Bulk Upload)
            if ($request->hasFile('images')) {
                $uploadedFiles = $request->file('images');

                // Nếu frontend gửi mảng thứ tự reorder (file_indices)
                $orderIndices = $request->input('image_order');
                if (!empty($orderIndices) && is_array($orderIndices)) {
                    // Sắp xếp lại danh sách file theo thứ tự kéo thả từ giao diện
                    $orderedFiles = [];
                    foreach ($orderIndices as $index) {
                        if (isset($uploadedFiles[$index])) {
                            $orderedFiles[] = $uploadedFiles[$index];
                        }
                    }
                    if (count($orderedFiles) === count($uploadedFiles)) {
                        $uploadedFiles = $orderedFiles;
                    }
                }

                foreach ($uploadedFiles as $idx => $file) {
                    $pageNumber = str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
                    $extension = $file->getClientOriginalExtension() ?: 'jpg';
                    $filename = "page_{$pageNumber}_" . Str::random(6) . ".{$extension}";

                    // Lưu file vào storage/app/public/comics/{comic_id}/chapters/{chapter_id}/
                    $path = $file->storeAs($storageFolder, $filename, 'public');
                    $pages[] = $path;
                }
            }

            // 3. Xử lý thêm link URL nếu có nhập ở tab URL
            if (!empty(trim($request->input('pages_raw', '')))) {
                $rawUrls = array_values(array_filter(
                    array_map('trim', explode("\n", $request->input('pages_raw'))),
                    fn($url) => !empty($url)
                ));
                $pages = array_merge($pages, $rawUrls);
            }

            // 4. Cập nhật mảng JSON đường dẫn ảnh
            $chapter->update(['pages' => $pages]);

            DB::commit();

            return redirect()->route('admin.comics.chapters.index', $comic->id)
                ->with('success', "Đăng thành công Chapter {$chapterNumber} với " . count($pages) . " trang ảnh!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'error' => 'Đã xảy ra lỗi khi lưu chương: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Giao diện chỉnh sửa chương (Sửa thứ tự trang, xóa/thêm ảnh)
     */
    public function edit(Comic $comic, Chapter $chapter)
    {
        return view('admin.chapters.edit', compact('comic', 'chapter'));
    }

    /**
     * Cập nhật chương (Xóa ảnh cũ, thêm ảnh mới, thay đổi thứ tự trang)
     */
    public function update(Request $request, Comic $comic, Chapter $chapter)
    {
        $request->validate([
            'chapter_number' => 'required|numeric',
            'title'          => 'nullable|string|max:255',
            'is_free'        => 'nullable|boolean',
            'new_images'     => 'nullable|array',
            'new_images.*'   => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        DB::beginTransaction();
        try {
            $existingPages = $request->input('existing_pages', []); // Mảng các đường dẫn ảnh hiện tại giữ lại
            $removedPages  = $request->input('removed_pages', []);  // Mảng các đường dẫn ảnh bị xóa

            // 1. Xóa các file ảnh bị xóa khỏi Storage
            if (!empty($removedPages) && is_array($removedPages)) {
                foreach ($removedPages as $path) {
                    // Chỉ xóa nếu là file lưu cục bộ (bắt đầu bằng comics/)
                    if (str_starts_with($path, 'comics/') && Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }

            $finalPages = is_array($existingPages) ? array_values($existingPages) : [];

            // 2. Thêm ảnh mới upload vào danh sách
            if ($request->hasFile('new_images')) {
                $storageFolder = "comics/{$comic->id}/chapters/{$chapter->id}";
                $currentCount = count($finalPages);

                foreach ($request->file('new_images') as $idx => $file) {
                    $pageNumber = str_pad($currentCount + $idx + 1, 3, '0', STR_PAD_LEFT);
                    $extension  = $file->getClientOriginalExtension() ?: 'jpg';
                    $filename   = "page_{$pageNumber}_" . Str::random(6) . ".{$extension}";

                    $path = $file->storeAs($storageFolder, $filename, 'public');
                    $finalPages[] = $path;
                }
            }

            // 3. Nếu có nhập thêm URL từ textarea
            if (!empty(trim($request->input('add_urls', '')))) {
                $rawUrls = array_values(array_filter(
                    array_map('trim', explode("\n", $request->input('add_urls'))),
                    fn($url) => !empty($url)
                ));
                $finalPages = array_merge($finalPages, $rawUrls);
            }

            // 4. Cập nhật thông tin Chapter
            $chapterNumber = $request->input('chapter_number');
            $chapter->update([
                'chapter_number' => $chapterNumber,
                'title'          => $request->input('title') ?: 'Chapter ' . $chapterNumber,
                'slug'           => 'chapter-' . Str::slug($chapterNumber),
                'pages'          => array_values($finalPages),
                'is_free'        => $request->boolean('is_free', true),
            ]);

            DB::commit();

            return redirect()->route('admin.comics.chapters.index', $comic->id)
                ->with('success', "Cập nhật Chapter {$chapterNumber} thành công!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'error' => 'Đã xảy ra lỗi khi cập nhật chương: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Xóa chương và toàn bộ thư mục chứa ảnh của chương
     */
    public function destroy(Comic $comic, Chapter $chapter)
    {
        try {
            // Xóa thư mục chứa ảnh trong storage/app/public/comics/{comic_id}/chapters/{chapter_id}/
            $storageFolder = "comics/{$comic->id}/chapters/{$chapter->id}";
            if (Storage::disk('public')->exists($storageFolder)) {
                Storage::disk('public')->deleteDirectory($storageFolder);
            }

            // Xóa bản ghi trong CSDL
            $chapterNumber = $chapter->chapter_number;
            $chapter->forceDelete();

            return redirect()->route('admin.comics.chapters.index', $comic->id)
                ->with('success', "Đã xóa Chapter {$chapterNumber} và toàn bộ ảnh thuộc chương!");
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Lỗi khi xóa chương: ' . $e->getMessage()
            ]);
        }
    }
}
