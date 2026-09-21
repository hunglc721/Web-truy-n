@extends('layouts.main')

@section('title', 'Truyện Độc Quyền - WebComics Originals')

@section('meta')
  <meta name="description" content="Khám phá các bộ Manga, Manhwa và Webtoon độc quyền được tuyển chọn trên WebComics Originals." />
@endsection

@section('content')
<section class="orig-hero-section">
  <div class="orig-hero-glow"></div>
  <div class="container">
    <div class="orig-hero-inner">
      <div class="orig-hero-badge">✨ TRUYỆN TRANH ĐỘC QUYỀN WEBCOMICS</div>
      <h1 class="orig-hero-title">Khám Phá Các Bộ Truyện <span class="purple-gradient-text">Độc Quyền Chính Thức</span></h1>
      <p class="orig-hero-sub">Khám phá những tác phẩm nổi bật được tuyển chọn từ kho truyện WebComics và cập nhật trực tiếp từ dữ liệu Laravel.</p>
    </div>
  </div>
</section>

<div class="genre-section">
  <div class="container">
    <div class="genre-tabs-wrapper">
      <button type="button" class="genre-scroll-btn genre-scroll-left" aria-label="Cuộn trái" title="Xem các mục trước">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <div class="genre-tabs" id="orig-tabs">
        <a href="{{ route('originals') }}" class="genre-tab {{ empty($selectedGenre) ? 'active' : '' }}">Biên Tập Viên Chọn</a>
        @foreach($genres as $genre)
          <a href="{{ route('originals', ['genre' => $genre->slug]) }}" class="genre-tab {{ ($selectedGenre ?? '') === $genre->slug ? 'active' : '' }}">{{ $genre->name }}</a>
        @endforeach
      </div>
      <button type="button" class="genre-scroll-btn genre-scroll-right" aria-label="Cuộn phải" title="Xem thêm thể loại">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </div>
</div>

<main class="page-container discovery-page originals-page">
  <div class="container">
    @if($spotlight)
      @php
        $spotlightGenres = $spotlight->genres->pluck('name');
        $spotlightAuthors = $spotlight->authors;
        $spotlightChapter = $spotlight->latestChapter;
        $spotlightSaved = auth()->check() ? auth()->user()->hasInLibrary($spotlight->id) : false;
      @endphp

      <div class="orig-spotlight-card">
        <div class="spotlight-cover">
          <a href="{{ route('comics.show', $spotlight->slug) }}">
            <img src="{{ $spotlight->cover_url }}" alt="{{ $spotlight->title }}" class="cover-img" />
          </a>
          <span class="spotlight-badge">{{ $spotlight->is_featured ? '⭐ LỰA CHỌN NỔI BẬT' : '⭐ ORIGINAL NỔI BẬT' }}</span>
        </div>

        <div class="spotlight-details">
          <div class="spotlight-tags">
            <span class="orig-tag">ĐỘC QUYỀN</span>
            @foreach($spotlightGenres->take(2) as $genre)
              <span class="genre-tag">{{ $genre }}</span>
            @endforeach
          </div>

          <h2 class="spotlight-title">{{ $spotlight->title }}</h2>
          <p class="spotlight-author">
            Tác giả: {{ $spotlightAuthors->pluck('name')->join(' · ') ?: 'Đang cập nhật' }}
            &middot; ★ {{ number_format($spotlight->avg_rating, 1) }}
            &middot; {{ $spotlight->formatted_views }} Lượt Đọc
          </p>
          <p class="spotlight-desc">{{ $spotlight->description }}</p>

          <div class="spotlight-actions">
            <a href="{{ route('comics.show', $spotlight->slug) }}" class="btn-spotlight-read">
              🚀 Đọc Ngay @if($spotlightChapter) · {{ $spotlightChapter->label }} mới nhất @endif
            </a>

            @auth
              <form action="{{ route('library.toggle', $spotlight) }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="btn-spotlight-sub">
                  {{ $spotlightSaved ? '✓ Đã Có Trong Tủ Truyện' : '+ Thêm Vào Tủ Truyện' }}
                </button>
              </form>
            @else
              <a href="{{ route('login') }}" class="btn-spotlight-sub" style="text-decoration:none;">+ Thêm Vào Tủ Truyện</a>
            @endauth
          </div>
        </div>
      </div>
    @endif

    <div class="comics-section" style="padding-top:36px;">
      <div class="section-header">
        <h2 class="section-title">✨ Tuyển Tập Được Đề Xuất</h2>
        <span class="results-count">{{ $originals->count() }} bộ truyện độc quyền{{ !empty($selectedGenre) ? ' trong thể loại đã chọn' : '' }}</span>
      </div>

      <div class="comics-grid discovery-results-grid">
        @forelse($originals as $comic)
          @include('partials.comic-card', ['comic' => $comic])
        @empty
          <div class="empty-state"><span aria-hidden="true">✦</span><strong>Chưa có Originals phù hợp</strong><p>Thử chọn một thể loại khác.</p></div>
        @endforelse
      </div>
    </div>
  </div>
</main>
@endsection
