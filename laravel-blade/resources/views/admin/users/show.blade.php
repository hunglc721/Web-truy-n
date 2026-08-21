@extends('layouts.admin')

@section('title', 'Chi tiết: ' . $user->name)
@section('breadcrumb', 'Thành viên / Chi tiết')

@section('topbar-actions')<a href="{{ route('admin.users.index') }}" class="topbar-btn topbar-btn-ghost">← Quay lại</a>@endsection

@push('styles')
<style>.user-detail-grid{display:grid;grid-template-columns:320px minmax(0,1fr);gap:20px;align-items:start}.user-stats-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}@media(max-width:850px){.user-detail-grid{grid-template-columns:1fr}.user-stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:480px){.user-stats-grid{grid-template-columns:1fr}}</style>
@endpush

@section('content')
@php $roleSlug=$user->roleSlug(); $roleLabel=$user->role?->name ?? ($user->isAdmin()?'Administrator':'Member'); @endphp
<div class="admin-page-header"><h1 class="admin-page-title">👤 Chi tiết thành viên</h1><p class="admin-page-sub">Thông tin tài khoản, vai trò và hoạt động gần đây.</p></div>
<div class="user-detail-grid">
  <div class="admin-card">
    <div style="text-align:center;padding:8px 0 20px"><div style="width:80px;height:80px;border-radius:50%;margin:0 auto 14px;background:linear-gradient(135deg,#6c63ff,#ff2a6d);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;color:#fff">{{ strtoupper(substr($user->name,0,1)) }}</div><h2 style="font-size:18px;font-weight:800">{{ $user->name }}</h2><p style="font-size:13px;color:var(--admin-text-muted);margin-top:4px">{{ $user->email }}</p><div style="margin-top:12px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap"><span class="badge badge-primary">{{ $roleLabel }}</span>@if($user->banned_at)<span class="badge badge-danger">🔒 Bị khóa</span>@else<span class="badge badge-success">✅ Hoạt động</span>@endif</div></div>
    <div style="border-top:1px solid var(--admin-border);padding-top:16px;display:grid;gap:9px;font-size:13px"><div style="display:flex;justify-content:space-between"><span style="color:var(--admin-text-muted)">Ngày tham gia</span><strong>{{ $user->created_at?->format('d/m/Y') }}</strong></div>@if($user->banned_at)<div style="display:flex;justify-content:space-between"><span style="color:var(--admin-text-muted)">Ngày bị khóa</span><strong style="color:var(--admin-danger)">{{ $user->banned_at->format('d/m/Y H:i') }}</strong></div>@endif<div style="display:flex;justify-content:space-between"><span style="color:var(--admin-text-muted)">Cập nhật cuối</span><strong>{{ $user->updated_at?->diffForHumans() }}</strong></div></div>

    @if($user->id !== auth()->id())
      <div style="margin-top:16px;border-top:1px solid var(--admin-border);padding-top:16px;display:grid;gap:8px">
        @if(auth()->user()->hasPermission('users.manage_role'))
          <form method="POST" action="{{ route('admin.users.updateRole',$user) }}">@csrf @method('PATCH')<label class="form-label">Vai trò</label><div style="display:flex;gap:6px"><select class="form-control" name="role">@foreach(\App\Models\Role::orderBy('name')->get() as $role)<option value="{{ $role->slug }}" {{ $roleSlug===$role->slug?'selected':'' }}>{{ $role->name }}</option>@endforeach</select><button class="btn-admin btn-admin-primary">Lưu</button></div></form>
        @endif
        @if(auth()->user()->hasPermission('users.ban') && !$user->isAdmin())<form method="POST" action="{{ route('admin.users.toggleBan',$user) }}">@csrf @method('PATCH')<button class="btn-admin {{ $user->banned_at?'btn-admin-success':'btn-admin-danger' }}" style="width:100%;justify-content:center" onclick="return confirm('{{ $user->banned_at?'Mở khóa':'Khóa' }} tài khoản này?')">{{ $user->banned_at?'🔓 Mở khóa tài khoản':'🔒 Khóa tài khoản' }}</button></form>@endif
      </div>
    @endif
  </div>

  <div style="display:grid;gap:18px">
    <div class="user-stats-grid">@foreach([['💬','Bình luận',$stats['comments_count']],['📚','Thư viện',$stats['libraries_count']],['⭐','Đánh giá',$stats['ratings_count']],['📖','Lịch sử đọc',$stats['history_count']]] as [$icon,$label,$count])<div class="admin-stat-card"><div class="admin-stat-label">{{ $icon }} {{ $label }}</div><div class="admin-stat-value primary">{{ number_format($count) }}</div></div>@endforeach</div>
    @if($user->comments->isNotEmpty())<div class="admin-card"><div class="admin-card-header"><span class="admin-card-title">💬 Bình luận gần đây</span></div><div style="display:grid;gap:10px">@foreach($user->comments as $comment)<div style="padding:10px 12px;background:rgba(255,255,255,.04);border-radius:8px;border:1px solid var(--admin-border)"><div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:5px"><span style="font-size:12.5px;font-weight:700;color:var(--admin-primary)">{{ $comment->comic->title ?? 'Truyện đã xóa' }}</span><span style="font-size:11px;color:var(--admin-text-muted)">{{ $comment->created_at?->diffForHumans() }}</span></div><p style="font-size:13px">{{ Str::limit($comment->content,140) }}</p></div>@endforeach</div></div>@endif
    @if($user->readingHistories->isNotEmpty())<div class="admin-card"><div class="admin-card-header"><span class="admin-card-title">📖 Lịch sử đọc gần đây</span></div><div style="overflow-x:auto"><table class="admin-table" style="min-width:600px"><thead><tr><th>Truyện</th><th>Chapter cuối</th><th>Tiến độ</th><th>Thời gian</th></tr></thead><tbody>@foreach($user->readingHistories as $history)<tr><td>{{ $history->comic->title ?? '—' }}</td><td>{{ $history->chapter->label ?? '—' }}</td><td>{{ round($history->scroll_percent ?? 0) }}%</td><td>{{ $history->last_read_at?->diffForHumans() }}</td></tr>@endforeach</tbody></table></div></div>@endif
  </div>
</div>
@endsection
