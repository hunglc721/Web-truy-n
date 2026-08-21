@extends('layouts.admin')

@section('title', 'Nhật ký Hoạt động (Audit Logs)')
@section('breadcrumb', 'Nhật ký Hoạt động')

@push('styles')
<style>
  .badge-action {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    font-family: monospace;
    white-space: nowrap;
  }
  .badge-create { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
  .badge-update { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
  .badge-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
  .badge-auth   { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
  .badge-other  { background: rgba(255, 255, 255, 0.08); color: var(--admin-text-muted); border: 1px solid rgba(255, 255, 255, 0.12); }
</style>
@endpush

@section('content')
<div class="ph" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 22px;">
  <div>
    <h1>📋 Nhật ký Hoạt động Hệ Thống</h1>
    <p>Ghi nhận bất biến mọi thao tác tạo, sửa, xóa, đăng nhập/xuất và kiểm duyệt của ban quản trị.</p>
  </div>
  <button type="button" onclick="openClearModal()" class="btn-admin btn-admin-danger" style="font-size: 12.5px; padding: 8px 14px;">
    🗑️ Dọn Dẹp Nhật Ký
  </button>
</div>

<div class="admin-card">
  
  {{-- BỘ LỌC TÌM KIẾM (FILTERS) --}}
  <form method="GET" action="{{ route('admin.logs.index') }}" style="margin-bottom: 18px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; align-items: flex-end;">
      
      <div>
        <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--admin-text-muted); margin-bottom: 4px;">Tìm kiếm từ khóa</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tên, action, IP..." class="form-control" style="font-size: 12.5px; padding: 8px 10px;" />
      </div>

      <div>
        <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--admin-text-muted); margin-bottom: 4px;">Nhóm hành động</label>
        <select name="action_group" class="form-control" style="font-size: 12.5px; padding: 8px 10px;">
          <option value="">— Tất cả hành động —</option>
          @foreach($actionGroups as $prefix => $label)
            <option value="{{ $prefix }}" {{ request('action_group') === $prefix ? 'selected' : '' }}>
              {{ $label }} ({{ $prefix }}.*)
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--admin-text-muted); margin-bottom: 4px;">Người thực hiện</label>
        <select name="user_id" class="form-control" style="font-size: 12.5px; padding: 8px 10px;">
          <option value="">— Tất cả quản trị viên —</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
              {{ $u->name }} ({{ $u->email }})
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--admin-text-muted); margin-bottom: 4px;">Từ ngày</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" style="font-size: 12.5px; padding: 8px 10px;" />
      </div>

      <div>
        <label style="display: block; font-size: 11.5px; font-weight: 700; color: var(--admin-text-muted); margin-bottom: 4px;">Đến ngày</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" style="font-size: 12.5px; padding: 8px 10px;" />
      </div>

      <div style="display: flex; gap: 6px;">
        <button type="submit" class="btn-admin btn-admin-primary" style="flex: 1; justify-content: center; font-size: 12.5px; padding: 8px 12px;">
          🔍 Lọc
        </button>
        @if(request()->hasAny(['q', 'action_group', 'user_id', 'date_from', 'date_to']))
          <a href="{{ route('admin.logs.index') }}" class="btn-admin btn-admin-ghost" style="padding: 8px 12px; font-size: 12.5px;" title="Xóa bộ lọc">
            ✕
          </a>
        @endif
      </div>

    </div>
  </form>

  {{-- BẢNG DANH SÁCH AUDIT LOGS --}}
  <div style="overflow-x: auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 140px;">Thời gian</th>
          <th style="width: 180px;">Quản trị / Tác nhân</th>
          <th>Hành động</th>
          <th>Đối tượng tác động</th>
          <th>Dữ liệu đính kèm (Payload)</th>
          <th style="width: 120px;">Địa chỉ IP</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
          @php
            $act = $log->action;
            $badgeClass = 'badge-other';
            if (str_contains($act, 'created') || str_contains($act, 'store') || str_contains($act, 'restore') || str_contains($act, 'approve')) {
                $badgeClass = 'badge-create';
            } elseif (str_contains($act, 'updated') || str_contains($act, 'saved') || str_contains($act, 'hide') || str_contains($act, 'status') || str_contains($act, 'toggle')) {
                $badgeClass = 'badge-update';
            } elseif (str_contains($act, 'deleted') || str_contains($act, 'ban') || str_contains($act, 'cleared')) {
                $badgeClass = 'badge-delete';
            } elseif (str_starts_with($act, 'auth.')) {
                $badgeClass = 'badge-auth';
            }
          @endphp
          <tr>
            <td style="font-size: 12px; color: var(--admin-text-muted); white-space: nowrap;">
              <div style="color: #fff; font-weight: 600;">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}</div>
              <div>{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</div>
            </td>

            <td>
              @if($log->user)
                <div style="display: flex; align-items: center; gap: 8px;">
                  <div style="width: 26px; height: 26px; border-radius: 50%; background: rgba(99,102,241,0.2); color: #818cf8; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0;">
                    {{ strtoupper(substr($log->user->name, 0, 1)) }}
                  </div>
                  <div style="min-width: 0;">
                    <div style="font-size: 12.5px; font-weight: 700; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $log->user->name }}</div>
                    <div style="font-size: 11px; color: var(--admin-text-muted);">{{ $log->user->email }}</div>
                  </div>
                </div>
              @else
                <span style="font-size: 12px; color: var(--admin-text-muted); font-style: italic;">⚡ Hệ thống (System)</span>
              @endif
            </td>

            <td>
              <span class="badge-action {{ $badgeClass }}">
                {{ $log->action }}
              </span>
            </td>

            <td style="font-size: 12px;">
              @if($log->subject_type)
                <span style="color: #c084fc; font-family: monospace;">{{ class_basename($log->subject_type) }}</span>
                <span style="color: var(--admin-text-muted);">#{{ $log->subject_id }}</span>
              @else
                <span style="color: var(--admin-text-muted);">—</span>
              @endif
            </td>

            <td style="font-size: 11.5px; max-width: 260px;">
              @if($log->payload && is_array($log->payload))
                <details style="cursor: pointer;">
                  <summary style="color: var(--admin-primary); font-weight: 600; outline: none;">
                    🔍 Chi tiết ({{ count($log->payload) }} trường)
                  </summary>
                  <pre style="background: rgba(0,0,0,0.5); padding: 8px; border-radius: 6px; font-size: 10.5px; margin-top: 4px; overflow-x: auto; color: #a5f3fc; border: 1px solid rgba(255,255,255,0.08);">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
              @else
                <span style="color: var(--admin-text-muted);">Trống</span>
              @endif
            </td>

            <td style="font-family: monospace; font-size: 11.5px; color: var(--admin-text-muted); white-space: nowrap;">
              {{ $log->ip_address ?: '127.0.0.1' }}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; padding: 50px 20px; color: var(--admin-text-muted);">
              <div style="font-size: 40px; margin-bottom: 8px;">📭</div>
              <p style="font-size: 14px; font-weight: 700; color: #fff;">Không tìm thấy nhật ký hoạt động nào.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- PHÂN TRANG --}}
  <div class="pagination-wrap">
    {{ $logs->links() }}
  </div>

