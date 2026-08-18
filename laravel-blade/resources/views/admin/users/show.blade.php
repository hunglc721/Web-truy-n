{{-- resources/views/admin/users/show.blade.php --}}
@extends('layouts.admin')

@section('title', 'Chi tiết: ' . $user->name)
@section('breadcrumb', 'Thành viên / Chi tiết')

@section('topbar-actions')
  <a href="{{ route('admin.users.index') }}" class="topbar-btn topbar-btn-ghost">← Quay lại</a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">👤 Chi tiết thành viên</h1>
</div>

<div style="display:grid; grid-template-columns: 320px 1fr; gap:20px; align-items:start">

  {{-- ── Profile Card ── --}}
  <div class="admin-card">
    <div style="text-align:center; padding:8px 0 20px">
      <div style="
        width:80px; height:80px; border-radius:50%; margin:0 auto 14px;
        background: linear-gradient(135deg, {{ ($user->is_admin ?? false) ? '#6c63ff, #ff2a6d' : '#3b82f6, #06b6d4' }});
        display:flex; align-items:center; justify-content:center;
        font-size:32px; font-weight:800; color:#fff;
      ">{{ strtoupper(substr($user->name, 0, 1)) }}</div>

      <h2 style="font-size:18px; font-weight:800; color:var(--admin-text); margin-bottom:4px">{{ $user->name }}</h2>
      <p style="font-size:13px; color:var(--admin-text-muted)">{{ $user->email }}</p>

      <div style="margin-top:12px; display:flex; gap:8px; justify-content:center; flex-wrap:wrap">
        @if($user->is_admin ?? false)
          <span class="badge badge-primary">⚡ Admin</span>
        @else
          <span class="badge badge-muted">👤 User</span>
        @endif

        @if($user->banned_at)
          <span class="badge badge-danger">🔒 Bị khóa</span>
        @else
          <span class="badge badge-success">✅ Hoạt động</span>
        @endif
      </div>
    </div>

    <div style="border-top:1px solid var(--admin-border); padding-top:16px; display:flex; flex-direction:column; gap:10px">
      <div style="display:flex; justify-content:space-between; font-size:13px">
        <span style="color:var(--admin-text-muted)">Ngày tham gia</span>
        <span style="font-weight:600">{{ $user->created_at->format('d/m/Y') }}</span>
      </div>
      @if($user->banned_at)
      <div style="display:flex; justify-content:space-between; font-size:13px">
        <span style="color:var(--admin-text-muted)">Ngày bị khóa</span>
        <span style="font-weight:600; color:var(--admin-danger)">{{ $user->banned_at->format('d/m/Y') }}</span>
      </div>
      @endif
      <div style="display:flex; justify-content:space-between; font-size:13px">
        <span style="color:var(--admin-text-muted)">Hoạt động cuối</span>
        <span style="font-weight:600">{{ $user->updated_at->diffForHumans() }}</span>
      </div>
    </div>

    {{-- Quick Actions --}}
    @if($user->id !== auth()->id())
    <div style="margin-top:16px; border-top:1px solid var(--admin-border); padding-top:16px; display:flex; flex-direction:column; gap:8px">
      <form method="POST" action="{{ route('admin.users.toggleRole', $user) }}">
        @csrf @method('PATCH')
        <button type="submit" class="btn-admin btn-admin-ghost" style="width:100%; justify-content:center"
          onclick="return confirm('Thay đổi vai trò của {{ $user->name }}?')">
          {{ ($user->is_admin ?? false) ? '👤 Thu hồi quyền Admin' : '👑 Cấp quyền Admin' }}
        </button>
      </form>

      @if(!($user->is_admin ?? false))
      <form method="POST" action="{{ route('admin.users.toggleBan', $user) }}">
        @csrf @method('PATCH')
        <button type="submit" class="btn-admin {{ $user->banned_at ? 'btn-admin-success' : 'btn-admin-danger' }}" style="width:100%; justify-content:center"
          onclick="return confirm('{{ $user->banned_at ? 'Mở khóa' : 'Khóa' }} tài khoản {{ $user->name }}?')">
          {{ $user->banned_at ? '🔓 Mở khóa tài khoản' : '🔒 Khóa tài khoản' }}
        </button>
      </form>
      @endif
    </div>
    @endif
  </div>

  {{-- ── Stats + Activity ── --}}
  <div style="display:flex; flex-direction:column; gap:18px">

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:14px">
      @foreach([
        ['💬', 'Bình luận', $stats['comments_count'], 'primary'],
        ['📚', 'Thư viện', $stats['libraries_count'], 'warning'],
        ['⭐', 'Đánh giá', $stats['ratings_count'], 'success'],
        ['📖', 'Lịch sử đọc', $stats['history_count'], 'info'],
      ] as [$icon, $label, $count, $color])
      <div class="admin-stat-card">
        <div class="admin-stat-label">{{ $icon }} {{ $label }}</div>
        <div class="admin-stat-value {{ $color }}">{{ number_format($count) }}</div>
      </div>
      @endforeach
    </div>

    {{-- Recent Comments --}}
    @if($user->comments->isNotEmpty())
    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">💬 Bình luận gần đây</span>
        <span style="font-size:12px; color:var(--admin-text-muted)">10 gần nhất</span>
      </div>
      <div style="display:flex; flex-direction:column; gap:10px">
        @foreach($user->comments as $comment)
        <div style="padding:10px 12px; background:rgba(255,255,255,0.04); border-radius:8px; border:1px solid var(--admin-border)">
          <div style="display:flex; justify-content:space-between; margin-bottom:5px">
            <a href="{{ route('comics.show', $comment->comic->slug ?? '#') }}" style="font-size:12.5px; font-weight:600; color:var(--admin-primary); text-decoration:none">
              📖 {{ $comment->comic->title ?? 'Unknown' }}
            </a>
            <span style="font-size:11.5px; color:var(--admin-text-muted)">{{ $comment->created_at->diffForHumans() }}</span>
          </div>
          <p style="font-size:13px; color:var(--admin-text); margin:0">{{ Str::limit($comment->content, 120) }}</p>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Reading History --}}
    @if($user->readingHistories->isNotEmpty())
    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">📖 Lịch sử đọc gần đây</span>
      </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Truyện</th>
            <th>Chapter cuối đọc</th>
            <th>Thời gian</th>
          </tr>
        </thead>
        <tbody>
          @foreach($user->readingHistories as $history)
          <tr>
            <td style="font-weight:600; font-size:13px">{{ $history->comic->title ?? '—' }}</td>
            <td style="font-size:12.5px; color:var(--admin-text-muted)">{{ $history->chapter->title ?? '—' }}</td>
            <td style="font-size:12px; color:var(--admin-text-muted)">{{ $history->last_read_at?->diffForHumans() }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif

  </div>

</div>
@endsection
