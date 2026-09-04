@extends('layouts.main')

@section('title', '#' . $tag->name . ' - WebComics')

@section('meta')
<meta name="description" content="Khám phá các bộ truyện thuộc tag {{ $tag->name }} trên WebComics." />
@endsection

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Tag</span> &rsaquo; <strong>#{{ $tag->name }}</strong></div>
      <h1 class="page-title">#{{ $tag->name }}</h1>
      <p class="page-subtitle">{{ number_format($comics->total()) }} truyện đang có tag này.</p>
    </div>

    @if($comics->count())
      <div class="comics-grid">
        @foreach($comics as $comic)
          @php($chapter = $comic->latestChapter)
          <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm">
            <div class="sm-cover">
              <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
              @if($chapter)<span class="sm-badge">{{ $chapter->label }}</span>@endif
              <span class="sm-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
            </div>
            <div class="sm-info">
              <h2 class="sm-title">{{ $comic->title }}</h2>
              <div class="sm-meta">
                <span class="sm-genre">{{ $comic->genres->first()?->name ?? 'Truyện' }}</span>
                <span class="sm-time">{{ $chapter?->time_ago ?? 'Mới' }}</span>
              </div>
            </div>
          </a>
        @endforeach
      </div>

      <div style="margin-top:28px">{{ $comics->links() }}</div>
    @else
      <div class="roadmap-empty-state">
        <strong>Chưa có truyện nào cho tag #{{ $tag->name }}.</strong>
      </div>
    @endif
  </div>
</main>
@endsection