</div>

{{-- MODAL DỌN DẸP LOG --}}
<div class="modal-overlay" id="clear-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: #1a1d27; border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 26px 28px; width: 90%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,0.8);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="color: #fff; font-size: 16.5px; font-weight: 800; margin: 0;">🗑️ Dọn Dẹp Nhật Ký Hoạt Động</h3>
      <button type="button" onclick="closeClearModal()" style="background: none; border: none; color: var(--admin-text-muted); font-size: 18px; cursor: pointer;">✕</button>
    </div>

    <form method="POST" action="{{ route('admin.logs.clear') }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhật ký theo tiêu chí đã chọn?');">
      @csrf
      @method('DELETE')

      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #e0e0e0; margin-bottom: 8px;">
          Chọn mốc thời gian muốn xóa
        </label>
        <select name="days" class="form-control">
          <option value="30">Chỉ giữ lại log 30 ngày gần nhất (Xóa cũ hơn 30 ngày)</option>
          <option value="60">Chỉ giữ lại log 60 ngày gần nhất (Xóa cũ hơn 60 ngày)</option>
          <option value="90">Chỉ giữ lại log 90 ngày gần nhất (Xóa cũ hơn 90 ngày)</option>
          <option value="0" style="color: #f87171;">⚠️ Xóa toàn bộ tất cả nhật ký trong CSDL</option>
        </select>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
        <button type="button" onclick="closeClearModal()" class="btn-admin btn-admin-ghost">
          Hủy bỏ
        </button>
        <button type="submit" class="btn-admin btn-admin-danger" style="font-weight: 700;">
          🗑️ Xác Nhận Xóa
        </button>
      </div>
    </form>

  </div>
</div>

<script>
  function openClearModal() {
    const modal = document.getElementById('clear-modal');
    if (modal) modal.style.display = 'flex';
  }

  function closeClearModal() {
    const modal = document.getElementById('clear-modal');
    if (modal) modal.style.display = 'none';
  }

  document.getElementById('clear-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeClearModal();
  });
</script>
@endsection
