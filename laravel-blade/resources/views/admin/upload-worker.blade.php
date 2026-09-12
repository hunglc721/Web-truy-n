<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Comicx · Upload nền</title></head>
<body style="font:16px system-ui;background:#141722;color:#eee;padding:24px">
    <h1>Trình upload nền</h1>
    <p>Giữ cửa sổ này mở. Bạn có thể chuyển trang trong cửa sổ chính.</p>
    <p id="worker-status" role="status">Đang chờ folder từ trang upload…</p>
    <script src="{{ asset('js/admin-upload-worker.js') }}"></script>
</body></html>
