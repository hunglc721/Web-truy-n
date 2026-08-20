<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Không có quyền | WebComics</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0f0f1a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .container { max-width: 560px; padding: 2rem; }
        .code {
            font-size: 8rem;
            font-weight: 900;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        h1 { font-size: 1.75rem; margin: 1rem 0 0.5rem; color: #f1f5f9; }
        p  { color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
        .actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        a  {
            display: inline-block;
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            color: #fff;
        }
        .btn-secondary {
            border: 1px solid #374151;
            color: #94a3b8;
        }
        a:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">403</div>
        <h1>Không có quyền truy cập</h1>
        <p>{{ $message ?? 'Bạn không có quyền truy cập trang này. Vui lòng đăng nhập hoặc liên hệ quản trị viên.' }}</p>
        <div class="actions">
            <a href="{{ url('/') }}" class="btn-primary">← Về trang chủ</a>
            @guest
            <a href="{{ route('login') }}" class="btn-secondary">Đăng nhập</a>
            @endguest
        </div>
    </div>
</body>
</html>
