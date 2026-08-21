{{-- resources/views/admin/users/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Thành viên')
@section('breadcrumb', 'Thành viên')

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">👥 Quản lý Thành viên</h1>
  <p class="admin-page-sub">Tìm kiếm, phân vai trò và khóa/mở khóa tài khoản bằng quyền backend thật.</p>
</div>

<div class="admin-stats-row">
  <div class="admin-stat-card"><div class="admin-stat-label">Tổng tài khoản</div><div class="admin-stat-value primary">{{ number_format($stats['total']) }}</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Admins</div><div class="admin-stat-value">{{ number_format($stats['admins']) }}</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Members</div><div class="admin-stat-value">{{ number_format($stats['members']) }}</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Hoạt động</div><div class="admin-stat-value" style="color:var(--admin-success)">{{ number_format($stats['active']) }}</div></div>
  <div class="admin-stat-card"><div class="admin-stat-label">Bị khóa</div><div class="admin-stat-value" style="color:var(--admin-danger)">{{ number_format($stats['banned']) }}</div></div>
</div>

<div class="admin-card" style="margin-bottom:20px; padding:16px 20px">
  <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <div style="flex:1;min-width:220px">
      <label class="form-label" style="margin-bottom:5px">Tìm kiếm</label>
      <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Tên hoặc email..." />
    </div>
    <div>
      <label class="form-label" style="margin-bottom:5px">Vai trò</label>
      <select name="role" class="form-control" style="min-width:150px">
        <option value="">Tất cả</option>
        @foreach($roles as $role)
          <option value="{{ $role->slug }}" {{ request('role') === $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="form-label" style="margin-bottom:5px">Trạng thái</label>
      <select name="status" class="form-control" style="min-width:140px">
        <option value="">Tất cả</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hoạt động</option>
        <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Bị khóa</option>
      </select>
    </div>
    <div style="display:flex;gap:8px">
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
    <span style="font-size:13px;color:var(--admin-text-muted)">Hiện {{ $users->count() }} / {{ $users->total() }} kết quả</span>
  </div>

  @if($users->isEmpty())
    <div style="text-align:center;padding:48px;color:var(--admin-text-muted)"><div style="font-size:48px;margin-bottom:12px">🔍</div><p>Không tìm thấy thành viên nào.</p></div>
  @else
    <div style="overflow-x:auto">
      <table class="admin-table" style="min-width:1050px">
        <thead><tr><th>#</th><th>Thành viên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Hoạt động</th><th>Ngày tham gia</th><th style="text-align:center">Thao tác</th></tr></thead>
        <tbody>
          @foreach($users as $user)
            @php
              $roleSlug = $user->roleSlug();
              $roleLabel = $user->role?->name ?? ($user->isAdmin() ? 'Administrator' : 'Member');
              $roleBadge = match($roleSlug) {
                'admin' => 'badge-primary',
                'moderator' => 'badge-warning',
                'editor' => 'badge-info',
                'viewer' => 'badge-muted',
                default => 'badge-success',
              };
            @endphp
            <tr>
              <td style="color:var(--admin-text-muted);font-size:12px">{{ $user->id }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6c63ff,#ff2a6d);display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;flex-shrink:0">{{ strtoupper(substr($user->name,0,1)) }}</div>
                  <div><a href="{{ route('admin.users.show',$user) }}" style="font-weight:700;color:var(--admin-text);text-decoration:none">{{ $user->name }}</a>@if($user->id===auth()->id()) <span style="font-size:10px;color:var(--admin-primary)">(Bạn)</span>@endif</div>
                </div>
              </td>
              <td style="font-size:13px;color:var(--admin-text-muted)">{{ $user->email }}</td>
              <td><span class="badge {{ $roleBadge }}">{{ $roleLabel }}</span></td>
              <td>@if($user->banned_at)<span class="badge badge-danger">🔒 Bị khóa</span>@else<span class="badge badge-success">✅ Hoạt động</span>@endif</td>
              <td style="font-size:12px;color:var(--admin-text-muted)">💬 {{ $user->comments_count }} · 📚 {{ $user->libraries_count }} · ⭐ {{ $user->ratings_count }} · 📖 {{ $user->reading_histories_count }}</td>
              <td style="font-size:12px;color:var(--admin-text-muted)">{{ $user->created_at?->format('d/m/Y') }}</td>
              <td style="text-align:center">
                <div style="display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap">
                  <a href="{{ route('admin.users.show',$user) }}" class="btn-admin btn-admin-ghost btn-sm">👁 Chi tiết</a>

                  @if($user->id !== auth()->id() && auth()->user()->hasPermission('users.manage_role'))
                    <form method="POST" action="{{ route('admin.users.updateRole',$user) }}" style="display:flex;gap:5px;align-items:center">
                      @csrf @method('PATCH')
                      <select name="role" class="form-control" style="padding:6px 8px;font-size:11.5px;width:125px">
                        @foreach($roles as $role)
                          <option value="{{ $role->slug }}" {{ $roleSlug === $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                      </select>
                      <button type="submit" class="btn-admin btn-admin-primary btn-sm" onclick="return confirm('Đổi vai trò của tài khoản này?')">Lưu role</button>
                    </form>
                  @endif

                  @if($user->id !== auth()->id() && auth()->user()->hasPermission('users.ban') && !$user->isAdmin())
                    <form method="POST" action="{{ route('admin.users.toggleBan',$user) }}">@csrf @method('PATCH')
                      <button type="submit" class="btn-admin btn-sm {{ $user->banned_at ? 'btn-admin-success' : 'btn-admin-danger' }}" onclick="return confirm('{{ $user->banned_at ? 'Mở khóa tài khoản này?' : 'Khóa tài khoản này?' }}')">{{ $user->banned_at ? '🔓 Mở khóa' : '🔒 Khóa' }}</button>
                    </form>
                  @endif
                </div>
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
