@extends('layouts.admin')

@section('title', 'Thông báo hệ thống')
@section('breadcrumb', 'Thông báo hệ thống')

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">🔔 Thông báo hệ thống</h1>
  <p class="admin-page-sub">Gửi thông báo vào inbox user hoặc phát banner/cảnh báo khẩn cấp cho cả khách chưa đăng nhập.</p>
</div>

<div class="admin-stats-row">
  <div class="admin-stat-card"><div class="admin-stat-label">Tổng</div><div class="admin-stat-value">{{ $stats['total'] }}</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Đang bật</div><div class="admin-stat-value">{{ $stats['active'] }}</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">🚨 Khẩn cấp</div><div class="admin-stat-value" style="color:#ef4444">{{ $stats['emergency'] }}</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Hẹn giờ</div><div class="admin-stat-value">{{ $stats['scheduled'] }}</div></div>
</div>

<div style="display:grid;grid-template-columns:minmax(320px,440px) 1fr;gap:20px;align-items:start">
  <section class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">Tạo thông báo</h2></div>
    <form method="POST" action="{{ route('admin.notifications.store') }}">
      @csrf
      <div class="form-group">
        <label class="form-label">Tiêu đề <span>*</span></label>
        <input class="form-control" name="title" maxlength="160" required value="{{ old('title') }}" placeholder="Ví dụ: Bảo trì hệ thống">
        @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label">Nội dung <span>*</span></label>
        <textarea class="form-control" name="message" rows="5" required maxlength="5000" placeholder="Nội dung thông báo...">{{ old('message') }}</textarea>
        @error('message')<span class="invalid-feedback">{{ $message }}</span>@enderror
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label class="form-label">Mức độ</label>
          <select class="form-control" name="severity" id="notice-severity">
            @foreach(['info'=>'🔔 Thông tin','success'=>'✅ Thành công','warning'=>'⚠️ Cảnh báo','emergency'=>'🚨 Khẩn cấp'] as $value=>$label)
              <option value="{{ $value }}" {{ old('severity')===$value?'selected':'' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Đối tượng</label>
          <select class="form-control" name="audience" id="notice-audience">
            <option value="all">Tất cả, kể cả guest</option>
            <option value="guests">Chỉ khách chưa đăng nhập</option>
            <option value="authenticated">Tất cả user đã đăng nhập</option>
            <option value="role">Theo role</option>
            <option value="user">Một user cụ thể</option>
          </select>
        </div>
      </div>

      <div class="form-group" id="role-field" style="display:none">
        <label class="form-label">Role</label>
        <select class="form-control" name="role_slug">
          @foreach($roles as $role)<option value="{{ $role->slug }}">{{ $role->name }}</option>@endforeach
        </select>
      </div>
      <div class="form-group" id="user-field" style="display:none">
        <label class="form-label">User</label>
        <select class="form-control" name="target_user_id">
          @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>@endforeach
        </select>
        <div class="form-hint">Danh sách nhanh tối đa 500 tài khoản.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Link khi bấm</label>
        <input class="form-control" name="link_url" value="{{ old('link_url') }}" placeholder="/schedule hoặc https://...">
        @error('link_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Bắt đầu</label><input class="form-control" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"></div>
        <div class="form-group"><label class="form-label">Kết thúc</label><input class="form-control" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"></div>
      </div>

      <div style="display:flex;flex-direction:column;gap:9px;margin-bottom:18px;font-size:13px">
        <label><input type="checkbox" name="show_banner" value="1" checked> Hiện banner/popup trên website</label>
        <label><input type="checkbox" name="send_to_inbox" value="1" checked> Gửi vào inbox của user đăng nhập</label>
        <label><input type="checkbox" name="is_dismissible" value="1" checked> Cho phép người xem đóng thông báo</label>
      </div>

      <button type="submit" class="btn-admin btn-admin-primary" style="width:100%;justify-content:center">📣 Phát thông báo</button>
      <div class="form-hint" style="margin-top:10px">Thông báo khẩn cấp luôn được phát ra website. Nếu bỏ “cho phép đóng”, người xem sẽ thấy nó cho đến khi Admin tắt hoặc hết thời gian.</div>
    </form>
  </section>

  <section class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">Lịch sử phát</h2></div>
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead><tr><th>Thông báo</th><th>Đối tượng</th><th>Thời gian</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
        @forelse($announcements as $announcement)
          <tr>
            <td style="min-width:260px">
              <div style="font-weight:800">{{ $announcement->severity==='emergency'?'🚨':($announcement->severity==='warning'?'⚠️':'🔔') }} {{ $announcement->title }}</div>
              <div style="font-size:12px;color:var(--admin-text-muted);margin-top:4px">{{ Str::limit($announcement->message, 110) }}</div>
              <div style="font-size:11px;color:var(--admin-text-muted);margin-top:5px">{{ $announcement->show_banner?'Banner':'' }} {{ $announcement->send_to_inbox?' + Inbox':'' }}</div>
            </td>
            <td>{{ $announcement->audience }}@if($announcement->role_slug) / {{ $announcement->role_slug }}@endif @if($announcement->targetUser)<br><small>{{ $announcement->targetUser->email }}</small>@endif</td>
            <td style="font-size:12px">{{ $announcement->starts_at?->format('d/m/Y H:i') ?? 'Ngay' }}<br>@if($announcement->ends_at)<span style="color:var(--admin-text-muted)">đến {{ $announcement->ends_at->format('d/m/Y H:i') }}</span>@endif</td>
            <td><span class="badge {{ $announcement->is_active?'badge-success':'badge-muted' }}">{{ $announcement->is_active?'Đang bật':'Đã tắt' }}</span></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <form method="POST" action="{{ route('admin.notifications.toggle',$announcement) }}">@csrf @method('PATCH')<button class="btn-admin btn-admin-ghost btn-sm" type="submit">{{ $announcement->is_active?'Tắt':'Bật' }}</button></form>
                <form method="POST" action="{{ route('admin.notifications.destroy',$announcement) }}" onsubmit="return confirm('Xóa thông báo này?')">@csrf @method('DELETE')<button class="btn-admin btn-admin-danger btn-sm" type="submit">Xóa</button></form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:var(--admin-text-muted);padding:28px">Chưa có thông báo nào.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="pagination-wrap">{{ $announcements->links() }}</div>
  </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const audience=document.getElementById('notice-audience');
  const severity=document.getElementById('notice-severity');
  const role=document.getElementById('role-field');
  const user=document.getElementById('user-field');
  const sync=()=>{role.style.display=audience.value==='role'?'block':'none';user.style.display=audience.value==='user'?'block':'none'};
  audience?.addEventListener('change',sync);sync();
})();
</script>
@endpush
