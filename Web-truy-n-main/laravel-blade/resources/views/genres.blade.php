{{-- resources/views/genres.blade.php --}}
@extends('layouts.main')

@section('title', !empty($selectedGenres) ? 'Lọc Truyện Tranh - WebComics' : 'Khám Phá Truyện Tranh - WebComics')

@section('meta')
  <meta name="description" content="Tìm kiếm và lọc truyện tranh theo nhiều thể loại, trạng thái ra tập và thứ tự sắp xếp hot nhất trên WebComics." />
@endsection

@section('content')
<main class="page-container">
  <div class="container">

    {{-- Breadcrumb & Page Title --}}
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Home</a> &rsaquo;
        <a href="{{ route('genres') }}">Genres</a>
        @if(!$activeGenres->isEmpty())
          &rsaquo; <span>{{ $activeGenres->pluck('name')->join(', ') }}</span>
        @endif
      </div>

      <h1 class="page-title">
        @if(!$activeGenres->isEmpty())
          📖 Truyện Thể Loại: {{ $activeGenres->pluck('name')->join(' + ') }}
        @else
          🔍 Khám Phá Kho Truyện Tranh
        @endif
      </h1>
      <p class="page-subtitle">
        Tìm thấy <strong>{{ $comics->total() }}</strong> bộ truyện phù hợp với bộ lọc đã chọn.
      </p>
    </div>

    {{-- BỘ LỌC ĐA TIÊU CHÍ (MULTI-CRITERIA FILTER PANEL) --}}
    <form action="{{ route('genres') }}" method="GET" id="filter-form">

      <div class="filter-panel" style="
        background: var(--bg-surface-1);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-bottom: 30px;
        display: flex;
        flex-direction: column;
        gap: 20px;
      ">

        {{-- 1. Lọc theo Thể loại (Genre Chips & Multi-select) --}}
        <div class="filter-group" style="display: flex; align-items: flex-start; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px; padding-top: 6px;">Thể loại:</span>
          <div class="filter-chips" style="display: flex; flex-wrap: wrap; gap: 8px; flex: 1;">

            <a href="{{ route('genres', request()->except(['page', 'genres', 'genre'])) }}"
               class="chip {{ empty($selectedGenres) ? 'active' : '' }}">
              Tất cả Thể loại
            </a>

            @foreach($genres as $g)
              @php
                $isCurrentlySelected = in_array($g->slug, $selectedGenres);
                // Toggle slug in array for multi-selection link
                $newSelected = $isCurrentlySelected
                    ? array_diff($selectedGenres, [$g->slug])
                    : array_merge($selectedGenres, [$g->slug]);

                $urlParams = request()->except(['page', 'genres', 'genre']);
                if (!empty($newSelected)) {
                    $urlParams['genre'] = implode(',', $newSelected);
                }
              @endphp

              <a href="{{ route('genres', $urlParams) }}"
                 class="chip {{ $isCurrentlySelected ? 'active' : '' }}">
                {{ $g->icon ?? '📁' }} {{ $g->name }}
              </a>
            @endforeach
          </div>
        </div>

        {{-- 2. Lọc theo Trạng thái truyện (Status: Tất cả / Ongoing / Completed) --}}
        <div class="filter-group" style="display: flex; align-items: center; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px;">Trạng thái:</span>
          <div class="filter-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">
            @foreach([
              'all'       => '🌐 Tất cả',
              'ongoing'   => '🟢 Đang tiến hành (Ongoing)',
              'completed' => '🔵 Hoàn thành (Completed)'
            ] as $statusKey => $statusLabel)
              <a href="{{ route('genres', array_merge(request()->except(['page', 'status']), ['status' => $statusKey])) }}"
                 class="chip {{ $status === $statusKey ? 'active' : '' }}">
                {{ $statusLabel }}
              </a>
            @endforeach
          </div>
        </div>

        {{-- 3. Sắp xếp kết quả (Sort By: Top views / Rating / Latest) --}}
        <div class="filter-group" style="display: flex; align-items: center; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px;">Sắp xếp:</span>
          <div class="filter-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">
            @foreach([
              'hot'    => '🔥 Top Lượt Xem (Hottest)',
              'rating' => '⭐ Đánh Giá Cao (Top Rated)',
              'latest' => '🆕 Mới Cập Nhật (Latest)'
            ] as $sortKey => $sortLabel)
              <a href="{{ route('genres', array_merge(request()->except(['page', 'sort']), ['sort' => $sortKey])) }}"
                 class="chip {{ $sortBy === $sortKey ? 'active' : '' }}">
                {{ $sortLabel }}
              </a>
            @endforeach
          </div>
        </div>

        @if(!empty($selectedGenres) || $status !== 'all' || $sortBy !== 'hot')
          <div style="border-top: 1px solid var(--border-color); padding-top: 14px; display: flex; justify-content: flex-end;">
            <a href="{{ route('genres') }}" style="color: #ef4444; font-size: 13px; font-weight: 700; text-decoration: none;">
              ✖ Xóa tất cả bộ lọc
            </a>
          </div>
        @endif

      </div>
    </form>

    {{-- Thống kê số lượng kết quả --}}
    <p class="results-bar">
      Hiển thị {{ $comics->firstItem() ?? 0 }}–{{ $comics->lastItem() ?? 0 }} trong tổng số {{ $comics->total() }} bộ truyện
    </p>

    {{-- BROWSE GRID TRUYỆN --}}
    <div class="browse-grid">
      @forelse($comics as $comic)
        @php
          $primaryTag   = $comic->tags->firstWhere('slug', 'hot')
                       ?? $comic->tags->firstWhere('slug', 'popular')
                       ?? $comic->tags->first();
          $primaryGenre = $comic->genres->firstWhere('pivot.is_primary', true)
                       ?? $comic->genres->first();
          $secondGenre  = $comic->genres->skip(1)->first();
        @endphp

        <div class="browse-card">
          <div class="browse-cover">
            <a href="{{ route('comics.show', $comic->slug) }}">
              <img src="{{ $comic->cover_image }}"
                   alt="{{ $comic->title }}"
                   class="cover-img"
                   loading="lazy" />
            </a>

            @if($primaryTag)
              <span class="badge-tag {{ in_array($primaryTag->slug, ['hot']) ? 'hot' : 'new' }}">
                {{ strtoupper($primaryTag->name) }}
              </span>
            @endif

            <span class="rating-tag">★ {{ number_format($comic->avg_rating, 1) }}</span>
          </div>

          <div class="browse-info">
            <h3 class="browse-title">
              <a href="{{ route('comics.show', $comic->slug) }}">{{ $comic->title }}</a>
            </h3>
            <p class="browse-author">
              Tác giả: {{ $comic->authors->pluck('name')->join(' &middot; ') ?: 'Đang cập nhật' }}
            </p>
            <p class="browse-meta">
              <span>{{ $primaryGenre?->name }}</span>
              @if($secondGenre)
                &middot; <span>{{ $secondGenre->name }}</span>
              @endif
              &middot;
              <span>{{ $comic->chapters_count }} Chapter</span>
            </p>
            <p class="browse-desc">{{ Str::limit($comic->description, 100) }}</p>
          </div>
        </div>
      @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: var(--text-sub); background: var(--bg-surface-1); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
          <p style="font-size: 48px; margin-bottom: 16px;">📭</p>
          <p style="font-size: 16px; font-weight: 600;">Không tìm thấy bộ truyện nào phù hợp với bộ lọc đã chọn.</p>
          <a href="{{ route('genres') }}" class="btn btn-login" style="margin-top: 16px; display: inline-block; text-decoration: none;">
            ✖ Xóa bộ lọc và xem tất cả
          </a>
        </div>
      @endforelse
    </div>

    {{-- Phân trang (Pagination) mượt mà giữ tham số query --}}
    @if($comics->hasPages())
      <div style="margin-top: 40px; display: flex; justify-content: center;">
        {{ $comics->links() }}
      </div>
    @endif

  </div>
</main>
@endsection
