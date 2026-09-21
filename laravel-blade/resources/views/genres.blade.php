{{-- resources/views/genres.blade.php --}}
@extends('layouts.main')

@section('title', !empty($selectedGenres) ? 'Lọc Truyện Tranh - WebComics' : 'Khám Phá Truyện Tranh - WebComics')

@section('meta')
  <meta name="description" content="Tìm kiếm và lọc truyện tranh theo nhiều thể loại, trạng thái ra tập và thứ tự sắp xếp hot nhất trên WebComics." />
@endsection

@push('styles')
<style>
  .chip.excluded {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
    text-decoration: line-through;
  }
</style>
@endpush

@section('content')
<main class="page-container discovery-page">
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
          Truyện thể loại: {{ $activeGenres->pluck('name')->join(' + ') }}
        @else
          Khám phá kho truyện
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
        gap: 18px;
      ">

        {{-- 0. Tìm kiếm từ khoá (Search Query) --}}
        <div class="filter-group" style="display: flex; align-items: center; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px;">Từ khoá:</span>
          <div style="flex: 1; max-width: 460px; display: flex; gap: 8px;">
            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Tìm theo tên truyện, tác giả (có dấu hoặc không dấu)..." class="form-control" style="flex: 1; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 14px; color: #fff; font-size: 14px;" />
            <button type="submit" class="btn btn-login" style="padding: 8px 16px; border-radius: 8px;">🔍 Tìm</button>
          </div>
        </div>

        {{-- 1. Lọc theo Thể loại (Genre Chips & Multi-select) --}}
        <div class="filter-group" style="display: flex; align-items: flex-start; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px; padding-top: 6px;">Thể loại:</span>
          <div class="filter-chips" style="display: flex; flex-wrap: wrap; gap: 8px; flex: 1;">

            <a href="{{ route('genres', request()->except(['page', 'genres', 'genre', 'exclude_genres'])) }}"
               class="chip {{ empty($selectedGenres) && empty($excludeGenres) ? 'active' : '' }}">
              Tất cả Thể loại
            </a>

            @foreach($genres as $g)
              @php
                $isIncluded = in_array($g->slug, $selectedGenres);
                $isExcluded = in_array($g->slug, $excludeGenres ?? []);
                
                $newIncluded = $selectedGenres;
                $newExcluded = $excludeGenres ?? [];

                if ($isIncluded) {
                    $newIncluded = array_diff($newIncluded, [$g->slug]);
                    $newExcluded = array_merge($newExcluded, [$g->slug]);
                } elseif ($isExcluded) {
                    $newExcluded = array_diff($newExcluded, [$g->slug]);
                } else {
                    $newIncluded = array_merge($newIncluded, [$g->slug]);
                }

                $urlParams = request()->except(['page', 'genres', 'genre', 'exclude_genres']);
                if (!empty($newIncluded)) {
                    $urlParams['genre'] = implode(',', $newIncluded);
                }
                if (!empty($newExcluded)) {
                    $urlParams['exclude_genres'] = implode(',', $newExcluded);
                }
              @endphp

              <a href="{{ route('genres', $urlParams) }}"
                 class="chip {{ $isIncluded ? 'active' : ($isExcluded ? 'excluded' : '') }}"
                 title="{{ $isIncluded ? 'Đang chọn (Click để Loại trừ)' : ($isExcluded ? 'Đang loại trừ (Click để Bỏ chọn)' : 'Click để Chọn') }}">
                {{ $g->icon ?? '📁' }} {{ $g->name }} {!! $isIncluded ? '✓' : ($isExcluded ? '✖' : '') !!}
              </a>
            @endforeach
          </div>
        </div>

        {{-- 2. Lọc theo Quốc gia / Xuất xứ (Manga, Manhwa, Manhua, VN) --}}
        <div class="filter-group" style="display: flex; align-items: center; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px;">Xuất xứ:</span>
          <div class="filter-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">
            @foreach([
              'all'     => '🌐 Tất cả',
              'manga'   => '🇯🇵 Manga (Nhật Bản)',
              'manhwa'  => '🇰🇷 Manhwa (Hàn Quốc)',
              'manhua'  => '🇨🇳 Manhua (Trung Quốc)',
              'vietnam' => '🇻🇳 WebComics Originals (Việt Nam)',
            ] as $cKey => $cLabel)
              <a href="{{ route('genres', array_merge(request()->except(['page', 'country']), ['country' => $cKey])) }}"
                 class="chip {{ ($country ?? 'all') === $cKey ? 'active' : '' }}">
                {{ $cLabel }}
              </a>
            @endforeach
          </div>
        </div>

        {{-- 3. Lọc theo Trạng thái truyện (Status: Tất cả / Ongoing / Completed / Hiatus) --}}
        <div class="filter-group" style="display: flex; align-items: center; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px;">Trạng thái:</span>
          <div class="filter-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">
            @foreach([
              'all'       => '🌐 Tất cả',
              'ongoing'   => '🟢 Đang tiến hành (Ongoing)',
              'completed' => '🔵 Hoàn thành (Completed)',
              'hiatus'    => '🟡 Tạm dừng (Hiatus)',
            ] as $statusKey => $statusLabel)
              <a href="{{ route('genres', array_merge(request()->except(['page', 'status']), ['status' => $statusKey])) }}"
                 class="chip {{ ($status ?? 'all') === $statusKey ? 'active' : '' }}">
                {{ $statusLabel }}
              </a>
            @endforeach
          </div>
        </div>

        {{-- 4. Lọc theo Số chương tối thiểu (Min Chapters) --}}
        <div class="filter-group" style="display: flex; align-items: center; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px;">Số chương:</span>
          <div class="filter-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">
            @foreach([
              0   => 'Tất cả',
              10  => '>= 10 Chapter',
              50  => '>= 50 Chapter',
              100 => '>= 100 Chapter',
            ] as $chapMin => $chapLabel)
              <a href="{{ route('genres', array_merge(request()->except(['page', 'min_chapters']), ['min_chapters' => $chapMin])) }}"
                 class="chip {{ ((int) ($minChapters ?? 0)) === $chapMin ? 'active' : '' }}">
                {{ $chapLabel }}
              </a>
            @endforeach
          </div>
        </div>

        {{-- 5. Sắp xếp kết quả (Sort By: Top views / Rating / Latest / Alphabetical) --}}
        <div class="filter-group" style="display: flex; align-items: center; gap: 16px;">
          <span class="filter-label" style="font-weight: 700; color: var(--text-sub); min-width: 90px;">Sắp xếp:</span>
          <div class="filter-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">
            @foreach([
              'hot'          => '🔥 Top Lượt Xem (Hottest)',
              'rating'       => '⭐ Đánh Giá Cao (Top Rated)',
              'latest'       => '🆕 Mới Cập Nhật (Latest)',
              'alphabetical' => '🔤 Tên A-Z',
            ] as $sortKey => $sortLabel)
              <a href="{{ route('genres', array_merge(request()->except(['page', 'sort']), ['sort' => $sortKey])) }}"
                 class="chip {{ ($sortBy ?? 'hot') === $sortKey ? 'active' : '' }}">
                {{ $sortLabel }}
              </a>
            @endforeach
          </div>
        </div>

        @if(!empty($selectedGenres) || !empty($excludeGenres) || ($status ?? 'all') !== 'all' || ($country ?? 'all') !== 'all' || !empty($minChapters) || ($sortBy ?? 'hot') !== 'hot' || !empty($q))
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
    <div class="comics-grid discovery-results-grid">
      @forelse($comics as $comic)
        @include('partials.comic-card', ['comic' => $comic])
      @empty
        <div class="empty-state"><span aria-hidden="true">📚</span><strong>Không tìm thấy truyện phù hợp</strong><p>Thử bỏ bớt bộ lọc hoặc tìm bằng từ khóa khác.</p><a href="{{ route('genres') }}" class="btn btn-login">Xóa bộ lọc</a></div>
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
