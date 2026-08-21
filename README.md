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

- `laravel-blade/`: ứng dụng chính và là runtime duy nhất.
- `prototype/`: chỉ giữ làm tài liệu tham khảo UI/UX trong quá trình migration. Không có server riêng và không phải nguồn dữ liệu/nghiệp vụ.
- Không còn `localhost:3000` hay mock API Node.js.

## Quyền truy cập

### Guest
Không cần đăng nhập để:
- xem trang chủ, thể loại, lịch ra truyện, originals;
- tìm kiếm truyện;
- xem chi tiết truyện;
- đọc chapter;
- xem bình luận, rating và gợi ý truyện.

### Member
Sau khi đăng nhập có thêm:
- bình luận và trả lời;
- like / rating;
- thêm hoặc bỏ truyện khỏi tủ sách;
- lưu lịch sử và tiến độ đọc;
- xem thống kê cá nhân.

### Admin
Admin là user có `is_admin = true`.

Ngoài toàn bộ quyền của Member, Admin được truy cập `/admin` để quản lý:
- truyện và chapter;
- thể loại, tag, tác giả;
- thành viên;
- bình luận và báo cáo;
- lịch phát hành và banner;
- analytics và audit logs;
- cấu hình website và maintenance mode.

Toàn bộ `/admin/*` được bảo vệ bằng `auth` + `AdminMiddleware`. Việc ẩn nút trên giao diện không được dùng thay cho authorization backend.

## Cài đặt

```bash
cd laravel-blade
composer install
cp .env.example .env
php artisan key:generate
```

Cấu hình database trong `.env`, ví dụ MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_truyen
DB_USERNAME=root
DB_PASSWORD=
```

Sau đó:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Ứng dụng chạy tại:

```text
http://localhost:8000
```

Đây là URL duy nhất cần chạy khi phát triển local.

## Tài khoản seed

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Member | `user@example.com` | `password` |

## Các URL chính

### Public
- `/`
- `/genres`
- `/schedule`
- `/originals`
- `/truyen/{slug}`
- `/truyen/{comicSlug}/{chapterSlug}`
- `/login`
- `/register`

### Member
- `/user/library`

### Admin
- `/admin`
- `/admin/analytics`
- `/admin/comics`
- `/admin/genres`
- `/admin/tags`
- `/admin/authors`
- `/admin/users`
- `/admin/comments`
- `/admin/reports`
- `/admin/schedules`
- `/admin/banners`
- `/admin/logs`
- `/admin/permissions`
- `/admin/settings`

## Prototype migration rule

Khi đối chiếu `prototype/` với Laravel:

1. **UI/UX prototype** là tham chiếu giao diện.
2. **Laravel** là nguồn sự thật cho auth, authorization, dữ liệu và nghiệp vụ.
3. Chức năng Laravel đã có thì giữ nguyên, chỉ chỉnh giao diện cho phù hợp prototype.
4. Không tạo route/model/controller/auth flow thứ hai chỉ vì prototype có mock tương ứng.
5. Mock data và `localStorage` không được dùng làm state nghiệp vụ của sản phẩm chạy thật.
6. Mọi request của sản phẩm phải dùng cùng origin Laravel.

Chi tiết migration xem:
- `docs/ARCHITECTURE.md`
- `docs/MIGRATION_CHECKLIST.md`
- `docs/PHASE_STATUS.md`

## Test

```bash
cd laravel-blade
php artisan test
```

Các test tập trung vào public reader access, auth boundary, AdminMiddleware, comments, settings/maintenance, analytics và các module quản trị.
