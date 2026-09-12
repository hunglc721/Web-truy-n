@if(auth()->user()?->isAdmin())
<aside id="upload-task-widget" hidden aria-live="polite" data-user-id="{{ auth()->id() }}" data-csrf="{{ csrf_token() }}" style="position:fixed;right:12px;bottom:72px;z-index:1100;max-width:min(340px,calc(100vw - 24px));padding:12px;border-radius:12px;background:#191e2b;color:#fff;box-shadow:0 4px 20px #0008;font:13px system-ui">
    <button type="button" data-upload-minimize aria-label="Thu gọn upload" style="float:right">−</button>
    <button type="button" data-upload-title aria-label="Theo dõi tiến trình upload" style="color:inherit;background:none;border:0;font-weight:bold;text-align:left;cursor:pointer"></button>
    <div data-upload-details>
        <p data-upload-progress></p><p data-upload-status></p><p data-upload-error role="alert"></p>
        <a data-upload-return style="color:#b9bdff">Quay lại uploader</a>
        <button type="button" data-upload-worker>Mở Upload Worker</button>
        <button type="button" data-upload-cancel>Hủy</button>
    </div>
</aside>
<script src="{{ asset('js/admin-upload-progress.js') }}"></script>
@endif
