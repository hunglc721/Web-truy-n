<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WebComics đang bảo trì</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0d0f14;color:#e4e6f0;font-family:Inter,sans-serif;padding:24px}.card{width:min(560px,100%);background:#1a1d27;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:34px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.45)}.icon{font-size:54px}.title{font-size:26px;font-weight:900;margin:14px 0 8px}.desc{color:#a3a8bd;line-height:1.7;margin:0 auto 22px}.btn{display:inline-flex;padding:10px 18px;border-radius:10px;background:linear-gradient(135deg,#FF5E36,#FF2A6D);color:white;text-decoration:none;font-weight:800}
  </style>
</head>
<body>
  <main class="card">
    <div class="icon">🚧</div>
    <h1 class="title">WebComics đang bảo trì</h1>
    <p class="desc">{{ $message ?? 'Hệ thống đang bảo trì. Vui lòng quay lại sau.' }}</p>
    <a class="btn" href="{{ route('login') }}">Đăng nhập quản trị</a>
  </main>
</body>
</html>
