{{-- resources/views/admin/comics/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Truyện - WebComics')
@section('breadcrumb', 'Quản lý Truyện')

@section('topbar-actions')
  <a href="{{ route('admin.comics.create') }}" class="topbar-btn topbar-btn-primary">+ Đăng Bộ Truyện Mới</a>
@endsection

@push('styles')
<style>
  .admin-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
  .admin-stat-value.success{color:var(--admin-success)}
  .admin-stat-value.warning{color:var(--admin-warning)}
  .admin-stat-value.info{color:var(--admin-info)}
  .admin-modules-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  .admin-module-card{display:flex;align-items:center;gap:12px;padding:14px;border:1px solid var(--admin-border);border-radius:10px;background:rgba(255,255,255,.025);text-decoration:none;transition:.18s}
  .admin-module-card:hover{border-color:rgba(108,99,255,.38);background:rgba(108,99,255,.07);transform:translateY(-1px)}
  .admin-module-icon{width:42px;height:42px;display:flex;align-items:center;justify-content:center;border-radius:10px;font-size:20px;flex-shrink:0}
  .admin-module-info{min-width:0;flex:1}.admin-module-info h3{font-size:13.5px;color:var(--admin-text);margin-bottom:3px}.admin-module-info p{font-size:11.5px;color:var(--admin-text-muted);line-height:1.4}
  .admin-module-arrow{color:var(--admin-text-muted);font-size:18px}
  @media(max-width:720px){.admin-modules-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="admin-page-header">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
    <div>
      <h1 class="admin-page-title">📚 Quản Lý Bộ Truyện</h1>
      <p class="admin-page-sub">Quản lý truyện, chapter và các dữ liệu nội dung đang chạy thật trên WebComics.</p>
    </div>
    <a href="{{ route('admin.comics.create') }}" class="btn-admin btn-admin-primary">➕ Đăng Bộ Truyện Mới</a>
  </div>
</div>

<div class="dashboard-grid">
  <div class="col-main-8" style="display:flex;flex-direction:column;gap:20px;">
    <div class="admin-stats-grid">
      <div class="admin-stat-card"><div class="admin-stat-label">📚 Tổng Truyện</div><div class="admin-stat-value primary">{{ $comics->total() }}</div></div>
      <a href="{{ route('admin.chapters.index') }}" class="admin-stat-card" style="text-decoration:none;color:inherit"><div class="admin-stat-label">📖 Tổng Chapter</div><div class="admin-stat-value success">{{ number_format(\App\Models\Chapter::count()) }}</div></a>
      <div class="admin-stat-card"><div class="admin-stat-label">👥 Thành Viên</div><div class="admin-stat-value warning">{{ number_format(\App\Models\User::count()) }}</div></div>
      <div class="admin-stat-card"><div class="admin-stat-label">🏷️ Thể Loại</div><div class="admin-stat-value info">{{ \App\Models\Genre::count() }}</div></div>
    </div>

    <div class="admin-card">
      <div class="admin-card-header"><span class="admin-card-title">⚡ Quản lý nhanh</span></div>
      <div class="admin-modules-grid">
        <a href="{{ route('admin.genres.index') }}" class="admin-module-card"><div class="admin-module-icon" style="background:rgba(108,99,255,.15)">📚</div><div class="admin-module-info"><h3>Thể loại</h3><p>Thêm, sửa, xóa thể loại truyện</p></div><span class="admin-module-arrow">→</span></a>
        <a href="{{ route('admin.tags.index') }}" class="admin-module-card"><div class="admin-module-icon" style="background:rgba(236,72,153,.15)">🏷️</div><div class="admin-module-info"><h3>Tags</h3><p>Quản lý nhãn cho truyện</p></div><span class="admin-module-arrow">→</span></a>
        <a href="{{ route('admin.authors.index') }}" class="admin-module-card"><div class="admin-module-icon" style="background:rgba(34,197,94,.15)">✍️</div><div class="admin-module-info"><h3>Tác giả</h3><p>Quản lý tác giả và họa sĩ</p></div><span class="admin-module-arrow">→</span></a>
        <a href="{{ route('admin.users.index') }}" class="admin-module-card"><div class="admin-module-icon" style="background:rgba(59,130,246,.15)">👥</div><div class="admin-module-info"><h3>Thành viên</h3><p>Phân quyền và khóa tài khoản</p></div><span class="admin-module-arrow">→</span></a>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">📖 Danh sách Bộ Truyện</span>
        <span style="font-size:13px;color:var(--admin-text-muted)">Hiện {{ $comics->count() }} / {{ $comics->total() }} kết quả</span>
      </div>

      @if($comics->isEmpty())
        <div style="text-align:center;padding:48px;color:var(--admin-text-muted)"><div style="font-size:48px;margin-bottom:12px">📭</div><p>Chưa có bộ truyện nào trên hệ thống.</p></div>
      @else
        <div style="overflow-x:auto">
          <table class="admin-table">
            <thead><tr><th style="width:60px">Bìa</th><th>Tên Bộ Truyện</th><th style="text-align:center">Trạng Thái</th><th style="text-align:center">Số Chapter</th><th style="text-align:center">Lượt Xem</th><th style="text-align:center">Thao tác</th></tr></thead>
            <tbody>
              @foreach($comics as $comic)
                @php
                  $statusMeta = match($comic->status) {
                    'ongoing' => ['badge-success', '🟢 ĐANG RA'],
                    'completed' => ['badge-info', '🔵 HOÀN THÀNH'],
                    'hiatus' => ['badge-warning', '🟡 TẠM NGƯNG'],
                    'cancelled' => ['badge-danger', '🔴 ĐÃ HỦY'],
                    default => ['badge-muted', strtoupper((string) $comic->status)],
                  };
                @endphp
                <tr>
                  <td>
                    @if($comic->cover_image)
                      <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" style="width:40px;height:54px;object-fit:cover;border-radius:6px;border:1px solid var(--admin-border)" loading="lazy" />
                    @else
                      <div style="width:40px;height:54px;background:rgba(255,255,255,.06);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px">📖</div>
                    @endif
                  </td>
                  <td><a href="{{ route('comics.show', $comic->slug) }}" target="_blank" rel="noopener" style="font-weight:600;color:var(--admin-text);text-decoration:none;font-size:14px">{{ $comic->title }}</a></td>
                  <td style="text-align:center"><span class="badge {{ $statusMeta[0] }}">{{ $statusMeta[1] }}</span></td>
                  <td style="text-align:center"><a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="badge badge-primary" style="text-decoration:none" title="Xem danh sách Chapter">📖 {{ $comic->chapters_count }} chaps</a></td>
                  <td style="text-align:center;font-size:13px;color:var(--admin-text-muted)">{{ number_format($comic->views) }}</td>
                  <td style="text-align:center">
                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                      <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="btn-admin btn-admin-ghost btn-sm" title="Quản lý Chapters">📑 Chapters</a>
                      <a href="{{ route('admin.comics.chapters.create', $comic->id) }}" class="btn-admin btn-admin-primary btn-sm" title="Đăng Chapter Mới">➕ Chap</a>
                      <a href="{{ route('admin.comics.edit', $comic->id) }}" class="btn-admin btn-admin-ghost btn-sm" title="Sửa thông tin truyện">✏️ Sửa</a>
                      <button type="button" class="btn-admin btn-admin-danger btn-sm" onclick="confirmDelete('{{ route('admin.comics.destroy', $comic->id) }}', @js('Bộ truyện: '.$comic->title))">🗑️ Xóa</button>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="pagination-wrap">{{ $comics->links() }}</div>
      @endif
    </div>
  </div>

  <div class="col-sidebar-4" style="display:flex;flex-direction:column;gap:20px;">
    <div class="admin-card">
      <div class="admin-card-header" style="margin-bottom:14px;padding-bottom:10px"><span class="admin-card-title" style="font-size:14.5px">🔥 Top Truyện Xem Nhiều</span><span style="font-size:11px;color:var(--admin-text-muted)">Dữ liệu DB</span></div>
      <div style="display:flex;flex-direction:column;gap:10px">
        @php $topComics = \App\Models\Comic::orderByDesc('views')->take(5)->get(); @endphp
        @forelse($topComics as $index => $top)
          <div class="widget-item">
            <span class="rank-badge {{ $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : 'rank-other')) }}">{{ $index + 1 }}</span>
            <img src="{{ $top->cover_image }}" alt="{{ $top->title }}" style="width:34px;height:46px;border-radius:5px;object-fit:cover;border:1px solid var(--admin-border)" loading="lazy" />
            <div style="flex:1;min-width:0"><a href="{{ route('comics.show', $top->slug) }}" target="_blank" rel="noopener" style="font-size:13px;font-weight:700;color:var(--admin-text);text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">{{ $top->title }}</a><div style="font-size:11.5px;color:var(--admin-text-muted)">👁️ {{ number_format($top->views) }} lượt xem</div></div>
          </div>
        @empty
          <p style="font-size:12.5px;color:var(--admin-text-muted);text-align:center;padding:10px 0">Chưa có dữ liệu</p>
        @endforelse
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-header" style="margin-bottom:14px;padding-bottom:10px"><span class="admin-card-title" style="font-size:14.5px">💬 Bình Luận Mới</span><a href="{{ route('admin.comments.index') }}" style="font-size:11px;color:var(--admin-primary);text-decoration:none">Kiểm duyệt →</a></div>
      <div style="display:flex;flex-direction:column;gap:12px">
        @php $recentComments = \App\Models\Comment::with(['user', 'comic'])->orderByDesc('created_at')->take(4)->get(); @endphp
        @forelse($recentComments as $cmt)
          <div style="padding-bottom:10px;border-bottom:1px solid var(--admin-border)">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px"><strong style="color:var(--admin-primary)">{{ $cmt->user->name ?? 'Người dùng' }}</strong><span style="color:var(--admin-text-muted)">{{ $cmt->created_at->diffForHumans() }}</span></div>
            <p style="font-size:12.5px;color:var(--admin-text);margin:0;line-height:1.4">“{{ Str::limit($cmt->content, 65) }}”</p>
            <p style="font-size:11px;color:var(--admin-text-muted);margin-top:3px">Tại: {{ $cmt->comic->title ?? 'Truyện' }}</p>
          </div>
        @empty
          <p style="font-size:12.5px;color:var(--admin-text-muted);text-align:center;padding:10px 0">Chưa có bình luận nào</p>
        @endforelse
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-header" style="margin-bottom:14px;padding-bottom:10px"><span class="admin-card-title" style="font-size:14.5px">🧭 Vận Hành Hệ Thống</span><span style="font-size:11px;color:var(--admin-success);font-weight:700">Laravel runtime</span></div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <a href="{{ route('admin.analytics.index') }}" class="btn-admin btn-admin-ghost" style="justify-content:flex-start">📈 Xem Analytics</a>
        <a href="{{ route('admin.logs.index') }}" class="btn-admin btn-admin-ghost" style="justify-content:flex-start">📜 Xem Audit Logs</a>
        <a href="{{ route('admin.settings.index') }}" class="btn-admin btn-admin-ghost" style="justify-content:flex-start">⚙️ Cài Đặt Website</a>
      </div>
    </div>
  </div>
</div>
@endsection
