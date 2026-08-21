{{--
  ============================================================
  FILE: resources/views/home.blade.php  (DYNAMIC VERSION)
  Variables từ HomeController::index():
    $trendingComics  — Collection<Comic>  (with genres, latestChapter, tags)
    $genres          — Collection<Genre>
    $latestUpdates   — Collection<Comic>  (with genres, latestChapter, tags)
  ============================================================
--}}
@extends('layouts.main')

@section('title', 'WebComics - Read Best Manhua, Manhwa & Manga Online For Free')

@section('meta')
  <meta name="description" content="WebComics is the official home for the best FREE Webtoons, Manhua & Manga online. Discover top-rated Action, Fantasy, and Romance series. Start reading exclusive comics with daily updates now!" />
  <meta name="keywords" content="comics,manga,anime,online manga reader,manga reader,webtoon,tapas,manhua,manhwa" />
@endsection

@section('content')
<main id="main-content">

  {{-- ==================== HERO BANNERS (BE-12) ==================== --}}
  @if(isset($banners) && $banners->isNotEmpty())
    <section class="banner-slider-section" id="hero-banner-section" style="max-width: 1200px; margin: 20px auto 24px; padding: 0 16px;">
      <div class="banner-carousel" style="position: relative; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 35px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.08);">
        @foreach($banners as $index => $banner)
          <div class="banner-slide" style="{{ $index === 0 ? 'display: block;' : 'display: none;' }}">
            <a href="{{ $banner->link_url ?: '#' }}" {{ $banner->link_url ? 'target="_blank"' : '' }} style="display: block; position: relative;">
              <img
                src="{{ $banner->display_image }}"
                alt="{{ $banner->title }}"
                style="width: 100%; height: auto; max-height: 420px; object-fit: cover; display: block;"
                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
              />
              <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(13,15,20,0.95) 0%, transparent 100%); padding: 30px 24px 20px;">
                <h3 style="color: #fff; font-size: 20px; font-weight: 800; margin: 0; text-shadow: 0 2px 8px rgba(0,0,0,0.8);">
                  {{ $banner->title }}
                </h3>
              </div>
            </a>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  {{-- ==================== TRENDING HERO ==================== --}}
  <section class="hero-section" id="trending-section" aria-label="Trending comics">
    <div class="hero-content-wrap">
      <h2 class="hero-title">🔥 Trending Right Now</h2>
      <div class="trending-scroll-wrap">
        <div class="trending-list" id="trending-list">

          {{--
            ✅ @foreach thay thế các thẻ <a> hardcode
            $loop->iteration = 1, 2, 3... dùng để gán class r1 r2 r3
          --}}
          @forelse($trendingComics as $comic)
            <a href="{{ route('comics.show', $comic->slug) }}"
               class="trending-card"
               aria-label="{{ $comic->title }}">
              <div class="tcard-cover">
                <img src="{{ $comic->cover_image }}"
                     alt="{{ $comic->title }} Cover"
                     class="cover-img"
                     loading="lazy" />

                {{-- Class r1/r2/r3 cho huy chương vàng/bạc/đồng --}}
                <div class="rank-num {{ $loop->iteration <= 3 ? 'r'.$loop->iteration : '' }}">
                  {{ $comic->trending_rank ?? $loop->iteration }}
                </div>
              </div>

              <p class="tcard-title">{{ $comic->title }}</p>

              {{-- Hiển thị thể loại dạng "Action · Fantasy" --}}
              <p class="tcard-genre">
                {{ $comic->genres->pluck('name')->join(' · ') }}
              </p>
            </a>
          @empty
            <p style="color:var(--text-sub);padding:20px;">No trending comics yet.</p>
          @endforelse

        </div>

        <button class="scroll-arrow scroll-left" id="trend-left" aria-label="Scroll left">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="scroll-arrow scroll-right" id="trend-right" aria-label="Scroll right">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
  </section>

  {{-- ==================== GENRE TABS ==================== --}}
  <section class="genre-section" id="genre-section">
    <div class="container">
      <div class="genre-tabs" id="genre-tabs" role="tablist">

        {{-- Nút "All" luôn đứng đầu --}}
        <a href="{{ route('genres') }}"
           class="genre-tab {{ !request('genre') ? 'active' : '' }}">
          All
        </a>

        {{-- ✅ @foreach render chip genre từ DB --}}
        @foreach($genres as $genre)
          <a href="{{ route('genres', ['genre' => $genre->slug]) }}"
             class="genre-tab {{ request('genre') === $genre->slug ? 'active' : '' }}">
            {{ $genre->icon ?? '' }} {{ $genre->name }}
          </a>
        @endforeach

      </div>
    </div>
  </section>

  {{-- ==================== LATEST UPDATES ==================== --}}
  <section class="comics-section" id="new-updates-section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">📚 Latest Chapter Updates</h2>
        <a href="{{ route('genres') }}" class="see-all">
          See All
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>

      <div class="comics-grid" id="new-updates-grid">

        {{--
          ✅ @foreach thay thế 15 thẻ <a> hardcode
          $comic->latestChapter trả về HasMany->latestOfMany()
          $comic->latestChapter->label      → "Ch.200"
          $comic->latestChapter->time_ago   → "2 hours ago"
        --}}
        @forelse($latestUpdates as $comic)
          @php
            $chapter    = $comic->latestChapter;
            $primaryTag = $comic->tags->first();   // lấy tag đầu tiên để hiển thị badge
          @endphp

          <a href="{{ route('comics.show', $comic->slug) }}"
             class="comic-card-sm"
             data-genre="{{ $comic->genres->first()?->slug }}">

            <div class="sm-cover">
              <img src="{{ $comic->cover_image }}"
                   alt="{{ $comic->title }}"
                   class="cover-img"
                   loading="lazy" />

              {{-- Badge: Ch.200, có class hot-badge / new-badge tuỳ theo tag --}}
              @if($chapter)
                <span class="sm-badge {{ match($primaryTag?->slug) {
                  'hot'     => 'hot-badge',
                  'new'     => 'new-badge',
                  default   => ''
                } }}">
                  {{ $chapter->label }}
                </span>
              @endif
            </div>

            <p class="sm-title">{{ $comic->title }}</p>

            <p class="sm-meta">
              {{ $comic->genres->first()?->name ?? 'Comics' }}
              &middot;
              {{ $chapter?->time_ago ?? 'Recently' }}
            </p>
          </a>
        @empty
          <p style="color:var(--text-sub);">No updates yet.</p>
        @endforelse

      </div>
    </div>
  </section>

