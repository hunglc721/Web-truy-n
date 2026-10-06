@extends('layouts.main')

@php
  $dayLabels = [
    0 => ['short' => 'CHỦ NHẬT', 'full' => 'Chủ Nhật'],
    1 => ['short' => 'THỨ 2', 'full' => 'Thứ Hai'],
    2 => ['short' => 'THỨ 3', 'full' => 'Thứ Ba'],
    3 => ['short' => 'THỨ 4', 'full' => 'Thứ Tư'],
    4 => ['short' => 'THỨ 5', 'full' => 'Thứ Năm'],
    5 => ['short' => 'THỨ 6', 'full' => 'Thứ Sáu'],
    6 => ['short' => 'THỨ 7', 'full' => 'Thứ Bảy'],
  ];
  $selectedDayLabel = $dayLabels[$selectedDay]['full'] ?? 'Hôm Nay';
@endphp

@section('title', 'Lịch Ra Truyện '.$selectedDayLabel.' - WebComics')

@section('meta')
  <meta name="description" content="Xem lịch cập nhật Manga, Manhwa và Manhua theo từng ngày trong tuần trên WebComics." />
@endsection

@push('styles')
<style>
  .schedule-day-bar {
    grid-template-columns: repeat(8, minmax(0, 1fr));
    overflow-x: auto;
    background: rgba(17, 24, 39, 0.7);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 10px;
    gap: 8px;
    margin-bottom: 24px;
  }
  .sched-day-item {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .sched-day-item:hover {
    background: rgba(139, 92, 246, 0.15);
    border-color: rgba(139, 92, 246, 0.4);
    transform: translateY(-2px);
  }
  .sched-day-item.active {
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%) !important;
    border-color: transparent !important;
    color: #fff !important;
    box-shadow: 0 4px 16px rgba(139, 92, 246, 0.45);
  }
  .badge-status-live {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.4);
    box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);
    padding: 5px 14px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }
  @media (max-width: 900px) {
    .schedule-day-bar { display:flex; gap:8px; padding-bottom:6px; scrollbar-width:none; -webkit-overflow-scrolling:touch; }
    .schedule-day-bar::-webkit-scrollbar { display:none; }
    .schedule-day-bar .sched-day-item { flex:0 0 112px; min-height:72px; }
  }
</style>
@endpush

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Lịch Ra Truyện</span>
      </div>
      <h1 class="page-title">Lịch Phát Sóng Truyện Hàng Tuần</h1>
      <p class="page-subtitle">Không bỏ lỡ chương mới. Chọn một ngày để xem các bộ truyện có lịch phát hành tương ứng.</p>
    </div>

    <div class="schedule-day-bar has-completed-tab">
      @foreach($days as $day)
        <a href="{{ route('schedule', ['day' => $day['day']]) }}"
           class="sched-day-item {{ $day['active'] ? 'active' : '' }}"
           aria-current="{{ $day['active'] ? 'page' : 'false' }}">
          <span class="day-name">{{ $dayLabels[$day['day']]['short'] ?? $day['name'] }}</span>
          <span class="day-count">
            {{ $day['count'] }} Bộ Truyện
            @if($day['count'] > 0 && $day['count'] === $days->max('count')) 🔥 @endif
          </span>
        </a>
      @endforeach
      <a href="{{ route('schedule.completed') }}" class="sched-day-item" data-completed-tab="1" style="text-decoration:none;">
        <span class="day-name">✓</span>
        <span class="day-count">HOÀN THÀNH</span>
      </a>
    </div>

    <div class="sched-current-title" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
      <h2 style="margin:0;">Lịch Ra Truyện {{ $selectedDayLabel }}</h2>
      @if($selectedDay === now()->dayOfWeek)
        <span class="badge-status-live">● ĐANG CẬP NHẬT HÔM NAY</span>
      @endif
    </div>

    <div class="browse-grid">
      @forelse($comics as $comic)
        @php
          $chapter = $comic->latestChapter;
          $primaryTag = $comic->tags->firstWhere('slug', 'hot') ?? $comic->tags->first();
          $primaryGenre = $comic->genres->first();
        @endphp

        <article class="browse-card card-shine-effect">
          <div class="browse-cover comic-card-poster">
            <a href="{{ route('comics.show', $comic->slug) }}" aria-label="Xem {{ $comic->title }}">
              <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy" />
            </a>

            @if($chapter)
              <span class="badge-tag {{ $primaryTag?->slug === 'hot' ? 'hot' : 'new' }}">
                MỚI {{ $chapter->label }}
              </span>
            @endif

            <span class="rating-tag">★ {{ number_format($comic->avg_rating, 1) }}</span>
          </div>

          <div class="browse-info">
            <h3 class="browse-title">
              <a href="{{ route('comics.show', $comic->slug) }}">{{ $comic->title }}</a>
            </h3>
            <p class="browse-author">Cập nhật: {{ $chapter?->time_ago ?? 'Hôm nay' }}</p>
            <p class="browse-meta">
              <span>{{ $primaryGenre?->name ?? 'Đang cập nhật' }}</span>
              @if($chapter)
                &middot; <span>{{ $chapter->label }}</span>
              @endif
            </p>
            <p class="browse-desc">{{ Str::limit($comic->description, 90) }}</p>
          </div>
        </article>
      @empty
        <div style="grid-column:1/-1;text-align:center;padding:80px;color:var(--text-sub);">
          <p style="font-size:48px;margin-bottom:16px;">📅</p>
          <p>Chưa có truyện nào được xếp lịch vào {{ $selectedDayLabel }}.</p>
        </div>
      @endforelse
    </div>
  </div>
</main>
@endsection
