{{--
  ============================================================
  FILE: resources/views/schedule.blade.php  (DYNAMIC VERSION)
  Variables từ ScheduleController::index():
    $days            — Collection  [{day, name, full, count, active, is_today}]
    $comics          — Collection<Comic>
    $selectedDay     — int (0-6)
    $selectedDayName — string ("Thursday")
  ============================================================
--}}
@extends('layouts.main')

@section('title', $selectedDayName.' Release Schedule - WebComics | Daily Updated Manga')

@section('meta')
  <meta name="description" content="Check out the weekly update schedule for your favorite Webtoons, Manhua & Manga on WebComics. Updated daily!" />
@endsection

@section('content')
<main class="page-container">
  <div class="container">

    {{-- Breadcrumb --}}
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Home</a> &rsaquo; <span>Schedule</span>
      </div>
      <h1 class="page-title">Weekly Update Schedule</h1>
      <p class="page-subtitle">Never miss a new chapter! Check release days for all series.</p>
    </div>

    {{-- DAY SELECTOR BAR — dynamic từ $days --}}
    <div class="schedule-day-bar">
      {{--
        ✅ @foreach thay thế 7 thẻ <a> hardcode
        Mỗi item: {day, name("THU"), full("Thursday"), count, active, is_today}
      --}}
      @foreach($days as $day)
        <a href="{{ route('schedule', ['day' => $day['day']]) }}"
           class="sched-day-item {{ $day['active'] ? 'active' : '' }}">
          <span class="day-name">{{ $day['name'] }}</span>
          <span class="day-count">
            {{ $day['count'] }} Series
            {{-- Flame emoji cho ngày có nhiều nhất --}}
            @if($day['count'] === $days->max('count')) 🔥 @endif
          </span>
        </a>
      @endforeach
    </div>

    {{-- CURRENT DAY HEADER --}}
    <div class="sched-current-title">
      <h2>{{ $selectedDayName }} Releases</h2>
      {{-- Chỉ hiện "UPDATING TODAY" nếu đang xem ngày hôm nay --}}
      @if($selectedDay === now()->dayOfWeek)
        <span class="badge-status-live">● UPDATING TODAY</span>
      @endif
    </div>

    {{-- COMICS GRID của ngày được chọn --}}
    <div class="browse-grid">

      {{-- ✅ @forelse thay thế các div.browse-card hardcode --}}
      @forelse($comics as $comic)
        @php
          $chapter      = $comic->latestChapter;
          $primaryTag   = $comic->tags->firstWhere('slug', 'hot')
                       ?? $comic->tags->first();
          $primaryGenre = $comic->genres->first();
        @endphp

        <div class="browse-card">
          <div class="browse-cover">
            <img src="{{ $comic->cover_image }}"
                 alt="{{ $comic->title }}"
                 class="cover-img"
                 loading="lazy" />

            @if($chapter)
              <span class="badge-tag {{ $primaryTag?->slug === 'hot' ? 'hot' : 'new' }}">
                NEW {{ $chapter->label }}
              </span>
            @endif

            <span class="rating-tag">★ {{ number_format($comic->avg_rating, 1) }}</span>
          </div>

          <div class="browse-info">
            <h3 class="browse-title">
              <a href="{{ route('comics.show', $comic->slug) }}">{{ $comic->title }}</a>
            </h3>
            {{-- Thời gian cập nhật: "Updated: 2 hours ago" --}}
            <p class="browse-author">
              Updated: {{ $chapter?->time_ago ?? 'Today' }}
            </p>
            <p class="browse-meta">
              <span>{{ $primaryGenre?->name }}</span>
              @if($chapter)
                &middot; <span>{{ $chapter->label }} Released</span>
              @endif
            </p>
            <p class="browse-desc">{{ Str::limit($comic->description, 90) }}</p>
          </div>
        </div>
      @empty
        <div style="grid-column:1/-1; text-align:center; padding:80px; color:var(--text-sub);">
          <p style="font-size:48px; margin-bottom:16px;">📅</p>
          <p>No releases scheduled for {{ $selectedDayName }}.</p>
        </div>
      @endforelse

    </div>

  </div>
</main>
@endsection
