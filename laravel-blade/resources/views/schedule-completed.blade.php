@extends('layouts.main')

@section('title', 'Truyện Đã Hoàn Thành - WebComics')

@section('meta')
<meta name="description" content="Danh sách truyện đã hoàn thành trên WebComics." />
@endsection

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <a href="{{ route('schedule') }}">Lịch Ra Truyện</a> &rsaquo; <span>Hoàn Thành</span></div>
      <h1 class="page-title">✅ Truyện Đã Hoàn Thành</h1>
      <p class="page-subtitle">Đọc liền mạch các bộ truyện đã phát hành đầy đủ.</p>
    </div>

    <div class="schedule-completed-nav">
      <a href="{{ route('schedule') }}" class="chip">← Lịch theo ngày</a>
      <span class="chip active">✅ Hoàn Thành</span>
    </div>

    @if($comics->count())
      <div class="comics-grid">
        @foreach($comics as $comic)
          @php($chapter = $comic->latestChapter)
          <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm">
            <div class="sm-cover">
              <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
              <span class="sm-badge new-badge">ĐÃ FULL</span>
              <span class="sm-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
            </div>
            <div class="sm-info">
              <h2 class="sm-title">{{ $comic->title }}</h2>
              <div class="sm-meta">
                <span class="sm-genre">{{ number_format($comic->chapters_count) }} chương</span>
                <span class="sm-time">{{ $chapter?->time_ago ?? 'Hoàn thành' }}</span>
              </div>
            </div>
          </a>
        @endforeach
      </div>
      <div style="margin-top:28px">{{ $comics->links() }}</div>
    @else
      <div class="roadmap-empty-state"><strong>Chưa có truyện hoàn thành.</strong></div>
    @endif
  </div>
</main>
@endsection
