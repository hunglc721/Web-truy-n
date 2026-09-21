@extends('layouts.main')

@section('title', ($siteSettings['site_name'] ?? 'WebComics') . ' - Đọc Manga, Manhwa & Manhua Online')

@section('meta')
<meta name="description" content="{{ $siteSettings['meta_description'] ?? 'Khám phá truyện tranh cập nhật mới, lịch phát hành và truyện thịnh hành trên WebComics.' }}" />
<meta name="keywords" content="{{ $siteSettings['seo_keywords'] ?? 'đọc truyện,manga,manhwa,manhua,webtoon' }}" />
@endsection

@section('content')
<main id="main-content" class="home-page">
  @if(isset($banners) && $banners->isNotEmpty())
    <section class="banner-slider-section" id="hero-banner-section" aria-label="Nội dung nổi bật">
      <div class="banner-carousel" id="banner-carousel">
        <div class="banner-track" id="banner-track">
          @foreach($banners as $index => $banner)
            <div class="banner-slide {{ $index === 0 ? 'active' : '' }}" data-slide-index="{{ $index }}">
              <a href="{{ route('banners.click', $banner) }}" class="banner-link">
                <div class="banner-img-container"><img src="{{ $banner->display_image }}" alt="{{ $banner->title }}" class="banner-hero-img" loading="{{ $index === 0 ? 'eager' : 'lazy' }}"></div>
                <div class="banner-overlay"><span class="banner-badge">Nổi bật</span><h2 class="banner-title">{{ $banner->title }}</h2><span class="banner-btn-explore">Khám phá ngay <span aria-hidden="true">→</span></span></div>
              </a>
            </div>
          @endforeach
        </div>
        @if($banners->count() > 1)
          <button type="button" class="banner-nav-btn banner-prev" id="banner-prev" aria-label="Banner trước"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button>
          <button type="button" class="banner-nav-btn banner-next" id="banner-next" aria-label="Banner kế tiếp"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button>
          <div class="banner-dots" id="banner-dots">@foreach($banners as $index => $banner)<button type="button" class="banner-dot {{ $index === 0 ? 'active' : '' }}" data-dot-index="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>@endforeach</div>
        @endif
      </div>
    </section>
  @endif

  @auth
    @if(isset($recentReadings) && $recentReadings->isNotEmpty())
      <section class="comics-section home-continue-section" aria-labelledby="continue-title"><div class="container">
        <div class="section-header"><h2 class="section-title" id="continue-title">Tiếp tục đọc</h2><a href="{{ route('user.history') }}" class="see-all">Lịch sử <span aria-hidden="true">→</span></a></div>
        <div class="continue-reading-list">
          @foreach($recentReadings as $history)
            @php($comic = $history->comic)
            @php($chapter = $history->chapter)
            @php($percent = max(5, min(100, (int) round($history->scroll_percent))))
            @if($comic && $chapter)
              <article class="continue-card">
                <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug ?: 'chapter-' . $chapter->chapter_number]) }}" class="continue-cover"><img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" loading="lazy"></a>
                <div class="continue-body"><h3><a href="{{ route('comics.show', $comic->slug) }}">{{ $comic->title }}</a></h3><p>Chương {{ $chapter->chapter_number }}</p><div class="reading-progress" role="progressbar" aria-label="Tiến độ đọc" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}"><span style="--progress: {{ $percent }}%"></span></div><div class="continue-meta"><span>{{ $percent }}%</span><a href="{{ route('chapters.show', [$comic->slug, $chapter->slug ?: 'chapter-' . $chapter->chapter_number]) }}">Đọc tiếp</a></div></div>
              </article>
            @endif
          @endforeach
        </div>
      </div></section>
    @endif
  @else
    <section class="comics-section home-continue-section" id="guest-continue-reading" style="display:none" aria-labelledby="guest-continue-title"><div class="container"><div class="section-header"><h2 class="section-title" id="guest-continue-title">Tiếp tục đọc</h2><a href="{{ route('login') }}" class="see-all">Đăng nhập để đồng bộ</a></div><div id="guest-history-cards" class="continue-reading-list"></div></div></section>
  @endauth

  <section class="comics-section home-updates-section" id="new-updates-section" aria-labelledby="updates-title"><div class="container">
    <div class="section-header"><h2 class="section-title" id="updates-title">Chương mới</h2><a href="{{ route('genres') }}" class="see-all">Xem tất cả <span aria-hidden="true">→</span></a></div>
    <div class="comics-grid home-comics-grid" id="new-updates-grid">
      @forelse($latestUpdates as $comic)
        @include('partials.comic-card', ['comic' => $comic])
      @empty
        <div class="empty-state compact"><span aria-hidden="true">📖</span><strong>Chưa có chương mới</strong><p>Quay lại sau để xem các cập nhật tiếp theo.</p></div>
      @endforelse
    </div>
  </div></section>

  <section class="hero-section home-trending-section" id="trending-section" aria-labelledby="trending-title"><div class="hero-content-wrap">
    <div class="section-header"><h2 class="section-title" id="trending-title">Đang thịnh hành</h2><a href="{{ route('genres', ['sort' => 'hot']) }}" class="see-all">Khám phá thêm</a></div>
    <div class="trending-scroll-wrap"><div class="trending-list" id="trending-list">
      @forelse($trendingComics as $comic)
        <a href="{{ route('comics.show', $comic->slug) }}" class="trending-card" aria-label="{{ $comic->title }}"><div class="tcard-cover"><img src="{{ $comic->cover_url }}" alt="Bìa {{ $comic->title }}" class="cover-img" loading="lazy"><span class="rank-num {{ $loop->iteration <= 3 ? 'r'.$loop->iteration : '' }}">{{ $comic->trending_rank ?? $loop->iteration }}</span></div><p class="tcard-title">{{ $comic->title }}</p><p class="tcard-genre">{{ $comic->genres->pluck('name')->take(2)->join(' · ') }}</p></a>
      @empty
        <div class="empty-state compact"><strong>Chưa có dữ liệu thịnh hành</strong></div>
      @endforelse
    </div><button class="scroll-arrow scroll-left" id="trend-left" aria-label="Cuộn trái"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button><button class="scroll-arrow scroll-right" id="trend-right" aria-label="Cuộn phải"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button></div>
  </div></section>

  <section class="genre-section home-genre-section" id="genre-section" aria-labelledby="genres-title"><div class="container">
    <div class="section-header"><h2 class="section-title" id="genres-title">Thể loại</h2><a href="{{ route('genres') }}" class="see-all">Tất cả thể loại</a></div>
    <div class="genre-tabs-wrapper"><button type="button" class="genre-scroll-btn genre-scroll-left" aria-label="Cuộn trái"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button><div class="genre-tabs" id="genre-tabs" role="list"><a href="{{ route('genres') }}" class="genre-tab {{ !request('genre') ? 'active' : '' }}">Tất cả</a>@foreach($genres as $genre)<a href="{{ route('genres', ['genre' => $genre->slug]) }}" class="genre-tab {{ request('genre') === $genre->slug ? 'active' : '' }}">{{ $genre->name }}</a>@endforeach</div><button type="button" class="genre-scroll-btn genre-scroll-right" aria-label="Cuộn phải"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button></div>
  </div></section>

  @include('partials.home-discovery')

  <section class="ai-recommendation-cta" aria-labelledby="ai-cta-title"><div class="container"><div class="ai-cta-inner"><div><span class="section-eyebrow">Gợi ý theo gu</span><h2 id="ai-cta-title">Chưa biết đọc gì tiếp?</h2><p>Nói vài điều bạn thích, trợ lý Comicx sẽ lọc nhanh những bộ phù hợp.</p></div><button type="button" class="btn btn-download" data-open-recommendation-chat>Mở trợ lý gợi ý</button></div></div></section>
</main>
@endsection
