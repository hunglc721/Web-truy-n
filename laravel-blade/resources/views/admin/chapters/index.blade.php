{{-- resources/views/admin/chapters/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Chapter — ' . $comic->title)
@section('breadcrumb', 'Truyện / ' . $comic->title . ' / Danh sách Chapter')

@section('topbar-actions')
  <a href="{{ route('admin.comics.index') }}" class="topbar-btn topbar-btn-ghost">← Danh sách truyện</a>
  <a href="{{ route('admin.comics.chapters.create', $comic->id) }}" class="topbar-btn topbar-btn-primary">+ Đăng Chapter Mới</a>
@endsection

@section('content')
<div class="admin-page-header">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px">
    <div style="display:flex; align-items:center; gap:14px">
      @if($comic->cover_image)
        <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" style="width:48px; height:64px; border-radius:8px; object-fit:cover; border:1px solid var(--admin-border)" />
      @endif
      <div>
        <h1 class="admin-page-title">📖 Quản lý Chapter: {{ $comic->title }}</h1>
        <p class="admin-page-sub">Tổng số {{ $chapters->total() }} chương đã đăng</p>
      </div>
    </div>
    <a href="{{ route('admin.comics.chapters.create', $comic->id) }}" class="btn-admin btn-admin-primary" style="padding:10px 20px">
      ➕ Đăng Chapter Mới
    </a>
  </div>
</div>

{{-- Stats Row --}}
<div class="admin-stats-grid" style="margin-bottom:20px">
  <div class="admin-stat-card">
    <div class="admin-stat-label">📚 Tổng Chapter</div>
    <div class="admin-stat-value primary">{{ $chapters->total() }}</div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-label">🖼️ Tổng Trang Ảnh</div>
    <div class="admin-stat-value success">
      {{ number_format($comic->chapters->sum(fn($ch) => count($ch->pages ?? []))) }}
    </div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-label">👁️ Tổng Lượt Xem</div>
    <div class="admin-stat-value info">
      {{ number_format($comic->chapters->sum('views')) }}
    </div>
  </div>
  <div class="admin-stat-card">
    <div class="admin-stat-label">🚀 Chương mới nhất</div>
    <div class="admin-stat-value warning">
      Ch.{{ $comic->chapters->max('chapter_number') ?? 0 }}
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title">Danh sách Chapter</span>
    <span style="font-size:13px; color:var(--admin-text-muted)">
      Hiển thị {{ $chapters->count() }} / {{ $chapters->total() }} kết quả
    </span>
  </div>

  @if($chapters->isEmpty())
    <div style="text-align:center; padding:50px; color:var(--admin-text-muted)">
      <div style="font-size:48px; margin-bottom:12px">📭</div>
      <p style="font-size:15px; margin-bottom:16px">Bộ truyện này chưa có chapter nào.</p>
      <a href="{{ route('admin.comics.chapters.create', $comic->id) }}" class="btn-admin btn-admin-primary">
        ➕ Đăng Chapter Đầu Tiên
      </a>
    </div>
  @else
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:70px">Chapter</th>
            <th>Tên Chapter</th>
            <th style="text-align:center">Số trang</th>
            <th style="text-align:center">Quyền đọc</th>
            <th style="text-align:center">Lượt xem</th>
            <th>Ngày đăng</th>
            <th style="text-align:center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @foreach($chapters as $chapter)
          <tr>
            <td>
              <span class="badge badge-primary" style="font-size:12.5px; font-weight:700">
                Ch.{{ $chapter->chapter_number }}
              </span>
            </td>
            <td>
              <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug]) }}" target="_blank" style="font-weight:600; color:var(--admin-text); text-decoration:none">
                {{ $chapter->title }}
              </a>
            </td>
            <td style="text-align:center">
              <span class="badge badge-info">
                {{ count($chapter->pages ?? []) }} trang
              </span>
            </td>
            <td style="text-align:center">
              @if($chapter->is_free)
                <span class="badge badge-success">✅ Miễn phí</span>
              @else
                <span class="badge badge-warning">🔒 Trả phí</span>
              @endif
            </td>
            <td style="text-align:center; font-size:13px; color:var(--admin-text-muted)">
              {{ number_format($chapter->views) }}
            </td>
            <td style="font-size:12.5px; color:var(--admin-text-muted)">
              {{ $chapter->published_at?->format('d/m/Y H:i') ?? 'N/A' }}
            </td>
            <td style="text-align:center">
              <div style="display:flex; gap:6px; justify-content:center">
                {{-- Xem trực tiếp trên user site --}}
                <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug]) }}" target="_blank" class="btn-admin btn-admin-ghost btn-sm" title="Xem trên Web">
                  👁
                </a>

                {{-- Sửa --}}
                <a href="{{ route('admin.comics.chapters.edit', [$comic->id, $chapter->id]) }}" class="btn-admin btn-admin-ghost btn-sm" title="Chỉnh sửa">
                  ✏️ Sửa
                </a>

                {{-- Xóa --}}
                <button type="button" class="btn-admin btn-admin-danger btn-sm" title="Xóa Chapter"
                  onclick="openDeleteModal('{{ route('admin.comics.chapters.destroy', [$comic->id, $chapter->id]) }}', 'Chapter {{ $chapter->chapter_number }} — {{ $chapter->title }}')">
                  🗑️ Xóa
                </button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="pagination-wrap">{{ $chapters->links() }}</div>
  @endif
</div>
@endsection
