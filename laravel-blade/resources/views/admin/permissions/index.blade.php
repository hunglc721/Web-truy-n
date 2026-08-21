@extends('layouts.admin')

@section('title', 'Phân Quyền')
@section('breadcrumb', 'Phân quyền')

@push('styles')
<style>
  .perm-grid { display:grid; grid-template-columns:minmax(240px,1.5fr) repeat(2,minmax(150px,.6fr)); width:100%; }
  .perm-cell { padding:14px 16px; border-bottom:1px solid var(--admin-border); display:flex; align-items:center; }
  .perm-head { background:rgba(255,255,255,.04); color:var(--admin-text-muted); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.7px; }
  .perm-center { justify-content:center; text-align:center; }
  .perm-title { font-weight:700; color:var(--admin-text); font-size:13.5px; }
  .perm-desc { color:var(--admin-text-muted); font-size:11.5px; margin-top:3px; }
  .role-pill { display:inline-flex; padding:5px 10px; border-radius:999px; font-size:12px; font-weight:800; }
  .role-admin { background:rgba(108,99,255,.16); color:#818cf8; }
  .role-member { background:rgba(59,130,246,.13); color:#60a5fa; }
  .perm-yes { color:#4ade80; font-size:18px; font-weight:900; }
  .perm-no { color:#64748b; font-size:18px; font-weight:900; }
  .architecture-note { padding:14px 16px; border-radius:10px; background:rgba(59,130,246,.1); border:1px solid rgba(59,130,246,.25); color:#93c5fd; margin-bottom:20px; font-size:13px; line-height:1.6; }
  @media(max-width:760px){ .perm-grid{grid-template-columns:minmax(190px,1.4fr) repeat(2,minmax(105px,.6fr)); overflow-x:auto;} }
</style>
@endpush

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">🔒 Phân Quyền Hệ Thống</h1>
  <p class="admin-page-sub">Ma trận quyền đang chạy thật theo mô hình 2 vai trò của WebComics.</p>
</div>

<div class="architecture-note">
  <strong>Kiến trúc hiện tại:</strong> hệ thống chỉ có <strong>Member</strong> và <strong>Admin</strong>. Không tạo Moderator/Editor/Viewer giả như prototype vì database và middleware hiện chưa có các role đó. Quyền Admin được quyết định ở backend bằng <code>User::isAdmin()</code> và <code>AdminMiddleware</code>.
</div>

<div class="admin-card" style="padding:0; overflow:hidden;">
  <div class="perm-grid">
    <div class="perm-cell perm-head">Chức năng</div>
    <div class="perm-cell perm-head perm-center"><span class="role-pill role-member">👤 Member</span></div>
    <div class="perm-cell perm-head perm-center"><span class="role-pill role-admin">🛡️ Admin</span></div>

    @php
      $permissions = [
        ['Đọc truyện', 'Xem trang chủ, chi tiết truyện và chapter', true, true],
        ['Tìm kiếm & xem bình luận', 'Các API đọc công khai', true, true],
        ['Bình luận & trả lời', 'Yêu cầu đăng nhập', true, true],
        ['Tủ truyện & yêu thích', 'Theo dõi, like và lịch sử đọc', true, true],
        ['Đánh giá truyện', 'Chấm sao và viết nhận xét', true, true],
        ['Quản lý Truyện / Chapter', 'CRUD nội dung và upload chapter', false, true],
        ['Quản lý Genres / Tags / Authors', 'CRUD dữ liệu danh mục', false, true],
        ['Quản lý Thành viên', 'Phân quyền Admin và khóa tài khoản', false, true],
        ['Kiểm duyệt Bình luận', 'Duyệt, ẩn, xóa và xử lý spam', false, true],
        ['Xử lý Báo cáo', 'Quản lý report từ reader', false, true],
        ['Lịch phát hành & Banner', 'Quản trị vận hành trang chủ', false, true],
        ['Audit Logs', 'Xem nhật ký hoạt động quản trị', false, true],
        ['Trang /admin', 'Được bảo vệ bởi auth + AdminMiddleware', false, true],
      ];
    @endphp

    @foreach($permissions as [$label, $desc, $member, $admin])
      <div class="perm-cell">
        <div><div class="perm-title">{{ $label }}</div><div class="perm-desc">{{ $desc }}</div></div>
      </div>
      <div class="perm-cell perm-center"><span class="{{ $member ? 'perm-yes' : 'perm-no' }}">{{ $member ? '✓' : '✕' }}</span></div>
      <div class="perm-cell perm-center"><span class="{{ $admin ? 'perm-yes' : 'perm-no' }}">{{ $admin ? '✓' : '✕' }}</span></div>
    @endforeach
  </div>
</div>

<div class="admin-card" style="margin-top:20px;">
  <div class="admin-card-header"><h2 class="admin-card-title">Cấp / gỡ quyền Admin</h2></div>
  <p style="color:var(--admin-text-muted); font-size:13px; line-height:1.7; margin-bottom:14px;">
    Việc cấp quyền được thực hiện tại trang Quản lý Thành viên. Hệ thống dùng cột <code>is_admin</code> làm nguồn sự thật duy nhất, vì thế không lưu một ma trận quyền giả bằng LocalStorage như prototype.
  </p>
  <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin-primary">👥 Mở Quản Lý Thành Viên</a>
</div>
@endsection
