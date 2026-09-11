# Comicx

Nền tảng đọc **Manga / Manhwa / Manhua** được xây dựng bằng **Laravel 11 + Blade**, tập trung vào Backend, quản lý nội dung, trải nghiệm đọc, cá nhân hoá và kiểm thử tự động.

> Dự án cá nhân của `hunglc721`, được phát triển theo hướng một sản phẩm thực tế thay vì chỉ dừng ở CRUD cơ bản.

## Mục tiêu dự án

Comicx được xây dựng để áp dụng các kiến thức Backend vào một hệ thống hoàn chỉnh, bao gồm:

- thiết kế và quản lý cơ sở dữ liệu;
- xử lý nghiệp vụ đọc và quản lý truyện;
- authentication, authorization và bảo mật tài khoản;
- cache, queue, scheduler và tối ưu truy vấn;
- tích hợp Backend với Blade/JavaScript;
- realtime notification;
- Unit / Feature / Browser E2E testing;
- CI tự động bằng GitHub Actions.

Toàn bộ chapter đã phát hành đều có thể đọc **miễn phí**, không sử dụng Coin, Wallet hay VIP để mở khoá nội dung.

---

## Công nghệ đã sử dụng

### Backend

- PHP 8.3
- Laravel 11
- Laravel MVC
- Eloquent ORM
- MySQL
- SQLite cho test/CI
- REST-style JSON API
- Laravel Cache
- Laravel Queue
- Laravel Scheduler
- Server-Sent Events (SSE)
- Rate Limiting

### Frontend

- Blade
- JavaScript
- HTML5
- CSS3
- AJAX / Fetch API

### Testing & DevOps

- PHPUnit / Laravel Test Suite
- Unit Test
- Feature Test
- Functional Smoke Test
- Playwright E2E
- Chromium Desktop + Mobile testing
- Git / GitHub
- GitHub Actions CI

---

## Kiến trúc hệ thống

Dự án sử dụng một ứng dụng Laravel duy nhất:

```text
Browser
  ↓
Blade + JavaScript
  ↓
Routes
  ↓
Controllers
  ↓
Services / Policies / Jobs
  ↓
Eloquent Models
  ↓
MySQL / SQLite
```

Thư mục ứng dụng chính:

```text
laravel-blade/
```

Browser E2E tests:

```text
e2e/
```

---

## Chức năng chính

### Guest

Không cần đăng nhập vẫn có thể:

- xem trang chủ và các khu vực khám phá truyện;
- xem thể loại, tag, tác giả và nhóm dịch;
- xem lịch phát hành và truyện đã hoàn thành;
- tìm kiếm truyện theo từ khoá;
- tìm kiếm tiếng Việt không dấu;
- xem chi tiết truyện và toàn bộ chapter đã phát hành;
- đọc chapter miễn phí;
- sử dụng các chế độ Reader và tuỳ chỉnh hiển thị;
- xem bình luận, đánh giá và gợi ý truyện;
- gửi báo cáo lỗi hình ảnh / DMCA / liên hệ.

### Member

Sau khi đăng nhập, Member có thêm:

- quản lý Tủ Truyện cá nhân;
- theo dõi truyện;
- hiển thị số chapter chưa đọc;
- tự xác định chapter chưa đọc tiếp theo;
- lưu lịch sử và tiến độ đọc;
- khôi phục vị trí đọc theo `% scroll`;
- tránh tụt tiến độ khi mở lại chapter cũ;
- like truyện;
- rating và viết nhận xét;
- bình luận và trả lời bình luận;
- tạo danh sách đọc riêng;
- xem thống kê đọc cá nhân;
- nhận thông báo chapter mới;
- nhận thông báo trực tiếp từ Admin;
- xem và quản lý Notification Center.

### Admin / Staff roles

Khu vực `/admin` hỗ trợ:

- Dashboard và analytics;
- quản lý truyện;
- quản lý chapter;
- upload / xử lý ảnh chapter;
- quản lý thể loại;
- quản lý tag;
- quản lý tác giả;
- quản lý banner;
- quản lý lịch phát hành;
- quản lý thành viên;
- khoá / mở khoá tài khoản;
- RBAC và phân quyền chi tiết;
- kiểm duyệt bình luận;
- xử lý báo cáo;
- xử lý yêu cầu đăng truyện;
- audit log;
- cấu hình website;
- maintenance mode;
- gửi thông báo theo đối tượng người dùng.

Toàn bộ `/admin/*` được bảo vệ bằng authentication, middleware và permission backend. Giao diện chỉ là lớp hiển thị, không được dùng thay cho authorization.

---

## Authentication & Security

Hệ thống hiện có:

