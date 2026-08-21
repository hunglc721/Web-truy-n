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

    <div class="schedule-day-bar">
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
    </div>

    <div class="sched-current-title">
      <h2>Lịch Ra Truyện {{ $selectedDayLabel }}</h2>
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

        <article class="browse-card">
          <div class="browse-cover">
            <a href="{{ route('comics.show', $comic->slug) }}" aria-label="Xem {{ $comic->title }}">
              <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy" />
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
