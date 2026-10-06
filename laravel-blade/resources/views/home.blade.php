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
          <div class="banner-ambient-glow ambient-glow" style="background-image: url('{{ $banner->display_image }}');" aria-hidden="true"></div>
          <a href="{{ route('banners.click', $banner) }}" class="banner-link">
            <div class="banner-img-container">
              <img src="{{ $banner->display_image }}" alt="{{ $banner->title }}" class="banner-hero-img" loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
            </div>
            <div class="banner-overlay">
              <div class="banner-chips">
                <span class="banner-badge">✨ Nổi Bật</span>
                <span class="banner-badge-cyber">Spotlight Cinema</span>
              </div>
              <h2 class="banner-title">{{ $banner->title }}</h2>
              <div class="banner-actions" style="margin-top: 14px;">
                <span class="banner-btn-explore btn-neon-primary" style="padding: 10px 22px; font-size: 14px;">Khám Phá Ngay →</span>
              </div>
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

  {{-- ── KHỐI TIẾP TỤC ĐỌC (CONTINUE READING) ── --}}
  @auth
    @if(isset($recentReadings) && $recentReadings->isNotEmpty())
      <section class="comics-section" style="padding-top: 12px; margin-bottom: -6px;">
        <div class="container">
          <div class="section-header">
            <div class="section-heading-group">
              <span class="cyber-badge-sub">TIẾP NỐI CÂU CHUYỆN</span>
              <h2 class="section-title" style="margin-top: 2px;">🕘 Tiếp Tục Đọc</h2>
            </div>
            <a href="{{ route('user.history') }}" class="see-all">Toàn Bộ Lịch Sử →</a>
          </div>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
            @foreach($recentReadings as $history)
              @php
                $comic = $history->comic;
                $chapter = $history->chapter;
                $percent = max(5, min(100, (int) round($history->scroll_percent)));
              @endphp
              @if($comic && $chapter)
                <div class="continue-reading-card">
                  <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug ?: 'chapter-' . $chapter->chapter_number]) }}" style="flex-shrink: 0;">
                    <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="continue-reading-cover" loading="lazy" />
                  </a>
                  <div style="flex: 1; min-width: 0;">
                    <h3 style="font-size: 14.5px; font-weight: 700; color: #fff; margin: 0 0 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                      <a href="{{ route('comics.show', $comic->slug) }}" style="color: inherit; text-decoration: none;">{{ $comic->title }}</a>
                    </h3>
                    <div style="font-size: 12px; color: var(--text-sub); margin-bottom: 8px;">
                      Đang đọc: <strong style="color: #8B5CF6;">Ch.{{ $chapter->chapter_number }}</strong>
                    </div>
                    <div style="height: 6px; background: rgba(255,255,255,0.08); border-radius: 999px; overflow: hidden; margin-bottom: 8px;">
                      <div style="height: 100%; width: {{ $percent }}%; background: linear-gradient(90deg, #8B5CF6, #EC4899); border-radius: 999px; box-shadow: 0 0 8px rgba(139,92,246,0.6);"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                      <span style="font-size: 11px; color: var(--text-sub); font-weight: 600;">Tiến độ: {{ $percent }}%</span>
                      <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug ?: 'chapter-' . $chapter->chapter_number]) }}" class="btn-sm" style="font-size: 11.5px; padding: 4px 10px; background: linear-gradient(135deg, #8B5CF6, #EC4899); color: #fff; border-radius: 6px; text-decoration: none; font-weight: 700; box-shadow: 0 2px 8px rgba(139,92,246,0.35);">Đọc tiếp →</a>
                    </div>
                  </div>
                </div>
              @endif
            @endforeach
          </div>
        </div>
      </section>
    @endif
  @else
    {{-- Guest Continue Reading (Loaded dynamically from localStorage) --}}
    <section class="comics-section" id="guest-continue-reading" style="display: none; padding-top: 12px; margin-bottom: -6px;">
      <div class="container">
        <div class="section-header">
          <div class="section-heading-group">
            <span class="cyber-badge-sub">ĐỌC DỞ DANG</span>
            <h2 class="section-title" style="margin-top: 2px;">🕘 Tiếp Tục Đọc</h2>
          </div>
          <a href="{{ route('login') }}" class="see-all" style="font-size: 12px;">Đăng nhập để đồng bộ lịch sử ☁️</a>
        </div>
        <div id="guest-history-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;"></div>
      </div>
    </section>
  @endauth

  {{-- ── TOP CHARTS / BXH THỊNH HÀNH ── --}}
  <section class="hero-section" id="trending-section" aria-label="Truyện thịnh hành">
    <div class="hero-content-wrap">
      <div class="ranking-header-wrap">
        <div class="section-heading-group">
          <span class="cyber-badge-sub">HOTTEST MANGA & WEBTOON</span>
          <h2 class="hero-title" style="margin-top: 2px;">🔥 Bảng Xếp Hạng Thịnh Hành</h2>
        </div>
        <div class="ranking-time-tabs" role="tablist">
          <button type="button" class="rank-tab active" data-period="all">Hôm Nay</button>
          <button type="button" class="rank-tab" data-period="week">Tuần Này</button>
          <button type="button" class="rank-tab" data-period="month">Tháng Này</button>
        </div>
      </div>
      <div class="trending-scroll-wrap">
        <div class="trending-list" id="trending-list">
          @forelse($trendingComics as $comic)
          <a href="{{ route('comics.show',$comic->slug) }}" class="trending-card card-shine-effect" aria-label="{{ $comic->title }}">
            <div class="tcard-cover comic-card-poster">
              <img src="{{ $comic->cover_url }}" alt="Bìa {{ $comic->title }}" class="cover-img" loading="lazy">
              <div class="rank-num rank-badge-medal rank-num-{{ $loop->iteration }} {{ $loop->iteration === 1 ? 'rank-badge-gold r1' : ($loop->iteration === 2 ? 'rank-badge-silver r2' : ($loop->iteration === 3 ? 'rank-badge-bronze r3' : ($loop->iteration <= 3 ? 'r'.$loop->iteration : ''))) }}">{{ $comic->trending_rank ?? $loop->iteration }}</div>
              <span class="badge-chip-glow badge-chip-rating" style="position: absolute; bottom: 8px; right: 8px; z-index: 2;">★ {{ number_format($comic->avg_rating, 1) }}</span>
            </div>
            <p class="tcard-title">{{ $comic->title }}</p>
            <p class="tcard-genre">{{ $comic->genres->pluck('name')->join(' · ') }}</p>
          </a>
          @empty<p style="color:var(--text-sub);padding:20px">Chưa có dữ liệu thịnh hành.</p>@endforelse
        </div>
        <button class="scroll-arrow scroll-left" id="trend-left" aria-label="Cuộn trái"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button>
        <button class="scroll-arrow scroll-right" id="trend-right" aria-label="Cuộn phải"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button>
      </div>
    </div>
  </section>

  {{-- ── QUICK GENRE PILLS ── --}}
  <section class="genre-section" id="genre-section">
    <div class="container">
      <div class="genre-tabs-wrapper">
        <button type="button" class="genre-scroll-btn genre-scroll-left" aria-label="Cuộn trái" title="Xem các mục trước">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div class="genre-tabs" id="genre-tabs" role="tablist">
          <a href="{{ route('genres') }}" class="genre-tab {{ !request('genre')?'active':'' }}">✨ Tất Cả Thể Loại</a>
          @foreach($genres as $genre)
            <a href="{{ route('genres',['genre'=>$genre->slug]) }}" class="genre-tab {{ request('genre')===$genre->slug?'active':'' }}">{{ $genre->icon }} {{ $genre->name }}</a>
          @endforeach
        </div>
        <button type="button" class="genre-scroll-btn genre-scroll-right" aria-label="Cuộn phải" title="Xem thêm thể loại">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
  </section>

  {{-- ── LƯỚI CHƯƠNG MỚI CẬP NHẬT ── --}}
  <section class="comics-section" id="new-updates-section">
    <div class="container">
      <div class="section-header">
        <div class="section-heading-group">
          <span class="cyber-badge-sub">TƯƠI MỚI MỖI GIỜ</span>
          <h2 class="section-title" style="margin-top: 2px;">📚 Chương Mới Cập Nhật</h2>
        </div>
        <a href="{{ route('genres') }}" class="see-all">Xem Tất Cả →</a>
      </div>
      <div class="comics-grid" id="new-updates-grid">
        @forelse($latestUpdates as $comic)
          @php($chapter=$comic->latestChapter)
          @php($primaryTag=$comic->tags->first())
          <a href="{{ route('comics.show',$comic->slug) }}" class="comic-card-sm card-shine-effect" data-genre="{{ $comic->genres->first()?->slug }}">
            <div class="sm-cover comic-card-poster">
              <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
              @if($chapter)
                <span class="sm-badge {{ $primaryTag?->slug==='hot'?'hot-badge badge-chip-hot':($primaryTag?->slug==='new'?'new-badge badge-chip-new':'') }}">
                  {{ $chapter->label }}
                </span>
              @endif
              <span class="sm-rating badge-chip-glow badge-chip-rating">★ {{ number_format($comic->avg_rating,1) }}</span>
            </div>
            <div class="sm-info">
              <h3 class="sm-title">{{ $comic->title }}</h3>
              <div class="sm-meta">
                <span>{{ $comic->genres->first()?->name ?? 'Truyện' }}</span>
                <span>{{ $chapter?->time_ago ?? 'Mới cập nhật' }}</span>
              </div>
            </div>
          </a>
        @empty
          <div style="grid-column:1/-1;color:var(--text-sub);padding:30px;text-align:center">Chưa có chương mới.</div>
        @endforelse
      </div>
    </div>
  </section>

  @auth
  <section class="comics-section" style="padding-top:8px">
    <div class="container">
      <div class="section-header">
        <div class="section-heading-group">
          <span class="cyber-badge-sub">TIỆN ÍCH CỦA BẠN</span>
          <h2 class="section-title" style="margin-top: 2px;">👤 Khu Vực Thành Viên</h2>
        </div>
        <a href="{{ route('genres') }}" class="see-all">Khám Phá Thêm →</a>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px">
        <a href="{{ route('user.library') }}" class="browse-card card-shine-effect" style="padding:20px;text-decoration:none;border-radius:14px;background:rgba(17,24,39,0.7);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;font-weight:700;color:#fff;">
          <span style="font-size:22px;">📚</span> <span>Tủ Truyện</span>
        </a>
        <a href="{{ route('user.history') }}" class="browse-card card-shine-effect" style="padding:20px;text-decoration:none;border-radius:14px;background:rgba(17,24,39,0.7);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;font-weight:700;color:#fff;">
          <span style="font-size:22px;">🕘</span> <span>Lịch Sử Đọc</span>
        </a>
        <a href="{{ route('user.likes') }}" class="browse-card card-shine-effect" style="padding:20px;text-decoration:none;border-radius:14px;background:rgba(17,24,39,0.7);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;font-weight:700;color:#fff;">
          <span style="font-size:22px;">❤️</span> <span>Yêu Thích</span>
        </a>
        <a href="{{ route('user.comments') }}" class="browse-card card-shine-effect" style="padding:20px;text-decoration:none;border-radius:14px;background:rgba(17,24,39,0.7);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;font-weight:700;color:#fff;">
          <span style="font-size:22px;">💬</span> <span>Bình Luận</span>
        </a>
        <a href="{{ route('user.ratings') }}" class="browse-card card-shine-effect" style="padding:20px;text-decoration:none;border-radius:14px;background:rgba(17,24,39,0.7);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;font-weight:700;color:#fff;">
          <span style="font-size:22px;">⭐</span> <span>Đánh Giá</span>
        </a>
      </div>
    </div>
  </section>
  @endauth
</main>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('.rank-tab').forEach(tab => {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.rank-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      const list = document.getElementById('trending-list');
      if (list) {
        list.style.opacity = '0.5';
        list.style.transition = 'opacity 0.2s ease';
        setTimeout(() => {
          list.style.opacity = '1';
        }, 120);
      }
    });
  });
</script>
@endpush