- đăng ký / đăng nhập / đăng xuất;
- email verification;
- quên và đặt lại mật khẩu;
- Two-Factor Authentication (2FA);
- recovery code;
- quản lý session;
- đăng xuất các thiết bị khác;
- kiểm tra user bị ban;
- RBAC;
- permission middleware;
- validation dữ liệu;
- secure image upload;
- anti-spam / honeypot cho comment;
- rate limiting;
- anti-hotlink;
- kiểm tra URL an toàn;
- xử lý quyền truy cập Admin ở Backend.

---

## Realtime Notification

Comicx sử dụng **Server-Sent Events (SSE)** cho thông báo realtime.

Khi user đang đăng nhập:

```text
Server tạo notification
        ↓
SSE stream
        ↓
Browser nhận snapshot mới
        ↓
Badge chuông cập nhật
        ↓
Dropdown refresh + Toast xuất hiện
```

Các đặc điểm:

- không cần reload trang;
- badge notification cập nhật realtime;
- dropdown đang mở được refresh trực tiếp;
- toast hiển thị khi có notification mới;
- có heartbeat giữ kết nối;
- EventSource tự reconnect;
- fallback sang polling khoảng 15 giây nếu SSE lỗi liên tiếp;
- JSON notification API cũ vẫn được giữ để tương thích.

Realtime hiện được dùng cho các notification trong hệ thống như chapter mới và Admin broadcast.

---

## Tủ Truyện & Reading Progress

Tủ Truyện không chỉ lưu bookmark mà còn quản lý trạng thái đọc:

- `+X chưa đọc` cho từng bộ truyện;
- trạng thái `Đã đọc hết`;
- xác định chapter chưa đọc đầu tiên;
- nút **Đọc tiếp** chuyển đến chapter phù hợp;
- chapter lên lịch trong tương lai không bị tính là chưa đọc;
- Reader tự đồng bộ `last_read_chapter_id`;
- đọc lại chapter cũ không làm lùi tiến độ;
- xoá lịch sử đọc đồng thời reset tiến độ liên quan;
- dữ liệu được batch query để tránh N+1 ở trang Library.

---

## Search & Recommendation

### Search

Search hỗ trợ:

- title;
- partial / contains search;
- author;
- genre;
- country;
- lọc loại trừ genre;
- sorting;
- từ khoá tiếng Việt không dấu.

Ví dụ:

```text
thang cap
```

vẫn có thể tìm được:

```text
Tôi Thăng Cấp Một Mình
```

### Recommendation

Hệ thống recommendation sử dụng dữ liệu như:

- lịch sử đọc;
- truyện trong Library;
- genre người dùng quan tâm;
- trending data;
- similar comics;
- cache kết quả recommendation;
- invalidate cache khi hành vi đọc thay đổi.

---

## Performance

Một số kỹ thuật đã áp dụng:

1. **Counter Cache**  
   Lưu sẵn các chỉ số thường dùng trên `comics` để giảm query tổng hợp khi render giao diện.

2. **Read-layer Cache**  
   Cache các dữ liệu đọc nhiều như banner, trending, latest, genre và schedule.

3. **View Counter Buffering**  
   Lượt xem được buffer và flush theo batch thay vì update database liên tục mỗi request.

4. **Composite Indexes**  
   Tối ưu các truy vấn lọc, tìm kiếm và phân trang.

5. **Queue**  
   Xử lý các tác vụ nền như ảnh chapter và notification.

6. **N+1 Prevention**  
   Dùng eager loading / batch query cho các trang có nhiều relation như Library và Comment.

---

## Automated Testing

Dự án có nhiều tầng kiểm thử.

### Laravel tests

Chạy toàn bộ test:

```bash
cd laravel-blade
php artisan test
```

Trạng thái CI gần nhất:

```text
249 tests passed
1089 assertions
0 failed
```

Bao gồm:

- Unit Tests;
- Feature Tests;
- auth / security tests;
- Admin permission tests;
- Search tests;
- Recommendation tests;
- Reader tests;
- Library tests;
- Notification tests;
- Realtime SSE tests;
- rate limiting tests;
- regression tests.

### Critical Functional Smoke Test

CI chạy riêng các luồng quan trọng trước full suite:

```bash
php artisan test tests/Feature/CriticalUserJourneyTest.php --stop-on-failure
```

Các journey chính gồm:

- Guest khám phá → mở truyện → đọc chapter;
- Member theo dõi truyện → lưu tiến độ → tiếp tục đọc;
- Search partial / tiếng Việt không dấu;
- các trang public và authenticated quan trọng không được crash.

### Browser E2E bằng Playwright

Cài đặt:

```bash
cd e2e
npm install
npx playwright install chromium
```

Chạy:

