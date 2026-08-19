# 📚 Web Truyện

Website đọc truyện tranh & manga. Gồm hai phần:

- **`prototype/`** – UI demo chạy bằng Node.js (không cần database)
- **`laravel-blade/`** – Backend Laravel 11 đầy đủ (production-ready)

---

## 🚀 Chạy Prototype (Node.js)

> Xem trước giao diện mà không cần setup database.

**Yêu cầu:** Node.js ≥ 18

```bash
# Từ thư mục gốc repo
node server.js
# Truy cập: http://localhost:3000
```

---

## ⚙️ Chạy Laravel (Backend đầy đủ)

**Yêu cầu:** PHP ≥ 8.2, Composer, MySQL/SQLite

```bash
cd laravel-blade

# 1. Cài dependencies
composer install

# 2. Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# 3. Cấu hình database trong .env
#    DB_CONNECTION=mysql
#    DB_DATABASE=web_truyen
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. Chạy migration & seed dữ liệu mẫu
php artisan migrate --seed

# 5. Tạo storage symlink (cho ảnh upload)
php artisan storage:link

# 6. Khởi động server
php artisan serve
# Truy cập: http://localhost:8000
```

### Tài khoản mặc định sau khi seed

| Email | Mật khẩu | Quyền |
|-------|----------|-------|
| `admin@example.com` | `password` | Admin |
| `user@example.com` | `password` | User |

---

## 🗂️ Cấu trúc repo

```
Web truyện/
├── prototype/          # UI demo (HTML/CSS/JS thuần)
│   ├── index.html
│   ├── style.css
│   └── app.js
├── laravel-blade/      # Laravel 11 project
│   ├── app/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   └── ...
├── server.js           # Dev server cho prototype/
├── .gitignore
└── README.md
```

---

## 🔒 Bảo mật

- File `.env` đã được thêm vào `.gitignore` — **không bao giờ commit file này**
- `vendor/` và `node_modules/` cũng bị ignore
- Static server (`server.js`) chỉ serve file trong thư mục `prototype/` (path traversal được chặn)

---

## 📋 Tính năng

- 🏠 Trang chủ với truyện trending & mới cập nhật
- 📖 Đọc chương với chế độ cuộn dọc
- 🔍 Tìm kiếm & lọc theo thể loại
- 📚 Tủ sách cá nhân & lịch sử đọc
- 💬 Bình luận theo chương
- 👑 Panel quản trị đầy đủ (comics, chapters, genres, users)
- 🎨 Responsive design, dark mode

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 11 (PHP 8.2+) |
| Frontend | Blade templates, Vanilla CSS/JS |
| Database | MySQL 8 / SQLite (dev) |
| Prototype | Node.js (built-in HTTP module) |
