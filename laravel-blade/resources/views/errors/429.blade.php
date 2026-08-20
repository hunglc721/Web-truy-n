<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 — Thao tác quá nhanh | WebComics</title>
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
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        h1 { font-size: 1.75rem; margin: 1rem 0 0.5rem; color: #f1f5f9; }
        p  { color: #94a3b8; line-height: 1.6; margin-bottom: 1rem; }
        .retry-info {
            display: inline-block;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            color: #93c5fd;
            margin-bottom: 2rem;
        }
        a  {
            display: inline-block;
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
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
        <div class="code">429</div>
        <h1>Thao tác quá nhanh</h1>
        <p>Bạn đang thực hiện quá nhiều yêu cầu trong thời gian ngắn.</p>
        @isset($retry_after)
        <div class="retry-info">⏱ Vui lòng thử lại sau <strong>{{ $retry_after }}</strong> giây.</div>
        @endisset
        <br>
        <a href="{{ url()->previous('/') }}">← Quay lại</a>
    </div>
</body>
</html>
