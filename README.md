# WebComics

Website đọc Manga / Manhwa / Manhua xây bằng **Laravel 11 + Blade**.

## Kiến trúc hiện tại

Dự án chỉ có **một ứng dụng chạy thật**:

```text
Browser
  ↓
Laravel / Blade
  ↓
Routes → Controllers → Models / Services
  ↓
MySQL hoặc SQLite
```

- `laravel-blade/`: ứng dụng chính và là runtime duy nhất của dự án.
- Giao diện được xây dựng bằng Laravel Blade kết hợp CSS/JS hiện đại, hỗ trợ caching đa tầng, queue worker xử lý ảnh/view counts và tối ưu hoá chỉ mục CSDL cho hơn 10.000+ truyện.

## Quyền truy cập

### Guest
Không cần đăng nhập để:
- xem trang chủ, thể loại, lịch ra truyện, originals;
- tìm kiếm truyện đa tiêu chí;
- xem chi tiết truyện và danh sách chương;
- đọc chapter mượt mà với thanh công cụ tuỳ biến;
- xem bình luận, rating và gợi ý truyện.

### Member
Sau khi đăng nhập có thêm:
- bình luận và trả lời (kèm kiểm duyệt từ khoá & chống spam tự động);
- like / rating (đánh giá sao và viết nhận xét);
- thêm hoặc bỏ truyện khỏi tủ sách;
- lưu lịch sử và tiến độ đọc (tự động khôi phục % scroll);
- xem thống kê cá nhân và xuất dữ liệu.

### Admin
Admin là user có `is_admin = true` hoặc được gán quyền tương ứng.

Ngoài toàn bộ quyền của Member, Admin được truy cập `/admin` để quản lý:
- truyện và chapter (xử lý ảnh nền qua queue);
- thể loại, tag, tác giả;
- thành viên và phân quyền (RBAC);
- bình luận và báo cáo vi phạm;
- lịch phát hành và banner quảng bá;
- analytics và audit logs;
- cấu hình website và maintenance mode.

Toàn bộ `/admin/*` được bảo vệ bằng `auth` + `AdminMiddleware` và phân quyền chi tiết. Việc ẩn nút trên giao diện không được dùng thay cho authorization backend.

## Cài đặt & Chạy ứng dụng

```bash
cd laravel-blade
composer install
cp .env.example .env
php artisan key:generate
```

Cấu hình database trong `.env`, ví dụ MySQL hoặc SQLite:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_truyen
DB_USERNAME=root
DB_PASSWORD=
CACHE_STORE=file
QUEUE_CONNECTION=database
```

Khởi tạo CSDL, symlink storage và chạy server:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Chạy Queue Worker (xử lý ảnh chapter, gửi notification, thống kê view):

```bash
php artisan queue:work --queue=default,chapter-images
```

Chạy Scheduler (tự động phát hành chương hẹn giờ & flush view counters):

```bash
php artisan schedule:work
```

Ứng dụng chạy tại:

```text
http://localhost:8000
```

## Tài khoản seed

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Member | `user@example.com` | `password` |

## Các URL chính

### Public
- `/` — Trang chủ (Banners, Trending, Latest updates)
- `/genres` — Bộ lọc thể loại đa tiêu chí
- `/schedule` — Lịch ra mắt truyện theo ngày trong tuần
- `/originals` — Danh mục WebComics Originals
- `/truyen/{slug}` — Chi tiết truyện
- `/truyen/{comicSlug}/{chapterSlug}` — Trình đọc chapter
- `/login` / `/register` — Xác thực người dùng

### Member
- `/user/dashboard` — Trung tâm cá nhân
- `/user/library` — Tủ sách đã lưu
- `/user/history` — Lịch sử đọc truyện
- `/user/likes` — Truyện yêu thích

### Admin
- `/admin` — Dashboard thống kê tổng quan
- `/admin/analytics` — Báo cáo phân tích chi tiết
- `/admin/comics` — Quản lý truyện & chương
- `/admin/genres` — Quản lý thể loại
- `/admin/tags` — Quản lý nhãn truyện
- `/admin/authors` — Quản lý tác giả
- `/admin/users` — Quản lý thành viên
- `/admin/comments` — Duyệt & kiểm duyệt bình luận
- `/admin/reports` — Xử lý báo cáo vi phạm
- `/admin/schedules` — Quản lý lịch phát hành
- `/admin/banners` — Quản lý banner Hero slider
- `/admin/logs` — Nhật ký hoạt động (Audit log)
- `/admin/permissions` — Phân quyền vai trò (RBAC)
- `/admin/settings` — Cấu hình hệ thống & Chế độ bảo trì

## Kiến trúc & Tối ưu hoá

1. **Counter Cache**: Duy trì các trường `likes_count`, `comments_count`, `rating_avg`, `rating_count`, `views_count` trực tiếp trên bảng `comics` thông qua Model Observers, loại bỏ hoàn toàn các câu query `COUNT(*)` / `AVG()` nặng khi tải trang.
2. **Read-layer Caching**: Áp dụng `Cache::remember` cho Banners (5m), Trending (15m), Latest (5m), Genres (60m) và Lịch ra truyện (15m). Tự động invalidate tức thì khi dữ liệu thay đổi.
3. **Async View Buffering & Anti-Spam**: Lượt đọc chương được buffer vào Cache/Redis kèm khoá chống spam theo IP/User trong 15 phút, sau đó flush theo batch định kỳ xuống database qua 1 câu SQL `CASE WHEN` duy nhất.
4. **Composite Indexes**: Tối ưu hoá toàn bộ các truy vấn lọc, tìm kiếm và phân trang cho dữ liệu quy mô 10.000+ truyện.

Chi tiết xem tại:
- `docs/ARCHITECTURE.md`
- `docs/PHASE_STATUS.md`

## Kiểm thử (Tests)

```bash
cd laravel-blade
php artisan test
```

Bộ test bao gồm unit & feature tests kiểm tra toàn bộ luồng public reading, auth boundary, RBAC, AdminMiddleware, comments moderation, settings, rating services và analytics.
