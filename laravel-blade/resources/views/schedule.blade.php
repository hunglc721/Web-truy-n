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
  .schedule-day-bar { grid-template-columns: repeat(8, minmax(0, 1fr)); overflow-x:auto; }
  @media (max-width: 900px) {
    .schedule-day-bar { display:flex; gap:8px; padding-bottom:6px; scrollbar-width:none; -webkit-overflow-scrolling:touch; }
    .schedule-day-bar::-webkit-scrollbar { display:none; }
    .schedule-day-bar .sched-day-item { flex:0 0 112px; min-height:72px; }
  }
</style>
@endpush

@section('content')
<main class="page-container discovery-page schedule-page">
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

    <div class="sched-current-title">
      <h2>Lịch Ra Truyện {{ $selectedDayLabel }}</h2>
      @if($selectedDay === now()->dayOfWeek)
        <span class="badge-status-live">● ĐANG CẬP NHẬT HÔM NAY</span>
      @endif
    </div>

    <div class="comics-grid discovery-results-grid">
      @forelse($comics as $comic)
        @include('partials.comic-card', ['comic' => $comic])
      @empty
        <div class="empty-state"><span aria-hidden="true">📅</span><strong>Chưa có lịch phát hành</strong><p>Hiện chưa có truyện nào được xếp lịch vào {{ $selectedDayLabel }}.</p></div>
      @endforelse
    </div>
  </div>
</main>
@endsection