```bash
npm test
```

CI hiện chạy Playwright trên:

- Desktop Chromium;
- Mobile Chromium.

Browser tests kiểm tra trực tiếp:

- responsive và horizontal overflow;
- mobile menu;
- comic detail;
- Reader settings;
- login bằng form thật;
- Library;
- live search;
- realtime notification giữa Member và Admin mà không reload trang.

Trạng thái CI gần nhất:

```text
8 browser tests passed
2 skipped có chủ đích theo viewport
0 failed
```

Nếu browser E2E thất bại, GitHub Actions lưu Playwright report / test result / Laravel server log để debug.

---

## CI Pipeline

GitHub Actions đang chạy pipeline:

```text
Checkout
   ↓
PHP 8.3 + Composer
   ↓
Validate Routes
   ↓
Critical Functional Smoke Test
   ↓
Full Laravel Regression Suite
   ↓
Seed E2E Database
   ↓
Start Laravel Server
   ↓
Playwright Desktop + Mobile
```

Các thay đổi code chính chỉ nên merge khi pipeline xanh.

Workflow:

```text
.github/workflows/laravel-tests.yml
```

---

## Cài đặt và chạy dự án

### 1. Cài Backend

```bash
cd laravel-blade
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Cấu hình MySQL

Ví dụ `.env`:

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

### 3. Khởi tạo database

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### 4. Chạy ứng dụng

```bash
php artisan serve
```

Mặc định:

```text
http://127.0.0.1:8000
```

### 5. Chạy Queue Worker

```bash
php artisan queue:work --queue=notifications,chapter-images,default
```

### 6. Chạy Scheduler

```bash
php artisan schedule:work
```

Scheduler được dùng cho các tác vụ định kỳ như auto publish chapter và flush view counters.

---

## Tài khoản seed

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@webcomics.com` | `12345678` |
| Member | `user@webcomics.com` | `12345678` |

Seeder còn tạo thêm Editor, Moderator và một số Member mẫu phục vụ phát triển / kiểm thử.

---

## URL chính

### Public

- `/` - Trang chủ
- `/genres` - Thể loại
- `/schedule` - Lịch phát hành
- `/schedule/completed` - Truyện hoàn thành
- `/originals` - Originals
- `/tags/{slug}` - Truyện theo tag
- `/authors/{slug}` - Trang tác giả
- `/teams` - Danh sách nhóm
- `/truyen/{slug}` - Chi tiết truyện
- `/truyen/{comicSlug}/{chapterSlug}` - Reader
- `/about` - Giới thiệu
- `/contact` - Liên hệ
- `/privacy` - Chính sách riêng tư
- `/terms` - Điều khoản
- `/sitemap.xml` - Sitemap

### Member

- `/user` - Dashboard cá nhân
- `/user/library` - Tủ Truyện
- `/user/history` - Lịch sử đọc
- `/user/likes` - Truyện yêu thích
- `/user/comments` - Bình luận của tôi
- `/user/ratings` - Đánh giá của tôi
- `/user/notifications` - Notification Center
- `/user/publishing-requests` - Yêu cầu đăng truyện

### Admin

- `/admin` - Dashboard
- `/admin/analytics` - Analytics
- `/admin/comics` - Quản lý truyện
- `/admin/chapters` - Quản lý chapter
- `/admin/genres` - Quản lý thể loại
- `/admin/tags` - Quản lý tag
- `/admin/authors` - Quản lý tác giả
- `/admin/users` - Quản lý user
- `/admin/comments` - Kiểm duyệt bình luận
- `/admin/reports` - Báo cáo
- `/admin/schedules` - Lịch phát hành
- `/admin/banners` - Banner
- `/admin/notifications` - Admin notification / broadcast
- `/admin/logs` - Audit log
- `/admin/permissions` - RBAC
- `/admin/settings` - Cấu hình hệ thống
- `/admin/story-requests` - Yêu cầu đăng truyện

---

## Một số API chính

```text
GET  /api/search/live
GET  /api/search/advanced
GET  /api/recommendations
GET  /api/comics/{comic}/chapters
GET  /api/comics/{comic}/release-meta
POST /api/reading-history
POST /api/comments
POST /api/comics/{comic}/toggle-library
POST /api/comics/{comicId}/ratings
GET  /user/notifications/header
```

`/user/notifications/header` hỗ trợ cả JSON response và SSE stream cho realtime notification.

---

## Tài liệu kỹ thuật

Xem thêm:

- `docs/ARCHITECTURE.md`
- `docs/PHASE_STATUS.md`
- `.github/workflows/laravel-tests.yml`
- `e2e/tests/core.spec.js`

---

## Repository

GitHub: `hunglc721/Web-truy-n`
