@extends('layouts.admin')

@section('title', 'Phân Quyền')
@section('breadcrumb', 'Phân quyền')

@push('styles')
<style>
  .perm-table-wrap{overflow-x:auto}.perm-table{width:100%;border-collapse:collapse;min-width:900px}.perm-table th,.perm-table td{padding:11px 12px;border-bottom:1px solid var(--admin-border);vertical-align:middle}.perm-table th{font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:var(--admin-text-muted);background:rgba(255,255,255,.035)}.perm-name{font-weight:700;font-size:13px}.perm-desc{font-size:11px;color:var(--admin-text-muted);margin-top:2px}.perm-check{width:18px;height:18px;accent-color:var(--admin-primary);cursor:pointer}.role-card{margin-bottom:20px}.role-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:12px}.role-title{display:flex;align-items:center;gap:8px}.role-pill{padding:5px 10px;border-radius:999px;font-size:11px;font-weight:800;background:rgba(108,99,255,.14);color:#a5b4fc}.readonly-note{font-size:12px;color:var(--admin-text-muted)}
</style>
@endpush

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">🔒 Roles & Permissions</h1>
  <p class="admin-page-sub">Permission được lưu trong DB và được middleware kiểm tra trên route, không còn localStorage/mock.</p>
</div>

<div class="admin-alert admin-alert-warning"><span>🛡️</span><span><strong>Admin</strong> luôn có toàn quyền. Member không vào khu quản trị. Moderator, Editor và Viewer dùng ma trận dưới đây.</span></div>

@foreach($roles as $role)
  @php $currentIds=$role->permissions->pluck('id')->all(); $isAdminRole=$role->slug==='admin'; @endphp
  <div class="admin-card role-card">
    <div class="role-head">
      <div class="role-title"><span class="role-pill">{{ strtoupper($role->slug) }}</span><div><strong>{{ $role->name }}</strong><div class="readonly-note">{{ $isAdminRole ? 'Toàn quyền cố định' : 'Chỉnh quyền và lưu xuống database' }}</div></div></div>
      @unless($isAdminRole)<button type="submit" form="role-form-{{ $role->id }}" class="btn-admin btn-admin-primary">💾 Lưu {{ $role->name }}</button>@endunless
    </div>

    <form id="role-form-{{ $role->id }}" method="POST" action="{{ $isAdminRole ? '#' : route('admin.permissions.update',$role) }}">
      @csrf @unless($isAdminRole) @method('PUT') @endunless
      <div class="perm-table-wrap">
        <table class="perm-table">
          <thead><tr><th>Permission</th><th>Mô tả</th><th style="width:120px;text-align:center">Cho phép</th></tr></thead>
          <tbody>
            @foreach($permissions as $permission)
              <tr>
                <td><div class="perm-name"><code>{{ $permission->slug }}</code></div></td>
                <td><div class="perm-desc">{{ $permission->description }}</div></td>
                <td style="text-align:center">
                  <input type="checkbox" class="perm-check" name="permissions[]" value="{{ $permission->id }}" {{ $isAdminRole || in_array($permission->id,$currentIds,true) ? 'checked' : '' }} {{ $isAdminRole ? 'disabled' : '' }}>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </form>
  </div>
@endforeach
@endsection
