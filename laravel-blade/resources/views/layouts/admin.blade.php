<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Admin Panel') — WebComics</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/style.css') }}" />

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --admin-sidebar-w: 240px; --admin-topbar-h: 60px; --admin-bg: #0d0f14;
      --admin-sidebar-bg: #13161e; --admin-card: #1a1d27; --admin-border: rgba(255,255,255,0.07);
      --admin-text: #e4e6f0; --admin-text-muted: #7b7f9e; --admin-primary: #6c63ff;
      --admin-primary-hover: #574fd6; --admin-success: #22c55e; --admin-danger: #ef4444;
      --admin-warning: #f59e0b; --admin-info: #3b82f6; --admin-radius: 10px;
    }
    body.admin-body { font-family:'Inter',sans-serif; background:var(--admin-bg); color:var(--admin-text); min-height:100vh; }
    .admin-sidebar { width:var(--admin-sidebar-w); background:var(--admin-sidebar-bg); border-right:1px solid var(--admin-border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:100; overflow-y:auto; }
    .sidebar-brand { display:flex; align-items:center; gap:10px; padding:20px 18px 16px; border-bottom:1px solid var(--admin-border); text-decoration:none; }
    .sidebar-brand-icon { width:36px; height:36px; background:linear-gradient(135deg,#6c63ff,#ff2a6d); border-radius:9px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:14px; color:#fff; }
    .sidebar-brand-text { font-weight:800; font-size:15px; color:var(--admin-text); }
    .sidebar-brand-sub { font-size:10px; color:var(--admin-text-muted); }
    .sidebar-nav { padding:12px 10px; flex:1; }
    .sidebar-section-label { font-size:9.5px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--admin-text-muted); padding:12px 8px 6px; }
    .sidebar-link { display:flex; align-items:center; gap:10px; padding:9px 10px; border-radius:8px; color:var(--admin-text-muted); text-decoration:none; font-size:13.5px; font-weight:500; transition:.15s; margin-bottom:2px; }
    .sidebar-link:hover { background:rgba(108,99,255,.12); color:var(--admin-text); }
    .sidebar-link.active { background:rgba(108,99,255,.18); color:var(--admin-primary); font-weight:600; }
    .sidebar-link svg { flex-shrink:0; width:16px; height:16px; }
    .sidebar-footer { padding:12px 10px; border-top:1px solid var(--admin-border); }
    .sidebar-user { display:flex; align-items:center; gap:10px; padding:10px; border-radius:8px; background:rgba(255,255,255,.04); }
    .sidebar-user-avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#6c63ff,#ff2a6d); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; flex-shrink:0; }
    .sidebar-user-name { font-size:12.5px; font-weight:600; color:var(--admin-text); }
    .sidebar-user-role { font-size:10.5px; color:var(--admin-primary); }
    .admin-topbar { position:fixed; top:0; left:var(--admin-sidebar-w); right:0; height:var(--admin-topbar-h); background:var(--admin-sidebar-bg); border-bottom:1px solid var(--admin-border); display:flex; align-items:center; justify-content:space-between; padding:0 24px; z-index:90; }
    .topbar-breadcrumb { display:flex; align-items:center; gap:6px; font-size:13.5px; color:var(--admin-text-muted); }
    .topbar-breadcrumb span { color:var(--admin-text); font-weight:600; }
    .topbar-actions { display:flex; align-items:center; gap:10px; }
    .topbar-btn { display:flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer; text-decoration:none; border:none; transition:.15s; }
    .topbar-btn-primary { background:var(--admin-primary); color:#fff; }
    .topbar-btn-primary:hover { background:var(--admin-primary-hover); }
    .topbar-btn-ghost { background:rgba(255,255,255,.06); color:var(--admin-text); }
    .topbar-btn-danger { background:rgba(239,68,68,.12); color:var(--admin-danger); }
    .admin-main { margin-left:var(--admin-sidebar-w); margin-top:var(--admin-topbar-h); padding:28px 28px 40px; min-height:calc(100vh - var(--admin-topbar-h)); }
    .admin-card { background:var(--admin-card); border:1px solid var(--admin-border); border-radius:var(--admin-radius); padding:24px; }
    .admin-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid var(--admin-border); }
    .admin-card-title { font-size:16px; font-weight:700; color:var(--admin-text); }
    .admin-table { width:100%; border-collapse:collapse; }
    .admin-table th { background:rgba(255,255,255,.04); padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--admin-text-muted); text-align:left; border-bottom:1px solid var(--admin-border); }
    .admin-table td { padding:12px 14px; font-size:13.5px; color:var(--admin-text); border-bottom:1px solid var(--admin-border); vertical-align:middle; }
    .admin-table tr:hover td { background:rgba(255,255,255,.025); }
    .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
    .badge-success { background:rgba(34,197,94,.14); color:var(--admin-success); }
    .badge-danger { background:rgba(239,68,68,.14); color:var(--admin-danger); }
    .badge-primary { background:rgba(108,99,255,.14); color:var(--admin-primary); }
    .badge-warning { background:rgba(245,158,11,.14); color:var(--admin-warning); }
    .badge-info { background:rgba(59,130,246,.14); color:var(--admin-info); }
    .badge-muted { background:rgba(255,255,255,.06); color:var(--admin-text-muted); }
    .dashboard-grid { display:grid; grid-template-columns:1fr; gap:20px; }
    @media(min-width:1024px){ .dashboard-grid{grid-template-columns:repeat(12,1fr)} .col-main-8{grid-column:span 8/span 8}.col-sidebar-4{grid-column:span 4/span 4} }
    .widget-item { display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:8px; background:rgba(255,255,255,.03); border:1px solid var(--admin-border); }
    .rank-badge { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px; flex-shrink:0; }
    .rank-1{background:rgba(245,158,11,.2);color:#f59e0b}.rank-2{background:rgba(108,99,255,.2);color:#6c63ff}.rank-3{background:rgba(59,130,246,.2);color:#3b82f6}.rank-other{background:rgba(255,255,255,.08);color:var(--admin-text-muted)}
    .form-group { margin-bottom:20px; }
    .form-label { display:block; font-size:13px; font-weight:600; color:var(--admin-text); margin-bottom:7px; }
    .form-label span { color:var(--admin-danger); margin-left:2px; }
    .form-hint { font-size:11.5px; color:var(--admin-text-muted); margin-top:5px; }
    .form-control { width:100%; padding:10px 14px; background:rgba(255,255,255,.05); border:1px solid var(--admin-border); border-radius:8px; color:var(--admin-text); font-family:'Inter',sans-serif; font-size:13.5px; outline:none; }
    .form-control:focus { border-color:var(--admin-primary); box-shadow:0 0 0 3px rgba(108,99,255,.2); }
    .form-control.is-invalid { border-color:var(--admin-danger); }
    textarea.form-control { resize:vertical; min-height:100px; }
    .invalid-feedback { color:var(--admin-danger); font-size:12px; margin-top:5px; display:block; }
    .btn-admin { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border-radius:8px; font-size:13.5px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:.15s; line-height:1; }
    .btn-admin-primary { background:var(--admin-primary); color:#fff; }.btn-admin-danger{background:var(--admin-danger);color:#fff}.btn-admin-success{background:var(--admin-success);color:#fff}.btn-admin-ghost{background:rgba(255,255,255,.06);color:var(--admin-text);border:1px solid var(--admin-border)}.btn-sm{padding:5px 12px;font-size:12px}
    .admin-alert { display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-radius:9px; margin-bottom:20px; font-size:13.5px; font-weight:500; }
    .admin-alert-success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);color:#4ade80}.admin-alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#f87171}.admin-alert-warning{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.25);color:#fbbf24}
    .pagination-wrap{display:flex;justify-content:flex-end;margin-top:20px}.pagination-wrap .pagination{display:flex;gap:4px}.pagination-wrap .page-link{display:flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:7px;font-size:13px;font-weight:600;text-decoration:none;color:var(--admin-text-muted);background:rgba(255,255,255,.05);border:1px solid var(--admin-border)}
    .color-swatch{display:inline-block;width:18px;height:18px;border-radius:4px;border:1px solid rgba(255,255,255,.15);vertical-align:middle;margin-right:6px}.avatar-preview{width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--admin-border)}.avatar-placeholder{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#6c63ff,#ff2a6d);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff}
    .admin-page-header{margin-bottom:24px}.admin-page-title{font-size:22px;font-weight:800;color:var(--admin-text)}.admin-page-sub{font-size:13px;color:var(--admin-text-muted);margin-top:4px}.admin-stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px}.admin-stat-card{background:var(--admin-card);border:1px solid var(--admin-border);border-radius:var(--admin-radius);padding:18px 20px}.admin-stat-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--admin-text-muted)}.admin-stat-value{font-size:28px;font-weight:800;color:var(--admin-text);margin-top:4px}.admin-stat-value.primary{color:var(--admin-primary)}
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);z-index:999;align-items:center;justify-content:center}.modal-overlay.show{display:flex}.modal-box{background:var(--admin-card);border:1px solid var(--admin-border);border-radius:14px;padding:28px 30px;max-width:400px;width:90%;text-align:center}.modal-icon{font-size:42px;margin-bottom:12px}.modal-title{font-size:18px;font-weight:700;margin-bottom:8px}.modal-desc{font-size:13.5px;color:var(--admin-text-muted);margin-bottom:22px}.modal-actions{display:flex;gap:10px;justify-content:center}
    @media(max-width:900px){:root{--admin-sidebar-w:200px}.admin-main{padding:20px}.admin-topbar{padding:0 14px}}
  </style>
  @stack('styles')
