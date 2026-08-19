# 📚 WebComics - Nền Tảng Đọc Truyện Tranh & Quản Trị Manga/Webtoon Toàn Diện

> Dự án website đọc truyện tranh trực tuyến hiện đại, tối ưu trải nghiệm đọc trên mọi thiết bị và tích hợp bảng điều khiển quản trị (Admin Dashboard) mạnh mẽ.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![Laravel Framework](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![Node.js Prototype](https://img.shields.io/badge/Node.js-18%2B-339933?style=flat&logo=nodedotjs&logoColor=white)](https://nodejs.org)
[![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20SQLite-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

---

## 📑 Mục Lục
- [1. Giới Thiệu & Tác Dụng Của Website](#-1-giới-thiệu--tác-dụng-của-website)
- [2. Các Tính Năng Nổi Bật](#-2-các-tính-năng-nổi-bật)
  - [2.1 Dành Cho Độc Giả (Client / Reader)](#21-dành-cho-độc-giả-client--reader)
  - [2.2 Dành Cho Quản Trị Viên (Admin & Biên Tập Viên)](#22-dành-cho-quản-trị-viên-admin--biên-tập-viên)
- [3. Cấu Trúc Dự Án](#-3-cấu-trúc-dự-án)
- [4. Hướng Dẫn Cài Đặt & Khởi Chạy](#-4-hướng-dẫn-cài-đặt--khởi-chạy)
  - [Cách 1: Chạy Bản Prototype (Node.js - Siêu Nhanh, Không Cần Database)](#cách-1-chạy-bản-prototype-nodejs---siêu-nhanh-không-cần-database)
  - [Cách 2: Chạy Bản Laravel 11 (Fullstack Backend Production-Ready)](#cách-2-chạy-bản-laravel-11-fullstack-backend-production-ready)
- [5. Hướng Dẫn Sử Dụng Chi Tiết](#-5-hướng-dẫn-sử-dụng-chi-tiết)
  - [5.1 Hướng Dẫn Dành Cho Độc Giả](#51-hướng-dẫn-dành-cho-độc-giả)
  - [5.2 Hướng Dẫn Dành Cho Quản Trị Viên](#52-hướng-dẫn-dành-cho-quản-trị-viên)
- [6. Công Nghệ & Bảo Mật](#-6-công-nghệ--bảo-mật)

---

## 🌟 1. Giới Thiệu & Tác Dụng Của Website

**WebComics** được thiết kế nhằm giải quyết nhu cầu đọc truyện tranh (Manga, Manhwa, Webtoon, Comic) với giao diện mượt mà, chuẩn Webtoon quốc tế, đồng thời cung cấp giải pháp xuất bản và quản lý nội dung số hiệu quả.

### 🎯 Tác dụng chính của hệ thống:
1. **Phục vụ Độc giả (End-Users):**
   - Đem lại không gian đọc truyện số 1 về trải nghiệm: tải ảnh tốc độ cao, cuộn trang không gián đoạn, tự động lưu lịch sử đọc và đánh dấu chương đọc dở.
   - Dễ dàng tiếp cận kho truyện phong phú thông qua bộ lọc thông minh, bảng xếp hạng trending và lịch phát hành theo ngày.
   - Tạo cộng đồng giao lưu, bình luận, tương tác và đánh giá các bộ truyện yêu thích.

2. **Phục vụ Đội ngũ Biên tập & Nhóm dịch (Creators/Editors):**
   - Đơn giản hóa quy trình đăng tải truyện, tải lên hàng loạt trang ảnh (bulk upload) cho từng chương.
   - Phân loại truyện linh hoạt theo Thể loại (Genres), Thẻ (Tags) và Tác giả (Authors).
   - Lên lịch phát hành tự động giúp giữ chân người đọc theo từng ngày trong tuần.

3. **Phục vụ Quản trị viên & Doanh nghiệp (Site Owners/Admins):**
   - Nắm bắt chỉ số người dùng, lượt xem, lượt thích thông qua bảng phân tích số liệu (Analytics).
   - Kiểm duyệt nội dung, quản lý người dùng, xử lý báo cáo vi phạm và quản lý quảng cáo/banner tài trợ.

---

## ✨ 2. Các Tính Năng Nổi Bật

### 2.1 Dành Cho Độc Giả (Client / Reader)
* 🏠 **Trang Chủ Hiện Đại:** Hero Banner nổi bật các siêu phẩm, bảng xếp hạng Top Trending (Rank 1, 2, 3 độc quyền) và danh sách truyện mới cập nhật theo thời gian thực.
* 📖 **Trình Đọc Chương Chuyên Nghiệp (Webtoon Reader):**
  * Chế độ cuộn dọc liên tục mượt mà, tối ưu cho cả máy tính và điện thoại.
  * Bộ điều hướng nhanh: Chuyển chương Trước/Sau, chọn chương từ danh sách thả xuống.
  * Tự động ghi nhớ chương vừa đọc để độc giả quay lại đọc tiếp bất cứ lúc nào.
* 🔍 **Tìm Kiếm & Bộ Lọc Nâng Cao:**
  * Tìm kiếm tức thì theo tên truyện, tên tác giả.
  * Lọc kết hợp đa thể loại (Hành động, Tình cảm, Isekai, Trinh thám, Võ thuật, Hài hước,...).
  * Lọc theo trạng thái (*Đang phát hành / Đã hoàn thành*) và sắp xếp (*Xem nhiều nhất, Đánh giá cao nhất, Mới nhất*).
* 📅 **Lịch Phát Sóng (Schedule):** Theo dõi lịch ra mắt chương mới từ Thứ 2 đến Chủ Nhật.
* ⭐ **Độc Quyền (Originals):** Khu vực dành riêng cho các tác phẩm Webtoon bản quyền đặc sắc.
* 📚 **Tủ Sách & Lịch Sử Cá Nhân (Personal Library):**
  * Đánh dấu yêu thích và lưu truyện vào tủ sách chỉ với 1 click.
  * Xem lại lịch sử đọc chi tiết, hỗ trợ xóa lịch sử khi cần.
* 💬 **Hệ Thống Bình Luận & Tương Tác:**
  * Bình luận trực tiếp dưới từng chương và phản hồi (reply).
  * Tích hợp chống spam (Rate Limit / Throttle).
* 🌓 **Giao Diện Đa Dụng (Dark/Light Mode):** Chuyển đổi tông màu bảo vệ mắt khi đọc truyện ban đêm.

---

### 2.2 Dành Cho Quản Trị Viên (Admin & Biên Tập Viên)
* 📊 **Dashboard Thống Kê (Analytics):** Báo cáo trực quan tổng số truyện, tổng chương, lượt xem, người dùng mới và tương tác.
* 📚 **Quản Lý Truyện (Comics Management):** Thêm mới, chỉnh sửa thông tin, upload ảnh bìa, đặt trạng thái (Hoàn thành / Đang tiến hành), gắn thể loại, tag, tác giả.
* 📑 **Quản Lý Chương (Chapters Management):**
  * Thêm chương mới, sửa số thứ tự chương.
  * Tải lên danh sách ảnh hàng loạt (Bulk Chapter Pages Upload) và sắp xếp thứ tự trang.
* 🏷️ **Quản Lý Danh Mục:** Thể loại (Genres), Thẻ phụ (Tags), Tác giả & Họa sĩ (Authors).
* 👥 **Quản Lý Thành Viên (Users):** Danh sách người dùng, phân quyền (Admin / Member), khóa tài khoản (Ban/Unban) khi có hành vi xấu.
* 🛡️ **Kiểm Duyệt Nội Dung & Báo Cáo:**
  * Quản lý & duyệt bình luận (Comments Moderation).
  * Tiếp nhận và giải quyết phản hồi lỗi từ độc giả (Report Center: ảnh lỗi, sai thứ tự,...).
* 🎨 **Quản Lý Banners & Quảng Cáo:** Thay đổi slide trang chủ, banner giới thiệu sự kiện.
* 📜 **Nhật Ký Hệ Thống (Audit Logs):** Theo dõi mọi thao tác thay đổi dữ liệu của đội ngũ quản trị.

---

## 🗂️ 3. Cấu Trúc Dự Án

Dự án được phát triển theo mô hình 2 tầng linh hoạt:

```
Web truyện/
├── prototype/                 # Giao diện tĩnh & demo mockup nhanh (HTML5, CSS3, Vanilla JS)
│   ├── index.html             # Trang chủ
│   ├── detail.html            # Trang chi tiết truyện & trình đọc
│   ├── genres.html            # Trang lọc thể loại
│   ├── schedule.html          # Lịch phát sóng
│   ├── originals.html         # Danh mục truyện độc quyền
│   ├── admin*.html            # 12+ màn hình quản trị hoàn chỉnh
│   ├── app.js                 # Xử lý logic phía client, lưu trữ LocalStorage
│   └── style.css              # Hệ thống CSS Design System chuẩn Webtoon
│
├── laravel-blade/             # Source code backend hoàn chỉnh (Laravel 11 Production)
│   ├── app/
│   │   ├── Http/Controllers/ # Bộ điều khiển (HomeController, ChapterController, Admin/...)
│   │   ├── Models/            # Eloquent Models (Comic, Chapter, Genre, Tag, User,...)
│   │   └── Middleware/        # AdminMiddleware, ThrottleMiddleware...
│   ├── database/
│   │   ├── migrations/        # Cấu trúc CSDL chuẩn hóa (foreign keys, indexes)
│   │   └── seeders/           # Dữ liệu mẫu (35+ truyện, tài khoản test)
│   ├── resources/views/       # Giao diện Blade Templates kế thừa & module hóa
│   ├── routes/web.php         # Hệ thống định tuyến URL chuẩn SEO
│   └── public/                # Static assets (CSS, JS, upload storage)
│
├── server.js                  # Máy chủ Node.js phục vụ chạy nhanh thư mục prototype/
├── .gitignore                 # Cấu hình bỏ qua các file nhạy cảm & dependencies
└── README.md                  # Hướng dẫn chi tiết dự án
```

---

## 🚀 4. Hướng Dẫn Cài Đặt & Khởi Chạy

Bạn có thể lựa chọn 1 trong 2 cách khởi chạy tùy theo mục đích sử dụng:

### Cách 1: Chạy Bản Prototype (Node.js - Siêu Nhanh, Không Cần Database)
> *Phù hợp để trình chiếu UI/UX, kiểm thử giao diện độc giả và bảng điều khiển quản trị ngay lập tức.*

* **Yêu cầu:** Máy tính đã cài [Node.js](https://nodejs.org/) (phiên bản 18+).

```bash
# 1. Mở Terminal tại thư mục gốc của dự án (Web truyện)
# 2. Khởi chạy máy chủ tích hợp:
node server.js
```
* **Địa chỉ truy cập:** [http://localhost:3000](http://localhost:3000)
* **Giao diện quản trị prototype:** [http://localhost:3000/admin.html](http://localhost:3000/admin.html)

---

### Cách 2: Chạy Bản Laravel 11 (Fullstack Backend Production-Ready)
> *Dành cho môi trường phát triển đầy đủ với cơ sở dữ liệu, phân quyền tài khoản và lưu trữ file thật.*

* **Yêu cầu:** 
  - PHP ≥ 8.2 (đã bật extensions: `pdo`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`)
  - [Composer](https://getcomposer.org/)
  - Cơ sở dữ liệu: MySQL 8.x hoặc SQLite

**Các bước cài đặt chi tiết:**

```bash
# Bước 1: Di chuyển vào thư mục Laravel
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
  *(Tạo một file rỗng `database/database.sqlite` nếu dùng SQLite)*

**Bước 6: Khởi tạo bảng & nạp dữ liệu mẫu**
```bash
php artisan migrate --seed
```

**Bước 7: Tạo liên kết thư mục tải ảnh (Symlink)**
```bash
php artisan storage:link
```

**Bước 8: Khởi chạy máy chủ**
```bash
php artisan serve
```
* **Địa chỉ trang web:** [http://localhost:8000](http://localhost:8000)

#### 🔑 Tài khoản đăng nhập mặc định:
| Vai trò | Email đăng nhập | Mật khẩu mặc định | Quyền hạn |
|:---|:---|:---|:---|
| **Quản trị viên (Admin)** | `admin@example.com` | `password` | Toàn quyền truy cập `/admin` |
| **Độc giả (Member)** | `user@example.com` | `password` | Đọc truyện, bình luận, lưu tủ sách |

---

## 📖 5. Hướng Dẫn Sử Dụng Chi Tiết

### 5.1 Hướng Dẫn Dành Cho Độc Giả

1. **Tìm & Lựa Chọn Truyện:**
   - Tại thanh điều hướng (Navbar), nhập từ khóa vào ô tìm kiếm để tra cứu truyện nhanh.
   - Nhấp vào mục **"Thể Loại"** để lọc truyện theo sở thích (ví dụ: *Action + Fantasy*).
   - Nhấp vào mục **"Lịch Phát Sóng"** để xem hôm nay có chương mới của truyện nào.

2. **Trải Nghiệm Đọc Truyện:**
   - Vào trang chi tiết truyện, nhấp **"Đọc Từ Đầu"** hoặc **"Đọc Tiếp"** để vào giao diện đọc chương.
   - Cuộn chuột hoặc vuốt màn hình để đọc từng trang ảnh.
   - Sử dụng thanh công cụ điều khiển nổi (Floating toolbar) để chuyển chương hoặc quay về mục lục.

3. **Lưu Truyện & Bình Luận:**
   - Bấm vào biểu tượng **"Thêm vào tủ sách"** (Bookmark) ở trang chi tiết để nhận thông báo khi có chương mới.
   - Kéo xuống cuối chương để gửi bình luận, trao đổi ý kiến với những người đọc khác.

---

### 5.2 Hướng Dẫn Dành Cho Quản Trị Viên

1. **Truy Cập Trang Quản Trị:**
   - Đăng nhập bằng tài khoản Admin, sau đó truy cập đường dẫn `/admin`.

2. **Đăng Tải & Cập Nhật Truyện Mới:**
   - Vào mục **Comics (Quản lý truyện)** ➡️ Nhấp nút **"Thêm Truyện Mới"** (`Create Comic`).
   - Nhập tiêu đề truyện, chọn tác giả, chọn các thể loại và tải lên ảnh bìa (Cover Image).
   - Bấm **Lưu** để khởi tạo bộ truyện trên hệ thống.

3. **Thêm Chương & Tải Ảnh Lên (Upload Chapter):**
   - Tại danh sách truyện, nhấp vào nút **"Quản lý Chapters"** của truyện tương ứng.
   - Bấm **"Thêm Chapter Mới"**, điền số thứ tự chương (VD: *Chapter 1* hoặc *Chapter 25*).
   - Tải lên danh sách ảnh nội dung chương (hỗ trợ JPG, PNG, WebP) và nhấn **Xuất Bản**.

4. **Quản Lý Người Dùng & Bình Luận:**
   - Vào mục **Users** để kiểm tra danh sách thành viên, nâng quyền lên Quản trị viên hoặc tạm khóa tài khoản có hành vi vi phạm.
   - Vào mục **Comments** và **Reports** để duyệt các bình luận phản cảm hoặc xử lý báo cáo chương hỏng.

---

## 🛡️ 6. Công Nghệ & Bảo Mật

### 💻 Công Nghệ Sử Dụng
- **Backend:** Laravel 11 (PHP 8.2+), Eloquent ORM, Blade Template Engine.
- **Frontend:** HTML5 Semantic, CSS3 Hiện Đại (CSS Variables, Flexbox/Grid, Glassmorphism, Micro-animations), JavaScript ES6+.
- **Database:** MySQL 8 / SQLite với cơ chế Indexing tối ưu truy vấn.
- **Mock Dev Server:** Node.js HTTP Server với cơ chế bảo vệ ngăn chặn Directory Traversal.

### 🔒 Tính Năng Bảo Mật Đã Triển Khai
- **Bảo Vệ Đường Dẫn & Role-Based Access Control:** Sử dụng `AdminMiddleware` ngăn chặn người dùng thường truy cập trang quản trị.
- **Chống Spam API (Rate Limiting / Throttle):** Giới hạn tần suất gọi API bình luận, lưu lịch sử và tương tác nhằm chống tấn công DDoS và spam bot.
- **Bảo Vệ Biểu Mẫu:** Toàn bộ form submit đều được tích hợp mã `CSRF Token`.
- **Chuẩn Hóa Đường Dẫn (SEO Slug Sanitization):** Ràng buộc Regex `[a-z0-9\-]` cho slug truyện và chương nhằm chặn mã độc và URL rác.
- **Bảo Mật Dữ Liệu:** File `.env` chứa mật khẩu cơ sở dữ liệu và app key được cấu hình an toàn trong `.gitignore`.

---

## 📝 Giấy Phép & Bản Quyền
Dự án được xây dựng phục vụ mục đích học tập, nghiên cứu và phát triển mã nguồn mở theo chuẩn giấy phép [MIT](LICENSE). Mọi hình ảnh và nội dung truyện demo thuộc quyền sở hữu của các tác giả và đơn vị phát hành gốc.
