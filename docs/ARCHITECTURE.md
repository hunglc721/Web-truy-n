# WebComics Architecture

## 1. Runtime Model

`laravel-blade/` là ứng dụng chính và là runtime duy nhất của hệ thống:

- **Framework**: Laravel 11 + PHP 8.3.
- **Frontend / Rendering**: Server-side Blade templates kết hợp Vanilla CSS & JavaScript (`public/js/app.js`), responsive tối ưu cho cả desktop và mobile.
- **Single Origin**: Mọi request web và API nội bộ đều chạy trên cùng một origin Laravel, sử dụng chung session, cookie CSRF và auth context.
- **Legacy Prototype**: Bản prototype HTML/JS ban đầu đã được di dời và hợp nhất 100% vào `laravel-blade/` (đã dọn dẹp khỏi source tree).

## 2. Visitor and Role Model

### Guest
Khách vãng lai có thể trải nghiệm đầy đủ các tính năng đọc mà không bắt buộc đăng nhập:
- Trang chủ (Hero Banners, Trending, Latest updates)
- Danh mục thể loại & bộ lọc đa tiêu chí (`/genres`)
- Lịch phát hành theo ngày trong tuần (`/schedule`)
- Danh mục WebComics Originals (`/originals`)
- Xem chi tiết truyện, danh sách chương (`/truyen/{slug}`)
- Trình đọc chapter mượt mà (`/truyen/{comicSlug}/{chapterSlug}`)
- Tìm kiếm tức thì & xem đánh giá / bình luận công khai

### Member
Người dùng đã đăng nhập có thêm các tương tác cá nhân:
- Gửi bình luận và trả lời (hệ thống tự động lọc từ nhạy cảm và phát hiện spam)
- Yêu thích truyện (Like) & Đánh giá sao kèm nhận xét (Rating & Review)
- Thêm / Bỏ truyện khỏi Tủ sách cá nhân (`/user/library`)
- Tự động lưu tiến độ đọc (khôi phục % vị trí cuộn trang)
- Bảng điều khiển cá nhân (`/user/dashboard`) và báo cáo thống kê đọc

### Staff & Admin (RBAC)
Được bảo vệ nghiêm ngặt bằng `AdminMiddleware` và phân quyền chi tiết (RBAC) theo vai trò:
- **Admin**: Toàn quyền quản trị hệ thống, truyện, chapter, thành viên, cài đặt.
- **Editor**: Quản lý truyện, chương, banner, lịch phát hành.
- **Moderator**: Quản lý bình luận, báo cáo vi phạm, nội dung người dùng.
- **Viewer**: Xem thống kê và nhật ký hoạt động.

## 3. High-Performance Architecture (Scale to 10k+ Comics)

```text
[ Browser / Client ]
       │
       ▼
[ Nginx / Cloudflare ]
       │
       ▼
[ Laravel 11 App ] ──► [ Multi-Tier Cache (Redis/File) ]
       │                ├── Banners (TTL 5m)
       │                ├── Trending / Latest (TTL 5-15m)
       │                ├── Genres / Schedules (TTL 15-60m)
       │                └── Anti-Spam F5 Lock (TTL 15m)
       │
       ├──► [ Async Queue Worker ]
       │      ├── ProcessChapterImages (Tối ưu ảnh & dimensions)
       │      ├── FlushViewCounters (Gộp view buffer -> 1 câu SQL CASE WHEN)
       │      └── InvalidateUserRecommendation (Debounced cache refresh)
       │
       ▼
[ Database (MySQL / SQLite) ]
       ├── Denormalized Counter Caches (likes_count, comments_count, rating_avg, rating_count, views_count)
       └── Optimized Composite Indexes (slug, [comic_id, chapter_number], [user_id, comic_id])
```

### A. Counter Cache & Model Observers
Bảng `comics` duy trì trực tiếp các chỉ số tổng hợp để trang chủ và danh sách truyện tải nhanh trong < 50ms mà không phải chạy `COUNT(*)` hay `AVG()`:
- `likes_count`: Duy trì bởi `ComicLikeObserver`.
- `comments_count`: Duy trì bởi `CommentObserver` (chỉ tính bình luận `approved`).
- `rating_avg` & `rating_count`: Duy trì bởi `RatingObserver` / `Comic::recalculateRating()`.
- `views_count`: Đồng bộ cùng `views` qua batch flush.

### B. Read-Layer Caching & Invalidation Lifecycle
- `home.banners`: Lưu cache 5 phút, invalidate khi `BannerObserver` phát hiện thêm/sửa/xoá banner.
- `home.trending`: Lưu cache 15 phút, invalidate khi có chapter mới được publish hoặc truyện cập nhật.
- `home.latest`: Lưu cache 5 phút, invalidate ngay khi chapter publish.
- `schedule.day.{0-6}` & `schedule.day_counts`: Lưu cache 15 phút, invalidate bởi `ScheduleObserver` và `ChapterObserver`.
- `all_genres`: Lưu cache 60 phút, invalidate khi quản trị viên cập nhật thể loại.

### C. Asynchronous View Buffer & Anti-Spam
1. Khi người dùng đọc một chương, hệ thống kiểm tra khoá chống spam: `view:{$chapterId}:{$userOrIp}` với TTL 15 phút.
2. Nếu hợp lệ, counter được tăng nguyên tử trong Cache Buffer (`views:buffer:chapter:{$id}` và `views:buffer:comic:{$id}`).
3. Định kỳ mỗi 5 phút, Scheduler chạy lệnh `views:flush` (job `FlushViewCounters`) để cập nhật toàn bộ views xuống CSDL trong một câu lệnh `UPDATE ... SET views = CASE ... ELSE views END` duy nhất.

## 4. Route Policy & Security Boundary

```text
PUBLIC
  GET  /
  GET  /genres
  GET  /schedule
  GET  /originals
  GET  /truyen/{slug}
  GET  /truyen/{comicSlug}/{chapterSlug}
  GET  /api/search/*
  GET  /api/recommendations
  GET  /api/comments [read-only GET]
  GET  /api/comics/{comicId}/ratings/*

AUTHENTICATED (MEMBER)
  GET  /user/dashboard
  GET  /user/library
  GET  /user/history
  GET  /user/likes
  POST /api/comments [rate-limited]
  POST /api/reading-history
  POST /api/comics/{comicId}/toggle-library
  POST /api/comics/{comicId}/toggle-like
  POST /api/comics/{comicId}/ratings

ADMIN / STAFF (auth + AdminMiddleware + permission:*)
  GET  /admin
  GET  /admin/analytics
  CRUD /admin/comics/*
  CRUD /admin/genres/*
  CRUD /admin/tags/*
  CRUD /admin/authors/*
  CRUD /admin/users/*
  CRUD /admin/comments/*
  CRUD /admin/reports/*
  CRUD /admin/schedules/*
  CRUD /admin/banners/*
  GET  /admin/logs
  CRUD /admin/permissions/*
  GET/POST /admin/settings/*
```