</main>

{{-- ==================== MOBILE APP PROMO ==================== --}}
<section class="app-promo-section" id="download-app-banner">
  <div class="container">
    <div class="app-promo-inner">
      <div class="promo-content">
        <div class="promo-logo-badge">
          <svg width="48" height="48" viewBox="0 0 44 44" fill="none">
            <rect width="44" height="44" rx="12" fill="url(#promo-logo-grad)"/>
            <defs>
              <linearGradient id="promo-logo-grad" x1="0" y1="0" x2="44" y2="44">
                <stop offset="0%" stop-color="#FF5E36"/>
                <stop offset="100%" stop-color="#FF2A6D"/>
              </linearGradient>
            </defs>
            <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="Inter" font-weight="900" font-size="20" fill="white">WC</text>
          </svg>
          <span class="promo-brand">WebComics App</span>
        </div>
        <h2 class="promo-title">Read 10,000+ Exclusive Webtoons &amp; Manga Anywhere</h2>
        <p class="promo-subtitle">Daily free updates, offline reading mode, and high-definition full-color comics right in your pocket.</p>
        <div class="promo-download-actions">
          <a href="#" class="store-badge-btn apple-badge">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.31c.67-.82 1.12-1.96.99-3.11-.97.04-2.14.65-2.83 1.46-.62.72-1.16 1.88-1.01 3.01 1.09.08 2.19-.54 2.85-1.36z"/></svg>
            <span>App Store</span>
          </a>
          <a href="#" class="store-badge-btn google-badge">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5v-17c0-.55.3-1 .8-1.2.5-.2 1.1-.1 1.5.3l12 8.5c.4.3.6.8.6 1.3s-.2 1-.6 1.3l-12 8.5c-.4.4-1 .5-1.5.3-.5-.2-.8-.7-.8-1.2z"/></svg>
            <span>Google Play</span>
          </a>
          <div class="qr-code-box">
            <div class="qr-mock">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/><rect x="18" y="18" width="3" height="3"/></svg>
            </div>
            <span class="qr-label">Scan QR to Download</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
