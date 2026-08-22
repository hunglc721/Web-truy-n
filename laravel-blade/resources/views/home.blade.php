@extends('layouts.main')

@section('title', ($siteSettings['site_name'] ?? 'WebComics') . ' - Đọc Manga, Manhwa & Manhua Online')

@section('meta')
<meta name="description" content="{{ $siteSettings['meta_description'] ?? 'Khám phá truyện tranh cập nhật mới, lịch phát hành và truyện thịnh hành trên WebComics.' }}" />
<meta name="keywords" content="{{ $siteSettings['seo_keywords'] ?? 'đọc truyện,manga,manhwa,manhua,webtoon' }}" />
@endsection

@section('content')
<main id="main-content">
  @if(isset($banners) && $banners->isNotEmpty())
  <section class="banner-slider-section" id="hero-banner-section">
    <div class="banner-carousel" id="banner-carousel">
      <div class="banner-track" id="banner-track">
        @foreach($banners as $index => $banner)
        <div class="banner-slide {{ $index === 0 ? 'active' : '' }}" data-slide-index="{{ $index }}">
          <a href="{{ route('banners.click', $banner) }}" class="banner-link">
            <div class="banner-img-container">
              <img src="{{ $banner->display_image }}" alt="{{ $banner->title }}" class="banner-hero-img" loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
            </div>
            <div class="banner-overlay">
              <span class="banner-badge">✨ Nổi bật</span>
              <h2 class="banner-title">{{ $banner->title }}</h2>
              <span class="banner-btn-explore">Khám phá ngay →</span>
            </div>
          </a>
        </div>
        @endforeach
      </div>

      @if($banners->count() > 1)
      <button type="button" class="banner-nav-btn banner-prev" id="banner-prev" aria-label="Banner trước">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button type="button" class="banner-nav-btn banner-next" id="banner-next" aria-label="Banner kế tiếp">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>

      <div class="banner-dots" id="banner-dots">
        @foreach($banners as $index => $banner)
        <button type="button" class="banner-dot {{ $index === 0 ? 'active' : '' }}" data-dot-index="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
        @endforeach
      </div>
      @endif
    </div>
  </section>
  @endif

  <section class="hero-section" id="trending-section" aria-label="Truyện thịnh hành">
    <div class="hero-content-wrap">
      <h2 class="hero-title">🔥 Truyện Thịnh Hành Hiện Nay</h2>
      <div class="trending-scroll-wrap">
        <div class="trending-list" id="trending-list">
          @forelse($trendingComics as $comic)
          <a href="{{ route('comics.show',$comic->slug) }}" class="trending-card" aria-label="{{ $comic->title }}">
            <div class="tcard-cover"><img src="{{ $comic->cover_image }}" alt="Bìa {{ $comic->title }}" class="cover-img" loading="lazy"><div class="rank-num {{ $loop->iteration<=3?'r'.$loop->iteration:'' }}">{{ $comic->trending_rank ?? $loop->iteration }}</div></div>
            <p class="tcard-title">{{ $comic->title }}</p><p class="tcard-genre">{{ $comic->genres->pluck('name')->join(' · ') }}</p>
          </a>
          @empty<p style="color:var(--text-sub);padding:20px">Chưa có dữ liệu thịnh hành.</p>@endforelse
        </div>
        <button class="scroll-arrow scroll-left" id="trend-left" aria-label="Cuộn trái"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button>
        <button class="scroll-arrow scroll-right" id="trend-right" aria-label="Cuộn phải"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button>
      </div>
    </div>
  </section>

  <section class="genre-section" id="genre-section"><div class="container"><div class="genre-tabs" id="genre-tabs" role="tablist"><a href="{{ route('genres') }}" class="genre-tab {{ !request('genre')?'active':'' }}">Tất Cả</a>@foreach($genres as $genre)<a href="{{ route('genres',['genre'=>$genre->slug]) }}" class="genre-tab {{ request('genre')===$genre->slug?'active':'' }}">{{ $genre->icon }} {{ $genre->name }}</a>@endforeach</div></div></section>

  <section class="comics-section" id="new-updates-section"><div class="container"><div class="section-header"><h2 class="section-title">📚 Chương Mới Cập Nhật</h2><a href="{{ route('genres') }}" class="see-all">Xem Tất Cả →</a></div><div class="comics-grid" id="new-updates-grid">
    @forelse($latestUpdates as $comic)
      @php($chapter=$comic->latestChapter)
      @php($primaryTag=$comic->tags->first())
      <a href="{{ route('comics.show',$comic->slug) }}" class="comic-card-sm" data-genre="{{ $comic->genres->first()?->slug }}"><div class="sm-cover"><img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">@if($chapter)<span class="sm-badge {{ $primaryTag?->slug==='hot'?'hot-badge':($primaryTag?->slug==='new'?'new-badge':'') }}">{{ $chapter->label }}</span>@endif<span class="sm-rating">★ {{ number_format($comic->avg_rating,1) }}</span></div><div class="sm-info"><h3 class="sm-title">{{ $comic->title }}</h3><div class="sm-meta"><span>{{ $comic->genres->first()?->name ?? 'Truyện' }}</span><span>{{ $chapter?->time_ago ?? 'Mới cập nhật' }}</span></div></div></a>
    @empty<div style="grid-column:1/-1;color:var(--text-sub);padding:30px;text-align:center">Chưa có chương mới.</div>@endforelse
  </div></div></section>

  @auth
  <section class="comics-section" style="padding-top:8px"><div class="container"><div class="section-header"><h2 class="section-title">👤 Khu Vực Của Bạn</h2><a href="{{ route('genres') }}" class="see-all">Mở Rộng →</a></div><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px"><a href="{{ route('user.library') }}" class="browse-card" style="padding:18px;text-decoration:none">📚 Tủ Truyện</a><a href="{{ route('user.history') }}" class="browse-card" style="padding:18px;text-decoration:none">🕘 Đọc Tiếp</a><a href="{{ route('user.likes') }}" class="browse-card" style="padding:18px;text-decoration:none">❤️ Yêu Thích</a><a href="{{ route('user.comments') }}" class="browse-card" style="padding:18px;text-decoration:none">💬 Bình Luận</a><a href="{{ route('user.ratings') }}" class="browse-card" style="padding:18px;text-decoration:none">⭐ Đánh Giá</a></div></div></section>
  @endauth
</main>
@endsection
