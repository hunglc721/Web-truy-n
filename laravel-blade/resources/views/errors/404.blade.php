<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Không tìm thấy | WebComics</title>
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
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        h1 { font-size: 1.75rem; margin: 1rem 0 0.5rem; color: #f1f5f9; }
        p  { color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
        a  {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: #fff;
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        a:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">404</div>
        <h1>Không tìm thấy trang</h1>
        <p>{{ $message ?? 'Trang hoặc nội dung bạn tìm kiếm không tồn tại hoặc đã bị xóa.' }}</p>
        <a href="{{ url('/') }}">← Về trang chủ</a>
    </div>
</body>
</html>
