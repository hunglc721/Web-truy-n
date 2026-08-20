<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Lỗi hệ thống | WebComics</title>
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
            background: linear-gradient(135deg, #ef4444, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        h1 { font-size: 1.75rem; margin: 1rem 0 0.5rem; color: #f1f5f9; }
        p  { color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
        .notice {
            font-size: 0.8rem;
            color: #4b5563;
            margin-bottom: 2rem;
        }
        a  {
            display: inline-block;
            background: linear-gradient(135deg, #ef4444, #dc2626);
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
        <div class="code">500</div>
        <h1>Lỗi hệ thống</h1>
        <p>Hệ thống đang gặp sự cố. Đội ngũ kỹ thuật đã được thông báo và đang xử lý.</p>
        <p class="notice">Nếu sự cố kéo dài, vui lòng liên hệ hỗ trợ.</p>
        <a href="{{ url('/') }}">← Về trang chủ</a>
    </div>
</body>
</html>
