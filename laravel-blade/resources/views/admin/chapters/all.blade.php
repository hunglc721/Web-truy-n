{{-- resources/views/admin/chapters/all.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Toàn bộ Chapter - WebComics')
@section('breadcrumb', 'Quản lý Chapter')

@section('topbar-actions')
  <button type="button" class="topbar-btn topbar-btn-primary" onclick="openNewChapterModal()">+ Đăng Chapter Mới</button>
@endsection

@push('styles')
<style>
  .admin-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px}
  .filter-bar{display:grid;grid-template-columns:2fr 1.5fr 1fr 1fr auto;gap:10px;align-items:end}
  .chapter-comic-cell{display:flex;align-items:center;gap:10px}
  .chapter-comic-thumb{width:36px;height:48px;border-radius:6px;object-fit:cover;border:1px solid var(--admin-border);flex-shrink:0}
  .comic-modal-select{width:100%;padding:11px 14px;background:rgba(255,255,255,.06);border:1px solid var(--admin-border);border-radius:9px;color:var(--admin-text);font-size:14px;outline:none}
  @media(max-width:1000px){.filter-bar{grid-template-columns:1fr 1fr}}
  @media(max-width:600px){.filter-bar{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="admin-page-header">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
    <div>
      <h1 class="admin-page-title">📖 Quản Lý Toàn Bộ Chapter</h1>
      <p class="admin-page-sub">Theo dõi, tìm kiếm và quản lý tất cả các chapter đã phát hành của mọi bộ truyện.</p>
    </div>
    <button type="button" class="btn-admin btn-admin-primary" onclick="openNewChapterModal()">
      ➕ Đăng Chapter Mới
    </button>
  </div>
</div>

{{-- Stats Row --}}
<div class="admin-stats-grid">
  <div class="admin-stat-card">
    <div class="admin-stat-label">📚 Tổng Chapter</div>
    <div class="admin-stat-value primary">{{ number_format($stats['total']) }}</div>
  </div>

  <div class="admin-stat-card">
    <div class="admin-stat-label">⚡ Sẵn Sàng (Ready)</div>
    <div class="admin-stat-value info">{{ number_format($stats['ready']) }}</div>
  </div>
</div>

{{-- Filter Card --}}
<div class="admin-card" style="margin-bottom:18px;padding:18px 20px;">
  <form method="GET" action="{{ route('admin.chapters.index') }}" class="filter-bar">
    <div>
      <label class="form-label" style="font-size:12px">Bộ truyện</label>
      <select name="comic_id" class="form-control">
        <option value="">— Tất cả bộ truyện —</option>
        @foreach($comics as $c)
          <option value="{{ $c->id }}" {{ request('comic_id') == $c->id ? 'selected' : '' }}>
            {{ $c->title }}
          </option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="form-label" style="font-size:12px">Tìm kiếm</label>
      <input type="text" name="q" class="form-control" placeholder="Tên hoặc số chapter..." value="{{ request('q') }}">
    </div>



    <div>
      <label class="form-label" style="font-size:12px">Trạng thái</label>
      <select name="status" class="form-control">
        <option value="all">Tất cả</option>
        <option value="ready" {{ request('status') === 'ready' ? 'selected' : '' }}>Ready</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
      </select>
    </div>

    <div style="display:flex;gap:6px">
      <button type="submit" class="btn-admin btn-admin-primary">🔍 Lọc</button>
      @if(request()->hasAny(['comic_id', 'q', 'status']))
        <a href="{{ route('admin.chapters.index') }}" class="btn-admin btn-admin-ghost" title="Xóa bộ lọc">✕</a>
      @endif
    </div>
  </form>
</div>

{{-- Main Table --}}
<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title">Danh sách Chapter</span>
    <span style="font-size:13px;color:var(--admin-text-muted)">
      Hiển thị {{ $chapters->count() }} / {{ $chapters->total() }} kết quả
    </span>
  </div>

  @if($chapters->isEmpty())
    <div style="text-align:center;padding:50px;color:var(--admin-text-muted)">
      <div style="font-size:48px;margin-bottom:12px">📭</div>
      <p style="font-size:15px;margin-bottom:16px">Không tìm thấy chapter nào phù hợp với điều kiện lọc.</p>
      <button type="button" class="btn-admin btn-admin-primary" onclick="openNewChapterModal()">
        ➕ Đăng Chapter Mới
      </button>
    </div>
  @else
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:240px">Bộ Truyện</th>
            <th style="width:90px;text-align:center">Chapter</th>
            <th>Tiêu đề Chapter</th>
            <th style="text-align:center">Số trang</th>

            <th style="text-align:center">Trạng thái</th>
            <th style="text-align:center">Lượt xem</th>
            <th>Ngày đăng</th>
            <th style="text-align:center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @foreach($chapters as $chapter)
            @php
              $comic = $chapter->comic;
            @endphp
            <tr>
              <td>
                <div class="chapter-comic-cell">
                  @if($comic && $comic->cover_image)
                    <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="chapter-comic-thumb" loading="lazy">
                  @else
                    <div class="chapter-comic-thumb" style="background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;font-size:16px">📖</div>
                  @endif
                  <div style="min-width:0">
                    <a href="{{ $comic ? route('admin.comics.chapters.index', $comic->id) : '#' }}" style="font-weight:700;color:var(--admin-text);text-decoration:none;font-size:13.5px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;" title="{{ $comic->title ?? 'N/A' }}">
                      {{ $comic->title ?? 'Không rõ bộ truyện' }}
                    </a>
                  </div>
                </div>
              </td>
              <td style="text-align:center">
                <span class="badge badge-primary" style="font-size:12px;font-weight:700">
                  Ch.{{ $chapter->chapter_number }}
                </span>
              </td>
              <td>
                @if($comic)
                  <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug]) }}" target="_blank" rel="noopener" style="font-weight:600;color:var(--admin-text);text-decoration:none;font-size:13.5px">
                    {{ $chapter->title ?: 'Chương ' . $chapter->chapter_number }}
                  </a>
                @else
                  <span>{{ $chapter->title ?: 'Chương ' . $chapter->chapter_number }}</span>
                @endif
              </td>
              <td style="text-align:center">
                <span class="badge badge-muted">{{ count($chapter->pages ?? []) }} trang</span>
              </td>

              <td style="text-align:center">
                @if(($chapter->processing_status ?? 'ready') === 'ready')
                  <span class="badge badge-success">✓ Sẵn sàng</span>
                @elseif($chapter->processing_status === 'pending')
                  <span class="badge badge-warning">⏳ Đang xử lý</span>
                @else
                  <span class="badge badge-danger">✕ Lỗi ảnh</span>
                @endif
              </td>
              <td style="text-align:center;color:var(--admin-text-muted);font-size:13px">
                {{ number_format($chapter->views) }}
              </td>
              <td style="white-space:nowrap;font-size:12px;color:var(--admin-text-muted)">
                {{ $chapter->created_at ? $chapter->created_at->format('d/m/Y H:i') : '—' }}
              </td>
              <td style="text-align:center">
                @if($comic)
                  <div style="display:flex;gap:5px;justify-content:center;flex-wrap:nowrap">
                    <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug]) }}" target="_blank" rel="noopener" class="btn-admin btn-admin-ghost btn-sm" title="Xem trên web">
                      👁️
                    </a>
                    <a href="{{ route('admin.comics.chapters.edit', [$comic->id, $chapter->id]) }}" class="btn-admin btn-admin-ghost btn-sm" title="Sửa chapter">
                      ✏️
                    </a>
                    <button type="button" class="btn-admin btn-admin-danger btn-sm" onclick="confirmDelete('{{ route('admin.comics.chapters.destroy', [$comic->id, $chapter->id]) }}', @js('Chapter ' . $chapter->chapter_number . ' - ' . $comic->title))" title="Xóa chapter">
                      🗑️
                    </button>
                  </div>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="pagination-wrap">
      {{ $chapters->links() }}
    </div>
  @endif