</head>
<body class="admin-body">

  <aside class="admin-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
      <div class="sidebar-brand-icon">WC</div>
      <div><div class="sidebar-brand-text">WebComics</div><div class="sidebar-brand-sub">Admin Panel</div></div>
    </a>

    <nav class="sidebar-nav">
      <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">📊 Tổng quan</a>
      <div class="sidebar-section-label">NỘI DUNG</div>
      <a href="{{ route('admin.comics.index') }}" class="sidebar-link {{ request()->routeIs('admin.comics.*') ? 'active' : '' }}">📚 Quản lý Truyện</a>
      <a href="{{ route('admin.comics.index') }}#section-add-chapter" class="sidebar-link {{ request()->routeIs('admin.comics.chapters.*') ? 'active' : '' }}">📖 Quản lý Chương</a>
      <a href="{{ route('admin.genres.index') }}" class="sidebar-link {{ request()->routeIs('admin.genres.*') ? 'active' : '' }}">🏷️ Thể loại</a>
      <a href="{{ route('admin.tags.index') }}" class="sidebar-link {{ request()->routeIs('admin.tags.*') ? 'active' : '' }}">🔖 Tags</a>
      <a href="{{ route('admin.authors.index') }}" class="sidebar-link {{ request()->routeIs('admin.authors.*') ? 'active' : '' }}">✍️ Tác giả</a>

      <div class="sidebar-section-label">TƯƠNG TÁC</div>
      <a href="{{ route('admin.comments.index') }}" class="sidebar-link {{ request()->routeIs('admin.comments.*') ? 'active' : '' }}">💬 Bình luận</a>
      <a href="{{ route('admin.reports.index') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">⚠️ Báo cáo lỗi</a>

      <div class="sidebar-section-label">VẬN HÀNH</div>
      <a href="{{ route('admin.schedules.index') }}" class="sidebar-link {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}">📅 Lịch ra truyện</a>
      <a href="{{ route('admin.banners.index') }}" class="sidebar-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">🖼️ Banner</a>

      <div class="sidebar-section-label">NGƯỜI DÙNG</div>
      <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">👥 Thành viên</a>
      <a href="{{ route('admin.permissions.index') }}" class="sidebar-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">🔒 Phân quyền</a>

      <div class="sidebar-section-label">HỆ THỐNG</div>
      <a href="{{ route('admin.logs.index') }}" class="sidebar-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">📜 Nhật ký</a>
      <a href="{{ route('admin.settings.index') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">⚙️ Cài đặt Website</a>
      <a href="{{ route('home') }}" target="_blank" rel="noopener" class="sidebar-link">↗ Xem trang web</a>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
        <div><div class="sidebar-user-name">{{ auth()->user()->name ?? 'Admin' }}</div><div class="sidebar-user-role">⚡ Administrator</div></div>
      </div>
    </div>
  </aside>

  <header class="admin-topbar">
    <div class="topbar-breadcrumb">Admin / <span>@yield('breadcrumb', 'Dashboard')</span></div>
    <div class="topbar-actions">
      @yield('topbar-actions')
      <form method="POST" action="{{ route('logout') }}" style="margin:0">
        @csrf
        <button type="submit" class="topbar-btn topbar-btn-danger">✕ Đăng xuất</button>
      </form>
    </div>
  </header>

  <main class="admin-main">
    @if(session('success'))<div class="admin-alert admin-alert-success" id="flash-msg"><span>✅</span><span>{{ session('success') }}</span></div>@endif
    @if(session('error'))<div class="admin-alert admin-alert-error" id="flash-msg"><span>❌</span><span>{{ session('error') }}</span></div>@endif
    @if(session('warning'))<div class="admin-alert admin-alert-warning" id="flash-msg"><span>⚠️</span><span>{{ session('warning') }}</span></div>@endif
    @yield('content')
  </main>

  <div class="modal-overlay" id="delete-modal">
    <div class="modal-box">
      <div class="modal-icon">🗑️</div><div class="modal-title">Xác nhận xóa</div>
      <div class="modal-desc" id="delete-modal-desc">Bạn có chắc chắn muốn xóa mục này không? Hành động này không thể hoàn tác.</div>
      <div class="modal-actions">
        <button type="button" class="btn-admin btn-admin-ghost" onclick="closeDeleteModal()">Hủy bỏ</button>
        <form id="delete-form" method="POST" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn-admin btn-admin-danger">Xóa ngay</button></form>
      </div>
    </div>
  </div>

  <script>
    const flash = document.getElementById('flash-msg');
    if (flash) setTimeout(() => flash.style.opacity = '0', 4000);
    function confirmDelete(action, itemName) {
      const modal = document.getElementById('delete-modal');
      const form = document.getElementById('delete-form');
      const desc = document.getElementById('delete-modal-desc');
      form.action = action;
      if (itemName) desc.textContent = `Bạn có chắc muốn xóa "${itemName}"? Hành động này không thể hoàn tác.`;
      modal.classList.add('show');
    }
    function closeDeleteModal(){ document.getElementById('delete-modal').classList.remove('show'); }
    document.getElementById('delete-modal')?.addEventListener('click', function(e){ if(e.target === this) closeDeleteModal(); });
  </script>
  @stack('scripts')
</body>
</html>
