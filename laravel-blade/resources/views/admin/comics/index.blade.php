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
  .comic-filter-bar{display:grid;grid-template-columns:2fr 1.3fr 1.3fr 1.1fr 1.1fr auto;gap:10px;align-items:end}
  @media(max-width:1150px){.comic-filter-bar{grid-template-columns:1fr 1fr}}
  @media(max-width:720px){.admin-modules-grid{grid-template-columns:1fr}}
  @media(max-width:600px){.comic-filter-bar{grid-template-columns:1fr}}

  /* Searchable Combobox */
  .searchable-dropdown { position: relative; width: 100%; }
  .searchable-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 10px 12px;
    background: rgba(255, 255, 255, .05);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    color: var(--admin-text);
    font-family: inherit;
    font-size: 13.5px;
    cursor: pointer;
    text-align: left;
    user-select: none;
    height: 42px;
    box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s, background .15s;
  }
  .searchable-trigger:hover {
    border-color: rgba(108, 99, 255, .4);
    background: rgba(255, 255, 255, .07);
  }
  .searchable-dropdown.open .searchable-trigger {
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, .2);
    background: rgba(255, 255, 255, .08);
  }
  .searchable-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-right: 6px;
    flex: 1;
  }
  .searchable-arrow {
    font-size: 11px;
    color: var(--admin-text-muted);
    transition: transform .2s ease;
    flex-shrink: 0;
  }
  .searchable-dropdown.open .searchable-arrow {
    transform: rotate(180deg);
    color: var(--admin-primary);
  }
  .searchable-menu {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    min-width: 220px;
    background: #181b26;
    border: 1px solid rgba(255, 255, 255, .14);
    border-radius: 10px;
    box-shadow: 0 12px 36px rgba(0, 0, 0, .75);
    z-index: 300;
    padding: 8px;
  }
  .searchable-dropdown.open .searchable-menu {
    display: block;
    animation: searchDropFade .15s ease;
  }
  @keyframes searchDropFade {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .searchable-search-wrapper {
    position: relative;
    margin-bottom: 6px;
  }
  .searchable-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    pointer-events: none;
    opacity: .7;
  }
  .searchable-search-input {
    width: 100%;
    padding: 7px 28px 7px 30px;
    background: rgba(255, 255, 255, .07);
    border: 1px solid var(--admin-border);
    border-radius: 6px;
    color: var(--admin-text);
    font-family: inherit;
    font-size: 12.5px;
    outline: none;
    box-sizing: border-box;
  }
  .searchable-search-input:focus {
    border-color: var(--admin-primary);
    background: rgba(255, 255, 255, .11);
  }
  .searchable-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--admin-text-muted);
    font-size: 13px;
    cursor: pointer;
    padding: 2px 4px;
    line-height: 1;
  }
  .searchable-search-clear:hover {
    color: var(--admin-text);
  }
  .searchable-options-list {
    max-height: 220px;
    overflow-y: auto;
    padding-right: 2px;
  }
  .searchable-options-list::-webkit-scrollbar {
    width: 5px;
  }
  .searchable-options-list::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, .18);
    border-radius: 3px;
  }
  .searchable-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 10px;
    border-radius: 6px;
    font-size: 13px;
    color: var(--admin-text);
    cursor: pointer;
    transition: background .12s;
  }
  .searchable-option:hover {
    background: rgba(108, 99, 255, .15);
    color: #fff;
  }
  .searchable-option.selected {
    background: rgba(108, 99, 255, .25);
    color: #a59eff;
    font-weight: 600;
  }
  .searchable-check {
    font-size: 12px;
    opacity: 0;
    margin-left: 6px;
  }
  .searchable-option.selected .searchable-check {
    opacity: 1;
    color: var(--admin-primary);
  }
  .searchable-empty {
    padding: 16px 10px;
    text-align: center;
    font-size: 12.5px;
    color: var(--admin-text-muted);
  }
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

    {{-- Filter Card --}}
    <div class="admin-card" style="padding:18px 20px;">
      <form method="GET" action="{{ route('admin.comics.index') }}" class="comic-filter-bar">
        <div>
          <label class="form-label" style="font-size:12px;margin-bottom:5px">🔍 Tìm kiếm</label>
          <input type="text" name="q" class="form-control" placeholder="Tên hoặc slug truyện..." value="{{ request('q') }}">
        </div>

        <div>
          <label class="form-label" style="font-size:12px;margin-bottom:5px">🏷️ Thể loại</label>
          @php
            $currentGenre = $genres->firstWhere('id', request('genre_id'));
            $selectedGenreLabel = $currentGenre ? $currentGenre->name : '— Tất cả thể loại —';
          @endphp
          <div class="searchable-dropdown" id="genre-searchable-dropdown">
            <select name="genre_id" class="searchable-native-select" style="display:none" tabindex="-1">
              <option value="all">— Tất cả thể loại —</option>
              @foreach($genres as $g)
                <option value="{{ $g->id }}" {{ request('genre_id') == $g->id ? 'selected' : '' }}>
                  {{ $g->name }}
                </option>
              @endforeach
            </select>
            <button type="button" class="searchable-trigger" aria-haspopup="listbox" aria-expanded="false" title="Lọc theo thể loại">
              <span class="searchable-label">{{ $selectedGenreLabel }}</span>
              <span class="searchable-arrow">▾</span>
            </button>
            <div class="searchable-menu" role="listbox">
              <div class="searchable-search-wrapper">
                <span class="searchable-search-icon">🔍</span>
                <input type="text" class="searchable-search-input" placeholder="Tìm thể loại..." autocomplete="off" />
                <button type="button" class="searchable-search-clear" style="display:none" title="Xóa tìm kiếm">✕</button>
              </div>
              <div class="searchable-options-list">
                <div class="searchable-option {{ !request('genre_id') || request('genre_id') === 'all' ? 'selected' : '' }}" data-value="all">
                  <span class="searchable-option-text">— Tất cả thể loại —</span>
                  <span class="searchable-check">✓</span>
                </div>
                @foreach($genres as $g)
                  <div class="searchable-option {{ request('genre_id') == $g->id ? 'selected' : '' }}" data-value="{{ $g->id }}">
                    <span class="searchable-option-text">{{ $g->name }}</span>
                    <span class="searchable-check">✓</span>
                  </div>
                @endforeach
                <div class="searchable-empty" style="display:none">Không tìm thấy thể loại nào</div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="form-label" style="font-size:12px;margin-bottom:5px">✍️ Tác giả</label>
          @php
            $currentAuthor = $authors->firstWhere('id', request('author_id'));
            $selectedAuthorLabel = $currentAuthor ? $currentAuthor->name : '— Tất cả tác giả —';
          @endphp
          <div class="searchable-dropdown" id="author-searchable-dropdown">
            <select name="author_id" class="searchable-native-select" style="display:none" tabindex="-1">
              <option value="all">— Tất cả tác giả —</option>
              @foreach($authors as $a)
                <option value="{{ $a->id }}" {{ request('author_id') == $a->id ? 'selected' : '' }}>
                  {{ $a->name }}
                </option>
              @endforeach
            </select>
            <button type="button" class="searchable-trigger" aria-haspopup="listbox" aria-expanded="false" title="Lọc theo tác giả">
              <span class="searchable-label">{{ $selectedAuthorLabel }}</span>
              <span class="searchable-arrow">▾</span>
            </button>
            <div class="searchable-menu" role="listbox">
              <div class="searchable-search-wrapper">
                <span class="searchable-search-icon">🔍</span>
                <input type="text" class="searchable-search-input" placeholder="Tìm tác giả..." autocomplete="off" />
                <button type="button" class="searchable-search-clear" style="display:none" title="Xóa tìm kiếm">✕</button>
              </div>
              <div class="searchable-options-list">
                <div class="searchable-option {{ !request('author_id') || request('author_id') === 'all' ? 'selected' : '' }}" data-value="all">
                  <span class="searchable-option-text">— Tất cả tác giả —</span>
                  <span class="searchable-check">✓</span>
                </div>
                @foreach($authors as $a)
                  <div class="searchable-option {{ request('author_id') == $a->id ? 'selected' : '' }}" data-value="{{ $a->id }}">
                    <span class="searchable-option-text">{{ $a->name }}</span>
                    <span class="searchable-check">✓</span>
                  </div>
                @endforeach
                <div class="searchable-empty" style="display:none">Không tìm thấy tác giả nào</div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="form-label" style="font-size:12px;margin-bottom:5px">🚦 Trạng thái</label>
          <select name="status" class="form-control">
            <option value="all">— Tất cả —</option>
            <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>🟢 Đang ra</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>🔵 Hoàn thành</option>
            <option value="hiatus" {{ request('status') === 'hiatus' ? 'selected' : '' }}>🟡 Tạm ngưng</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>🔴 Đã hủy</option>
          </select>
        </div>

        <div>
          <label class="form-label" style="font-size:12px;margin-bottom:5px">⚡ Sắp xếp</label>
          <select name="sort" class="form-control">
            <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
            <option value="views" {{ request('sort') === 'views' ? 'selected' : '' }}>Lượt xem cao</option>
            <option value="chapters" {{ request('sort') === 'chapters' ? 'selected' : '' }}>Nhiều chapter</option>
            <option value="title" {{ request('sort') === 'title' ? 'selected' : '' }}>Tên A-Z</option>
          </select>
        </div>

        <div style="display:flex;gap:6px">
          <button type="submit" class="btn-admin btn-admin-primary" style="white-space:nowrap">🔍 Lọc</button>
          @if(request()->hasAny(['q', 'genre_id', 'author_id', 'status', 'sort']))
            <a href="{{ route('admin.comics.index') }}" class="btn-admin btn-admin-ghost" title="Xóa toàn bộ bộ lọc">✕</a>
          @endif
        </div>
      </form>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <span class="admin-card-title">📖 Danh sách Bộ Truyện</span>
        <span style="font-size:13px;color:var(--admin-text-muted)">Hiện {{ $comics->count() }} / {{ $comics->total() }} kết quả</span>
      </div>

      @if($comics->isEmpty())
        <div style="text-align:center;padding:48px;color:var(--admin-text-muted)">
          <div style="font-size:48px;margin-bottom:12px">🔍</div>
          <p style="font-size:15px;font-weight:600;color:var(--admin-text);margin-bottom:6px">Không tìm thấy bộ truyện nào phù hợp.</p>
          <p style="font-size:13px">Thử thay đổi từ khóa tìm kiếm hoặc điều chỉnh lại bộ lọc.</p>
          @if(request()->hasAny(['q', 'genre_id', 'author_id', 'status', 'sort']))
            <div style="margin-top:14px">
              <a href="{{ route('admin.comics.index') }}" class="btn-admin btn-admin-ghost btn-sm">✕ Xóa bộ lọc</a>
            </div>
          @endif
        </div>
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
                    @if($comic->cover_url)
                      <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" style="width:40px;height:54px;object-fit:cover;border-radius:6px;border:1px solid var(--admin-border)" loading="lazy" />
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
            <img src="{{ $top->cover_url }}" alt="{{ $top->title }}" style="width:34px;height:46px;border-radius:5px;object-fit:cover;border:1px solid var(--admin-border)" loading="lazy" />
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  function removeVietnameseTones(str) {
    if (!str) return '';
    str = str.toLowerCase();
    str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, 'a');
    str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, 'e');
    str = str.replace(/ì|í|ị|ỉ|ĩ/g, 'i');
    str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, 'o');
    str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, 'u');
    str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, 'y');
    str = str.replace(/đ/g, 'd');
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  document.querySelectorAll('.searchable-dropdown').forEach(function(dropdown) {
    const trigger = dropdown.querySelector('.searchable-trigger');
    const label = dropdown.querySelector('.searchable-label');
    const searchInput = dropdown.querySelector('.searchable-search-input');
    const clearBtn = dropdown.querySelector('.searchable-search-clear');
    const options = dropdown.querySelectorAll('.searchable-option');
    const emptyMsg = dropdown.querySelector('.searchable-empty');
    const nativeSelect = dropdown.querySelector('.searchable-native-select');

    trigger.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const isOpen = dropdown.classList.contains('open');
      document.querySelectorAll('.searchable-dropdown.open').forEach(function(d) {
        if (d !== dropdown) {
          d.classList.remove('open');
          d.querySelector('.searchable-trigger')?.setAttribute('aria-expanded', 'false');
        }
      });
      if (!isOpen) {
        dropdown.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
        setTimeout(function() {
          searchInput.focus();
        }, 50);
      } else {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
      }
    });

    dropdown.querySelector('.searchable-menu').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    searchInput.addEventListener('input', function() {
      const q = removeVietnameseTones(this.value.trim());
      clearBtn.style.display = this.value ? 'block' : 'none';
      let visibleCount = 0;

      options.forEach(function(opt) {
        const text = removeVietnameseTones(opt.textContent || '');
        if (!q || text.includes(q)) {
          opt.style.display = 'flex';
          visibleCount++;
        } else {
          opt.style.display = 'none';
        }
      });

      emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    });

    clearBtn.addEventListener('click', function() {
      searchInput.value = '';
      searchInput.dispatchEvent(new Event('input'));
      searchInput.focus();
    });

    options.forEach(function(opt) {
      opt.addEventListener('click', function() {
        const value = this.getAttribute('data-value');
        const textSpan = this.querySelector('.searchable-option-text');
        const text = textSpan ? textSpan.textContent.trim() : this.textContent.trim();

        options.forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');

        label.textContent = text;

        if (nativeSelect) {
          nativeSelect.value = value;
          nativeSelect.dispatchEvent(new Event('change'));
        }

        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');

        const form = dropdown.closest('form');
        if (form) {
          form.submit();
        }
      });
    });

    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const firstVisible = Array.from(options).find(o => o.style.display !== 'none');
        if (firstVisible) {
          firstVisible.click();
        }
      } else if (e.key === 'Escape') {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.focus();
      }
    });
  });

  document.addEventListener('click', function() {
    document.querySelectorAll('.searchable-dropdown.open').forEach(function(d) {
      d.classList.remove('open');
      d.querySelector('.searchable-trigger')?.setAttribute('aria-expanded', 'false');
    });
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.searchable-dropdown.open').forEach(function(d) {
        d.classList.remove('open');
        d.querySelector('.searchable-trigger')?.setAttribute('aria-expanded', 'false');
      });
    }
  });
});
</script>
@endpush
