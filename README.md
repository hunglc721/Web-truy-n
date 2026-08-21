# 📚 WebComics - Nền Tảng Đọc Truyện Tranh & Quản Trị Manga/Webtoon

> Dự án website đọc truyện tranh trực tuyến hiện đại (Laravel 11 & Blade Templates) kết hợp bộ Prototype giao diện mẫu (HTML/CSS/JS).

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![Laravel Framework](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![Node.js Prototype](https://img.shields.io/badge/Prototype-Mockup%20UI-339933?style=flat&logo=nodedotjs&logoColor=white)](https://nodejs.org)
[![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20SQLite-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

---

## 📑 Mục Lục
- [1. Hướng Dẫn Khởi Động Dự Án](#-1-hướng-dẫn-khởi-động-dự-án)
  - [Cách 1: Chạy Bản Prototype (Mockup UI tĩnh, không có Database)](#cách-1-chạy-bản-prototype-mockup-ui-tĩnh-không-có-database)
  - [Cách 2: Chạy Bản Laravel 11 (Fullstack Backend Production-Ready)](#cách-2-chạy-bản-laravel-11-fullstack-backend-production-ready)
- [2. Mục Đích & Kiến Trúc Dự Án](#-2-mục-đích--kiến-trúc-dự-án)
- [3. Tình Trạng Tính Năng Chi Tiết](#-3-tình-trạng-tính-năng-chi-tiết)
  - [3.1 Dành Cho Độc Giả (Client / Reader)](#31-dành-cho-độc-giả-client--reader)
  - [3.2 Dành Cho Quản Trị Viên (Admin Dashboard)](#32-dành-cho-quản-trị-viên-admin-dashboard)
- [4. Cấu Trúc Thư Mục](#-4-cấu-trúc-thư-mục)
- [5. Hướng Dẫn Sử Dụng Chi Tiết](#-5-hướng-dẫn-sử-dụng-chi-tiết)
- [6. Công Nghệ & Bảo Mật](#-6-công-nghệ--bảo-mật)

---

## 🚀 1. Hướng Dẫn Khởi Động Dự Án

Dự án gồm 2 phần riêng biệt, bạn có thể lựa chọn khởi chạy tùy theo nhu cầu:

### Cách 1: Chạy Bản Prototype (Mockup UI tĩnh, không có Database)
> ⚠️ **Lưu ý:** Thư mục `prototype/` là **bản vẽ giao diện (Mockup UI)** được viết bằng HTML5/CSS3/Vanilla JS thuần, dữ liệu demo lưu tại `localStorage` trình duyệt. Đây **không phải sản phẩm chạy thật**, dùng để kiểm thử nhanh trải nghiệm UI/UX.

* **Yêu cầu:** Máy tính đã cài [Node.js](https://nodejs.org/) (phiên bản 18+).

```bash
# 1. Mở Terminal tại thư mục gốc của dự án (Web truyện)
# 2. Khởi chạy máy chủ phục vụ file tĩnh:
node server.js
```
* 🌐 **Địa chỉ trang chủ prototype:** [http://localhost:3000](http://localhost:3000)
* ⚙️ **Bản vẽ quản trị prototype:** [http://localhost:3000/admin.html](http://localhost:3000/admin.html)
* 🏷️ **Bản vẽ thể loại:** [http://localhost:3000/genres.html](http://localhost:3000/genres.html)
* 📅 **Bản vẽ lịch phát sóng:** [http://localhost:3000/schedule.html](http://localhost:3000/schedule.html)

---

### Cách 2: Chạy Bản Laravel 11 (Fullstack Backend Production-Ready)
> ✅ **Đây là sản phẩm hoàn chỉnh**: Hoạt động với cơ sở dữ liệu thật (MySQL/SQLite), xử lý logic nghiệp vụ, phân quyền tài khoản, thuật toán gợi ý, kiểm duyệt nội dung, bảo mật và lưu trữ file thật.

* **Yêu cầu:** 
  - PHP ≥ 8.2 (extensions: `pdo`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`)
  - [Composer](https://getcomposer.org/)
  - Cơ sở dữ liệu: MySQL 8.x hoặc SQLite

**Các bước cài đặt chi tiết:**

```bash
# Bước 1: Di chuyển vào thư mục backend Laravel
cd laravel-blade

# Bước 2: Cài đặt các thư viện PHP
composer install

# Bước 3: Tạo file cấu hình môi trường (.env)
cp .env.example .env

# Bước 4: Tạo App Key
php artisan key:generate
```

**Bước 5: Cấu hình cơ sở dữ liệu trong file `.env`**
- Nếu dùng **MySQL**:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=web_truyen
  DB_USERNAME=root
  DB_PASSWORD=
  ```
- Hoặc dùng **SQLite** (nhanh chóng, không cần cài MySQL Server):
  ```env
  DB_CONNECTION=sqlite
  ```

**Bước 6: Khởi tạo bảng CSDL & nạp dữ liệu mẫu**
```bash
php artisan migrate:fresh --seed
```

**Bước 7: Tạo liên kết thư mục tải ảnh (Symlink)**
```bash
php artisan storage:link
```

**Bước 8: Khởi chạy máy chủ phát triển**
```bash
php artisan serve
```
* 🌐 **Địa chỉ trang web:** [http://localhost:8000](http://localhost:8000)

#### 🔑 Tài khoản đăng nhập mẫu sẵn có sau khi seed:
| Vai trò | Email đăng nhập | Mật khẩu mặc định | Quyền hạn thực tế |
|:---|:---|:---|:---|
| **Quản trị viên (Admin)** | `admin@example.com` | `password` | Truy cập trang quản trị `/admin`, quản lý truyện, chapter, danh mục, thành viên |
| **Độc giả (Member)** | `user@example.com` | `password` | Đọc truyện, đánh giá, lưu tủ sách, bình luận |

---

## 🌟 2. Mục Đích & Kiến Trúc Dự Án

**WebComics** giải quyết toàn diện bài toán đọc và quản trị truyện tranh trực tuyến (Manga, Manhwa, Webtoon):
1. **Trải nghiệm đọc tối ưu:** Đọc cuộn dọc mượt mà, tự động lưu lịch sử đọc, đánh dấu yêu thích và bình luận tương tác.
2. **Quản trị xuất bản:** Đăng tải truyện, tải ảnh bulk upload cho chapter, gắn thẻ/thể loại/tác giả, hẹn giờ xuất bản chapter (Publish Gate).
3. **Cơ chế đề xuất thông minh:** Thuật toán Multi-Signal Content Filtering gợi ý truyện theo thể loại và nhãn dựa trên lịch sử đọc 30 ngày và tủ sách cá nhân.
4. **An toàn & Kiểm duyệt:** Lọc từ cấm teencode tự động, chặn spam link/quảng cáo, chặn người dùng bị khóa tài khoản theo thời gian thực (Middleware Ban Check).

---

## ✨ 3. Tình Trạng Tính Năng Chi Tiết

### 3.1 Dành Cho Độc Giả (Client / Reader) — *Đã hoàn thiện & chạy thật 100%*
* 🏠 **Trang Chủ Hiện Đại:** Hero Banner, bảng xếp hạng Top Trending (Rank 1, 2, 3) và danh sách truyện mới cập nhật (tự động lọc chỉ hiển thị chương đã phát hành).
* 📖 **Trình Đọc Chương Chuyên Nghiệp (Webtoon Reader):**
  * Chế độ cuộn dọc liên tục mượt mà, tối ưu desktop & mobile.
  * Điều hướng thông minh: Chuyển chương Trước/Sau, chọn chương từ danh sách dropdown.
  * **Hẹn giờ phát hành (Publish Gate):** Chương chưa tới giờ phát hành sẽ trả về 404 đối với độc giả, Admin vẫn xem trước (preview) bình thường.
  * Tự động lưu tiến độ đọc vào Lịch sử đọc qua AJAX khi cuộn trang.
* 🔍 **Tìm Kiếm & Bộ Lọc Nâng Cao:**
  * Lọc kết hợp đa thể loại (Action, Romance, Fantasy, Isekai,...).
  * Lọc theo trạng thái (*Đang phát hành, Đã hoàn thành, Tạm ngưng, Đã hủy*) và sắp xếp (*Xem nhiều nhất, Đánh giá cao nhất, Mới nhất*).
* 📅 **Lịch Phát Sóng (Schedule):** Xem danh sách truyện ra mắt theo từng thứ trong tuần.
* ⭐ **Độc Quyền (Originals):** Danh mục riêng dành cho các tác phẩm Webtoon bản quyền.
* 📚 **Tủ Sách & Lịch Sử Cá Nhân (Personal Library):**
  * Thêm/bỏ theo dõi truyện vào tủ sách cá nhân (AJAX Toggle).
  * Xem danh sách lịch sử đọc chi tiết, hỗ trợ nút xóa sạch lịch sử.
* 💬 **Bình Luận & Tương Tác (Comments Engine):**
  * Đăng bình luận theo truyện hoặc theo từng chương cụ thể.
  * Hỗ trợ trả lời (Reply 1 cấp) với validation kiểm tra cùng truyện/chương.
  * **Tự động lọc nội dung:** Thay thế từ cấm/teencode thành `***`, gắn cờ `spam` và chuyển vào danh sách chờ duyệt nếu chứa liên kết ngoài/quảng cáo.
  * Chỉnh sửa bình luận trong vòng 15 phút (CommentPolicy), xóa mềm bình luận (SoftDeletes).
  * Chống spam: Giới hạn tần suất đăng (Rate limit 5 req/phút).
* 🎯 **Hệ Thống Gợi Ý Thông Minh (Recommendation API):**
  * Khách vãng lai (Guest): Gợi ý Top Trending & Đánh giá cao.
  * Đã đăng nhập: Gợi ý cá nhân hóa dựa trên Thể loại (Genre) + Nhãn (Tag) đã đọc và lưu tủ sách.
  * Trang chi tiết truyện: Gợi ý các bộ truyện tương tự (Similar Comics).
  * Cơ chế bộ nhớ đệm đa tầng (Version-Key Cache Invalidation) tự động làm mới khi có hành vi đọc mới.
* 🌓 **Giao Diện Đa Dụng (Dark/Light Mode):** Chuyển đổi tông màu trực tiếp trên giao diện.

---

### 3.2 Dành Cho Quản Trị Viên (Admin Dashboard)

#### ✅ Các tính năng đã hoàn thiện & chạy thật (Backend + CSDL đầy đủ):
* 📚 **Quản Lý Truyện (Comics Management):**
  * Xem danh sách phân trang, tìm kiếm bộ truyện.
  * Thêm mới & Chỉnh sửa truyện qua `StoreComicRequest` / `UpdateComicRequest`.
  * Upload ảnh bìa lưu trữ an toàn, tự động tạo slug chuẩn SEO.
  * Đồng bộ thể loại (Genres), nhãn (Tags), tác giả & họa sĩ (Authors).
  * Thiết lập trạng thái: *Đang phát hành (ongoing), Hoàn thành (completed), Tạm ngưng (hiatus), Đã hủy (cancelled)*.
  * Xóa mềm truyện (SoftDeletes) kèm ghi nhận nhật ký (ActivityLog).
* 📑 **Quản Lý Chương (Chapters Management):**
  * Quản lý danh sách chương theo từng bộ truyện (Route scope bindings chống can thiệp chéo).
  * Thêm chương mới, tải lên danh sách ảnh hàng loạt (Bulk Upload) hoặc nhập link ảnh.
  * Đặt lịch phát hành chương (`published_at`) trong tương lai để hẹn giờ ra mắt.
  * Ràng buộc Unique Composite Index (`comic_id`, `slug`) và (`comic_id`, `chapter_number`) chống trùng lặp dữ liệu ở tầng CSDL.
* 🏷️ **Quản Lý Thể Loại (Genres Management):** Thêm mới, sửa, xóa các thể loại truyện.
* 🔖 **Quản Lý Nhãn (Tags Management):** Thêm mới, sửa, xóa các tag (HOT, NEW, 18+,...).
* ✍️ **Quản Lý Tác Giả (Authors Management):** Thêm mới, sửa, xóa thông tin tác giả/họa sĩ.
* 👥 **Quản Lý Thành Viên (Users Management):**
  * Danh sách thành viên, lọc theo vai trò (Admin / Member) và trạng thái (Bình thường / Bị khóa).
  * Phân quyền Quản trị viên (`toggleRole`) — có cơ chế bảo vệ ngăn tự tước quyền của chính mình.
  * Khóa / Mở khóa tài khoản (`toggleBan`) — ngăn khóa Admin, tự động hủy toàn bộ session của tài khoản bị khóa trong CSDL.
  * **Middleware `EnsureUserNotBanned`:** Lập tức phát hiện cờ ban, đăng xuất và đẩy người dùng ra trang đăng nhập ngay tại request kế tiếp.
* 💬 **Kiểm Duyệt & Quản Lý Bình Luận (Comments Moderation):**
  * Quản lý toàn bộ bình luận với các tab trạng thái: *Tất cả, Đã duyệt (Approved), Chờ duyệt (Pending), Đã ẩn (Hidden/Spam), Bị báo cáo (Reported), Thùng rác (Soft-deleted)*.
  * Tìm kiếm theo tên người dùng, email, nội dung bình luận hoặc tên truyện.
  * Thao tác trực tiếp: Duyệt bình luận (`approve`), Ẩn bình luận (`hide`), Xóa mềm (`SoftDeletes`), Khôi phục từ thùng rác (`restore`).
  * Khóa tài khoản nhanh (`banUser`) tác giả bình luận vi phạm trực tiếp từ bảng kiểm duyệt.
* ⚠️ **Trung Tâm Xử Lý Báo Cáo Sự Cố (Report Center):**
  * Tiếp nhận báo cáo sự cố ảnh hỏng từ độc giả (FE-03) theo thời gian thực.
  * Quản lý trạng thái theo luồng 3 bước: *Chưa xử lý (Pending) $\to$ Đang xử lý (Processing) $\to$ Đã khắc phục (Resolved)* hoặc Bác bỏ (Dismissed).
  * Nút điều hướng nhanh (`🎯 Xem trang X`): Mở trực tiếp reader và cuộn chính xác tới trang ảnh bị sự cố.
* 📅 **Quản Lý Lịch Phát Sóng Tuần (Weekly Schedule Management):**
  * Giao diện lịch phát hành 7 ngày trong tuần trực quan (Chủ Nhật - Thứ Bảy).
  * Gán bộ truyện, ngày phát sóng trong tuần (`day_of_week`) và khung giờ phát hành (`release_time`).
  * **Cơ chế tự động phát hành (Publish-Gate & Scheduled Job):** Command `chapters:publish-scheduled` chạy mỗi phút quét các chapter đã đến giờ hẹn (`published_at <= now()`), tự động làm mới cache trang chủ (`home.latest`, `home.trending`) và lịch phát sóng mà không cần thao tác tay.
* 🖼️ **Quản Lý Banner Trang Chủ (Homepage Hero Banners):**
  * CRUD banner quảng cáo/hero slider trang chủ hoàn chỉnh, hỗ trợ upload file ảnh hoặc nhập URL ảnh ngoài.
  * Tùy chỉnh thứ tự ưu tiên (`order`), bật/tắt nhanh (`toggleActive`), gán link đích và thời hạn hiệu lực (`start_at` $\to$ `end_at`).
  * **Tự động kích hoạt & Tự động ẩn khi hết hạn:** Banner hết hạn (`end_at < now()`) hoặc chưa tới giờ bắt đầu (`start_at > now()`) tự động ẩn khỏi trang chủ mà không cần xóa tay.
* 📊 **Dashboard Thống Kê Tổng Quan (Realtime Admin Dashboard):**
  * Trang tổng quan trung tâm tại `/admin`, thống kê 6 chỉ số quan trọng (Tổng truyện, Chapter, Thành viên, Lượt đọc, Bình luận chờ duyệt, Báo cáo sự cố).
  * Điều hướng nhanh tới các khu vực quản trị, bảng Top truyện xem nhiều nhất và Feed hoạt động quản trị gần đây (Activity Logs).
* 📜 **Giao Diện Tra Cứu Nhật Ký Hoạt Động (Audit Logs Center):**
  * Bảng tra cứu toàn diện tại `/admin/logs`, hỗ trợ lọc theo từ khóa, nhóm hành động (`admin.comic`, `admin.comment`, `admin.report`, `admin.schedule`, `admin.banner`, `admin.user`, `auth`), theo tài khoản quản trị và khoảng thời gian.
  * Hiển thị chi tiết payload JSON, địa chỉ IP và phân loại badge màu trực quan theo từng loại thao tác.
  * Hỗ trợ dọn dẹp nhật ký cũ (30 ngày, 60 ngày, 90 ngày hoặc toàn bộ) an toàn.

#### 🚧 Các module giao diện mẫu (Đang trong lộ trình phát triển / Hiện là View tĩnh):
> ℹ️ *Các route dưới đây đã được định tuyến trong `routes/web.php` và trả về giao diện Blade mẫu để tham khảo UI, chưa gắn controller xử lý nghiệp vụ CSDL thực tế:*

* 🔑 **Phân Quyền Chi Tiết (`/admin/permissions`):** *(chưa hoàn thiện)* — Giao diện mẫu ma trận quyền hạn nâng cao. (Hệ thống hiện phân quyền theo 2 cấp độ: Admin và Member qua `AdminMiddleware`).
* ⚙️ **Cài Đặt Hệ Thống (`/admin/settings`):** *(chưa hoàn thiện)* — Giao diện mẫu cấu hình website.

---

## 🗂️ 4. Cấu Trúc Thư Mục

```
Web truyện/
├── prototype/                 # GIAO DIỆN MẪU TĨNH (Mockup UI - Không kết nối DB)
│   ├── index.html             # Bản vẽ trang chủ
│   ├── detail.html            # Bản vẽ trang chi tiết truyện & trình đọc
│   ├── genres.html            # Bản vẽ trang lọc thể loại
│   ├── schedule.html          # Bản vẽ lịch phát sóng
│   ├── originals.html         # Bản vẽ danh mục truyện độc quyền
│   ├── admin*.html            # Bản vẽ các màn hình quản trị mẫu
│   ├── app.js                 # Mock logic client, lưu LocalStorage
│   └── style.css              # Design System CSS chuẩn Webtoon
│
├── laravel-blade/             # ỨNG DỤNG CHÍNH (Laravel 11 Fullstack Production)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/   # HomeController, ComicController, ChapterController, Admin/...
│   │   │   ├── Middleware/    # AdminMiddleware, EnsureUserNotBanned
│   │   │   └── Requests/      # StoreCommentRequest, StoreComicRequest, UpdateComicRequest...
│   │   ├── Models/            # Comic, Chapter, Genre, Tag, Author, User, Comment, ActivityLog...
│   │   ├── Policies/          # CommentPolicy
│   │   ├── Services/          # RecommendationService, ImageService, CommentFilterService, LibraryService...
│   │   └── Observers/         # ComicObserver, ChapterObserver (Tự động xóa Cache)
│   ├── config/                # comments.php, badwords.php...
│   ├── database/
│   │   ├── migrations/        # 22+ migrations chuẩn hóa CSDL, composite indexes & unique constraints
│   │   └── seeders/           # Dữ liệu mẫu (11+ bộ truyện đầy đủ chapter, tags, genres, schedules)
│   ├── resources/views/       # Hệ thống Blade templates
│   ├── routes/web.php         # Hệ thống định tuyến URL chuẩn SEO & bảo mật
│   └── tests/                 # 61 Unit & Feature tests (100% Passed)
│
├── server.js                  # Máy chủ Node.js phục vụ chạy thử thư mục prototype/
├── .gitignore                 # Cấu hình an toàn
└── README.md                  # Tài liệu hướng dẫn dự án
```

---

## 📖 5. Hướng Dẫn Sử Dụng Chi Tiết

### 5.1 Dành Cho Độc Giả

1. **Khám Phá & Đọc Truyện:**
   - Sử dụng thanh tìm kiếm trên Navbar để tìm truyện theo tên hoặc tác giả.
   - Nhấp vào mục **"Thể Loại"** để lọc kết hợp theo thể loại và trạng thái truyện.
   - Tại trang chi tiết, nhấn **"Đọc Từ Đầu"** hoặc chọn chương từ mục lục để mở trình đọc cuộn dọc.
2. **Tương Tác & Lưu Trữ:**
   - Nhấn **"Thêm vào tủ sách"** để theo dõi truyện (yêu cầu đăng nhập).
   - Truy cập **"Tủ sách"** trên menu cá nhân để xem danh sách truyện đang theo dõi và lịch sử đọc.
   - Kéo xuống cuối mỗi chương để viết bình luận hoặc trả lời bình luận của độc giả khác.

---

### 5.2 Dành Cho Quản Trị Viên

1. **Đăng Nhập Quản Trị:**
   - Đăng nhập tài khoản `admin@example.com` / `password`, truy cập menu quản trị hoặc đường dẫn `/admin`.
2. **Đăng & Quản Lý Truyện:**
   - Vào mục **Comics** ➡️ Bấm **"Thêm Truyện Mới"**.
   - Điền tiêu đề, mô tả, chọn trạng thái (*ongoing / completed / hiatus / cancelled*), tích chọn thể loại, nhãn, tác giả và tải ảnh bìa.
   - Bấm **Lưu** để khởi tạo bộ truyện.
3. **Thêm Chapter Mới & Hẹn Giờ:**
   - Tại danh sách truyện, bấm **"Quản lý Chapters"** của bộ truyện tương ứng ➡️ Bấm **"Thêm Chapter Mới"**.
   - Nhập số chương, tải lên danh sách ảnh nội dung (Bulk Upload).
   - *(Tùy chọn)* Đặt ngày phát hành trong tương lai để hẹn giờ ra mắt.
4. **Quản Lý Danh Mục & Người Dùng:**
   - Thêm/sửa/xóa Thể loại (Genres), Thẻ (Tags), Tác giả (Authors).
   - Vào mục **Users** để xem danh sách thành viên, nâng quyền Admin hoặc Khóa tài khoản vi phạm (người dùng bị khóa sẽ bị đăng xuất ngay lập tức).

---

## 🛡️ 6. Công Nghệ & Bảo Mật

### 💻 Công Nghệ Sử Dụng
- **Backend:** Laravel 11 (PHP 8.2+), Eloquent ORM, Blade Template Engine.
- **Frontend:** HTML5 Semantic, CSS3 Hiện Đại (CSS Variables, Flexbox/Grid, Glassmorphism, Micro-animations), JavaScript ES6+.
- **Database:** MySQL 8 / SQLite với cơ chế Indexing tối ưu (Composite Index cho các truy vấn phức tạp).
- **Bộ nhớ đệm (Cache Engine):** Version-Key Pattern cho Recommendation Engine, Cache danh sách chương và trang chủ.

### 🔒 Tính Năng Bảo Mật Đã Triển Khai Thực Tế
- **Phân Quyền Chặt Chẽ:** `AdminMiddleware` bảo vệ toàn bộ phân vùng `/admin`, `EnsureUserNotBanned` chặn tức thì các tài khoản bị khóa.
- **Chống Spam & Giới Hạn Tần Suất (Rate Limiting):** Áp dụng `throttle` cho các route nhạy cảm (đăng bình luận, lưu lịch sử đọc, thêm tủ sách).
- **Bảo Vệ Biểu Mẫu:** Bắt buộc `CSRF Token` cho tất cả phương thức POST / PUT / PATCH / DELETE.
- **Kiểm Soát Dữ Liệu Đầu Vào (Form Requests):** Tách biệt validation qua `StoreComicRequest`, `UpdateComicRequest`, `StoreCommentRequest`, `UpdateCommentRequest`, `StoreChapterRequest`.
- **Ràng Buộc Tầng CSDL:** Unique Constraint (`comic_id`, `slug`) và (`comic_id`, `chapter_number`) chống xung đột dữ liệu.
- **Lọc Nội Dung Độc Hại:** Tự động lọc từ cấm tiếng Việt/teencode và chặn liên kết spam trong bình luận (`CommentFilterService`).

---

## 📝 Giấy Phép & Bản Quyền
Dự án được xây dựng phục vụ mục đích học tập, nghiên cứu và phát triển mã nguồn mở theo chuẩn giấy phép [MIT](LICENSE). Mọi hình ảnh và nội dung truyện demo thuộc quyền sở hữu của các tác giả và đơn vị phát hành gốc.
