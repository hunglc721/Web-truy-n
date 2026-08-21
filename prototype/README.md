# Prototype UI Reference

Thư mục này chỉ dùng để **đối chiếu giao diện/UX** khi chuyển sang Laravel Blade.

Không chạy thư mục này như một website riêng.

- Không có Node server riêng.
- Không dùng mock API hoặc localStorage ở runtime chính.
- Không thêm auth/role logic mới tại đây.
- Mọi chức năng chạy thật phải nằm trong `laravel-blade/`.

Quy tắc migration:

```text
Prototype HTML/CSS/JS
        ↓ tham khảo UI/UX
Laravel Blade
        ↓
Laravel Controller / Service / Model
        ↓
Database
```

Nếu Laravel đã có chức năng tương ứng, giữ logic Laravel và chỉ điều chỉnh Blade/CSS/JS để khớp trải nghiệm prototype.
