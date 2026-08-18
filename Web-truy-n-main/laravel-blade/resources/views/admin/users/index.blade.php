{{-- resources/views/admin/users/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Thành viên')
@section('breadcrumb', 'Thành viên')

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">👥 Quản lý Thành viên</h1>
  <p class="admin-page-sub">Tổng cộng {{ $users->total() }} thành viên</p>
</div>

{{-- Filter & Search --}}
<div class="admin-card" style="margin-bottom:20px; padding:16px 20px">
  <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
    <div style="flex:1; min-width:200px">
      <label class="form-label" style="margin-bottom:5px">Tìm kiếm</label>
      <input
        type="text" name="search"
        class="form-control"
        value="{{ request('search') }}"
        placeholder="Tên hoặc email..."
      />
    </div>
    <div>
      <label class="form-label" style="margin-bottom:5px">Vai trò</label>
      <select name="role" class="form-control" style="min-width:130px">
        <option value="">Tất cả</option>
        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
        <option value="user"  {{ request('role') === 'user'  ? 'selected' : '' }}>User</option>
      </select>
    </div>
    <div>
      <label class="form-label" style="margin-bottom:5px">Trạng thái</label>
      <select name="status" class="form-control" style="min-width:130px">
        <option value="">Tất cả</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hoạt động</option>
        <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Bị khóa</option>
      </select>
    </div>
    <div style="display:flex; gap:8px">
      <button type="submit" class="btn-admin btn-admin-primary">🔍 Lọc</button>
      @if(request()->hasAny(['search','role','status']))
        <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin-ghost">✖ Xóa lọc</a>
      @endif
    </div>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title">Danh sách thành viên</span>
    <span style="font-size:13px; color:var(--admin-text-muted)">
      Hiện {{ $users->count() }} / {{ $users->total() }} kết quả
    </span>
  </div>

  @if($users->isEmpty())
    <div style="text-align:center; padding:48px; color:var(--admin-text-muted)">
      <div style="font-size:48px; margin-bottom:12px">🔍</div>
      <p>Không tìm thấy thành viên nào.</p>
    </div>
  @else
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:50px">#</th>
            <th>Thành viên</th>
            <th>Email</th>
            <th style="text-align:center">Vai trò</th>
            <th style="text-align:center">Trạng thái</th>
            <th style="text-align:center">Bình luận</th>
            <th style="text-align:center">Thư viện</th>
            <th>Ngày tham gia</th>
            <th style="text-align:center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @foreach($users as $user)
          <tr>
            <td style="color:var(--admin-text-muted); font-size:12px">{{ $user->id }}</td>
            <td>
              <div style="display:flex; align-items:center; gap:10px">
                <div style="
                  width:34px; height:34px; border-radius:50%; flex-shrink:0;
                  background: linear-gradient(135deg, {{ $user->is_admin ? '#6c63ff, #ff2a6d' : '#3b82f6, #06b6d4' }});
                  display:flex; align-items:center; justify-content:center;
                  font-size:13px; font-weight:700; color:#fff;
                ">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div>
                  <a href="{{ route('admin.users.show', $user) }}" style="font-weight:600; color:var(--admin-text); text-decoration:none; font-size:13.5px">
                    {{ $user->name }}
                  </a>
                  @if($user->id === auth()->id())
                    <span style="font-size:10px; color:var(--admin-primary); margin-left:4px">(Bạn)</span>
                  @endif
                </div>
              </div>
            </td>
            <td style="font-size:13px; color:var(--admin-text-muted)">{{ $user->email }}</td>
            <td style="text-align:center">
              @if($user->is_admin ?? false)
                <span class="badge badge-primary">⚡ Admin</span>
              @else
                <span class="badge badge-muted">👤 User</span>
              @endif
            </td>
            <td style="text-align:center">
              @if($user->banned_at)
                <span class="badge badge-danger">🔒 Bị khóa</span>
              @else
                <span class="badge badge-success">✅ Hoạt động</span>
              @endif
            </td>
            <td style="text-align:center">
              <span class="badge badge-info">{{ number_format($user->comments_count) }}</span>
            </td>
            <td style="text-align:center">
              <span class="badge badge-warning">{{ number_format($user->libraries_count) }}</span>
            </td>
            <td style="font-size:12.5px; color:var(--admin-text-muted)">
              {{ $user->created_at->format('d/m/Y') }}
            </td>
            <td style="text-align:center">
              @if($user->id !== auth()->id())
                <div style="display:flex; gap:5px; justify-content:center; flex-wrap:wrap">
                  {{-- Toggle Admin --}}
                  <form method="POST" action="{{ route('admin.users.toggleRole', $user) }}" style="display:inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-admin btn-sm {{ ($user->is_admin ?? false) ? 'btn-admin-warning' : 'btn-admin-primary' }}"
                      style="{{ ($user->is_admin ?? false) ? 'background:rgba(245,158,11,0.15); color:#f59e0b; border:1px solid rgba(245,158,11,0.3)' : '' }}"
                      title="{{ ($user->is_admin ?? false) ? 'Thu hồi quyền Admin' : 'Cấp quyền Admin' }}"
                      onclick="return confirm('{{ ($user->is_admin ?? false) ? 'Thu hồi quyền Admin?' : 'Cấp quyền Admin?' }}')">
                      {{ ($user->is_admin ?? false) ? '👑→👤' : '👤→👑' }}
                    </button>
                  </form>

                  {{-- Toggle Ban --}}
                  @if(!($user->is_admin ?? false))
                  <form method="POST" action="{{ route('admin.users.toggleBan', $user) }}" style="display:inline">
                    @csrf @method('PATCH')
                    <button type="submit"
                      class="btn-admin btn-sm {{ $user->banned_at ? 'btn-admin-success' : 'btn-admin-danger' }}"
                      title="{{ $user->banned_at ? 'Mở khóa tài khoản' : 'Khóa tài khoản' }}"
                      onclick="return confirm('{{ $user->banned_at ? 'Mở khóa tài khoản này?' : 'Khóa tài khoản này?' }}')">
                      {{ $user->banned_at ? '🔓 Mở' : '🔒 Khóa' }}
                    </button>
                  </form>
                  @endif

                  {{-- View Detail --}}
                  <a href="{{ route('admin.users.show', $user) }}" class="btn-admin btn-admin-ghost btn-sm" title="Xem chi tiết">
                    👁
                  </a>
                </div>
              @else
                <span style="font-size:12px; color:var(--admin-text-muted)">—</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="pagination-wrap">{{ $users->links() }}</div>
  @endif
</div>
@endsection