</div>

{{-- Modal Chọn Truyện để Đăng Chapter --}}
<div class="modal-overlay" id="new-chapter-modal">
  <div class="modal-box" style="text-align:left;max-width:440px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h3 class="modal-title" style="margin:0">➕ Chọn Bộ Truyện</h3>
      <button type="button" class="btn-admin btn-admin-ghost btn-sm" onclick="closeNewChapterModal()">✕</button>
    </div>
    <p class="modal-desc" style="margin-bottom:18px;text-align:left">
      Chọn bộ truyện bạn muốn đăng tải chương mới:
    </p>
    <div class="form-group" style="margin-bottom:20px">
      <label class="form-label">Bộ truyện <span>*</span></label>
      <select id="modal-comic-select" class="comic-modal-select">
        @foreach($comics as $c)
          <option value="{{ $c->id }}">{{ $c->title }}</option>
        @endforeach
      </select>
    </div>
    <div class="modal-actions" style="justify-content:flex-end">
      <button type="button" class="btn-admin btn-admin-ghost" onclick="closeNewChapterModal()">Hủy</button>
      <button type="button" class="btn-admin btn-admin-primary" onclick="proceedToCreateChapter()">Tiếp tục →</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const chapterModal = document.getElementById('new-chapter-modal');
  function openNewChapterModal() {
    chapterModal?.classList.add('show');
  }
  function closeNewChapterModal() {
    chapterModal?.classList.remove('show');
  }
  function proceedToCreateChapter() {
    const comicId = document.getElementById('modal-comic-select')?.value;
    if (comicId) {
      window.location.href = `/admin/comics/${comicId}/chapters/create`;
    }
  }
  chapterModal?.addEventListener('click', e => {
    if (e.target === chapterModal) closeNewChapterModal();
  });
</script>
@endpush
