@extends('layouts.main')

@section('title', ($siteSettings['site_name'] ?? 'WebComics') . ' - Cổng Đọc Truyện Tranh Webtoon & Manga Đỉnh Cao')

@section('meta')
<meta name="description" content="{{ $siteSettings['meta_description'] ?? 'Khám phá truyện tranh cập nhật mới, lịch phát hành và truyện thịnh hành trên WebComics.' }}" />
<meta name="keywords" content="{{ $siteSettings['seo_keywords'] ?? 'đọc truyện,manga,manhwa,manhua,webtoon' }}" />
@endsection

@section('content')
<main id="main-content" style="padding-bottom: 50px;">

  {{-- ================= SECTION 1: HERO SPOTLIGHT BANNER ================= --}}
  @if(isset($banners) && $banners->isNotEmpty())
  <section class="banner-slider-section" id="hero-banner-section" style="max-width: 1320px; margin: 0 auto; padding: 20px 20px 28px;">
    <div class="banner-carousel" id="banner-carousel" style="border-radius: 28px; background: rgba(14, 21, 38, 0.85); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 20px 60px rgba(0,0,0,0.6); position: relative; overflow: hidden;">
      
      <!-- Ambient Glows -->
      <div class="ambient-glow" style="position: absolute; top: -100px; left: -100px; width: 380px; height: 380px; background: rgba(139, 92, 246, 0.25); border-radius: 50%; filter: blur(120px); pointer-events: none; z-index: 0;"></div>
      <div style="position: absolute; bottom: -100px; right: -100px; width: 380px; height: 380px; background: rgba(255, 107, 53, 0.2); border-radius: 50%; filter: blur(120px); pointer-events: none; z-index: 0;"></div>

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
                <span class="banner-badge">🔥 NỔI BẬT</span>
                <span class="banner-badge-cyber">Spotlight 4K</span>
                <span style="font-size: 11.5px; font-weight: 700; color: #fbbf24; background: rgba(251, 191, 36, 0.15); border: 1px solid rgba(251, 191, 36, 0.3); padding: 3px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;">★ 4.9/5</span>
              </div>
              <h2 class="banner-title">{{ $banner->title }}</h2>
              <div class="banner-actions" style="margin-top: 14px;">
                <span class="banner-btn-explore btn-neon-primary" style="padding: 10px 24px; font-size: 14px; font-weight: 800;">Khám Phá Ngay →</span>
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

  {{-- ================= SECTION 2: QUICK GENRE BADGES RIBBON ================= --}}
  <section style="max-width: 1320px; margin: 0 auto; padding: 10px 20px 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
      <h2 style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; display: flex; align-items: center; gap: 6px; margin: 0;">
        <span style="color: #ff6b35;">🔥</span> THỂ LOẠI ĐANG SỐT
      </h2>
      <a href="{{ route('genres') }}" style="font-size: 12.5px; font-weight: 700; color: #a78bfa; text-decoration: none; display: flex; align-items: center; gap: 4px;">
        Xem tất cả thể loại &rarr;
      </a>
    </div>
    <div class="mangakai-genre-ribbon" id="genre-tabs" role="tablist">
      <a href="{{ route('genres') }}" class="mangakai-genre-chip {{ !request('genre') ? 'active' : '' }}">
        ✨ Tất Cả
      </a>
      @foreach($genres as $genre)
        <a href="{{ route('genres', ['genre' => $genre->slug]) }}" class="mangakai-genre-chip {{ request('genre') === $genre->slug ? 'active' : '' }}">
          {{ $genre->icon ?? '#' }} {{ $genre->name }}
        </a>
      @endforeach
    </div>
  </section>

  {{-- ================= SECTION 3: CONTINUE READING (TIẾP TỤC ĐỌC) ================= --}}
  @auth
    @if(isset($recentReadings) && $recentReadings->isNotEmpty())
      <section style="max-width: 1320px; margin: 0 auto; padding: 10px 20px 28px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
          <div>
            <span class="cyber-badge-sub">TIẾP NỐI CÂU CHUYỆN</span>
            <h2 style="font-size: 20px; font-weight: 800; color: #fff; margin: 0;">🕘 Tiếp Tục Đọc</h2>
          </div>
          <a href="{{ route('user.history') }}" style="font-size: 12.5px; font-weight: 700; color: #a78bfa; text-decoration: none;">Toàn Bộ Lịch Sử &rarr;</a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
          @foreach($recentReadings as $history)
            @php
              $comic = $history->comic;
              $chapter = $history->chapter;
              $percent = max(5, min(100, (int) round($history->scroll_percent)));
            @endphp
            @if($comic && $chapter)
              <div class="continue-reading-card card-shine-effect">
                <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug ?: 'chapter-' . $chapter->chapter_number]) }}" style="flex-shrink: 0;" class="comic-card-poster">
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
      </section>
    @endif
  @else
    {{-- Guest Continue Reading --}}
    <section id="guest-continue-reading" style="display: none; max-width: 1320px; margin: 0 auto; padding: 10px 20px 28px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
        <div>
          <span class="cyber-badge-sub">ĐỌC DỞ DANG</span>
          <h2 style="font-size: 20px; font-weight: 800; color: #fff; margin: 0;">🕘 Tiếp Tục Đọc</h2>
        </div>
        <a href="{{ route('login') }}" style="font-size: 12px; color: #a78bfa; text-decoration: none;">Đăng nhập để đồng bộ lịch sử ☁️</a>
      </div>
      <div id="guest-history-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;"></div>
    </section>
  @endauth

  {{-- ================= SECTION 4: MAIN 2-COLUMN SPLIT (8 COLS / 4 COLS) ================= --}}
  <section style="max-width: 1320px; margin: 0 auto; padding: 10px 20px 36px;">
    <div class="mangakai-layout-grid">
      
      {{-- ── CỘT TRÁI (8 COLS): MỚI CẬP NHẬT & DISCOVERY ── --}}
      <div style="display: flex; flex-direction: column; gap: 36px; min-width: 0;">
        
        <!-- Mới Cập Nhật Section -->
        <div id="new-updates-section">
          <div style="display: flex; align-items: flex-end; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 14px; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
              <div style="display: flex; align-items: center; gap: 8px;">
                <h2 class="section-title" style="font-size: 22px; font-weight: 900; color: #fff; margin: 0; letter-spacing: -0.3px;">
                  📚 Mới Cập Nhật
                </h2>
                <span id="homeCurrentGenreBadge" style="font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 999px; background: rgba(139, 92, 246, 0.2); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.4);">
                  Tất cả
                </span>
              </div>
              <p style="font-size: 12.5px; color: #94a3b8; margin: 4px 0 0;">Chương mới phát hành với bản dịch chất lượng cao 4K</p>
            </div>

            <a href="{{ route('genres') }}" class="see-all" style="padding: 7px 14px; border-radius: 12px; background: rgba(21, 30, 52, 0.8); border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; font-size: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
              <span style="color: #00f5d4;">⚙</span> Bộ lọc sâu &rarr;
            </a>
          </div>

          <div class="comics-grid" id="new-updates-grid" style="grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 18px;">
            @forelse($latestUpdates as $comic)
              @php($chapter = $comic->latestChapter)
              @php($primaryTag = $comic->tags->first())
              <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm card-shine-effect" data-genre="{{ $comic->genres->first()?->slug }}">
                <div class="sm-cover comic-card-poster">
                  <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
                  @if($chapter)
                    <span class="sm-badge {{ $primaryTag?->slug === 'hot' ? 'hot-badge badge-chip-hot' : ($primaryTag?->slug === 'new' ? 'new-badge badge-chip-new' : '') }}">
                      {{ $chapter->label }}
                    </span>
                  @endif
                  <span class="sm-rating badge-chip-glow badge-chip-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
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
              <div style="grid-column: 1/-1; color: var(--text-sub); padding: 40px; text-align: center;">Chưa có chương mới.</div>
            @endforelse
          </div>
        </div>

        <!-- Gợi Ý Hôm Nay (Daily Picks) - Required by RoadmapFeaturesTest -->
        @if(isset($dailyPicks) && $dailyPicks->isNotEmpty())
        <div>
          <div style="display: flex; align-items: flex-end; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; margin-bottom: 18px;">
            <div>
              <span class="cyber-badge-sub">ĐƯỢC CHỌN LỌC</span>
              <h2 style="font-size: 20px; font-weight: 800; color: #fff; margin: 0;">✨ Gợi Ý Hôm Nay</h2>
            </div>
            <a href="{{ route('genres') }}" style="font-size: 12px; font-weight: 700; color: #a78bfa; text-decoration: none;">Xem Thêm &rarr;</a>
          </div>
          <div class="comics-grid" style="grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 16px;">
            @foreach($dailyPicks->take(6) as $comic)
              <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm card-shine-effect">
                <div class="sm-cover comic-card-poster">
                  <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
                  <span class="sm-rating badge-chip-glow badge-chip-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
                </div>
                <div class="sm-info">
                  <h3 class="sm-title">{{ $comic->title }}</h3>
                  <div class="sm-meta">
                    <span>{{ $comic->genres->first()?->name ?? 'Truyện' }}</span>
                    <span>{{ $comic->latestChapter?->label ?? 'Đang ra' }}</span>
                  </div>
                </div>
              </a>
            @endforeach
          </div>
        </div>
        @endif

        <!-- Truyện Mới Lên Kệ (New Arrivals) - Required by RoadmapFeaturesTest -->
        @if(isset($newArrivals) && $newArrivals->isNotEmpty())
        <div>
          <div style="display: flex; align-items: flex-end; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; margin-bottom: 18px;">
            <div>
              <span class="cyber-badge-sub">TÂN BINH ĐỔ BỘ</span>
              <h2 style="font-size: 20px; font-weight: 800; color: #fff; margin: 0;">🆕 Truyện Mới Lên Kệ</h2>
            </div>
            <a href="{{ route('genres') }}" style="font-size: 12px; font-weight: 700; color: #a78bfa; text-decoration: none;">Xem Thêm &rarr;</a>
          </div>
          <div class="comics-grid" style="grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 16px;">
            @foreach($newArrivals->take(6) as $comic)
              <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm card-shine-effect">
                <div class="sm-cover comic-card-poster">
                  <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
                  <span class="sm-badge badge-chip-new">MỚI</span>
                  <span class="sm-rating badge-chip-glow badge-chip-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
                </div>
                <div class="sm-info">
                  <h3 class="sm-title">{{ $comic->title }}</h3>
                  <div class="sm-meta">
                    <span>{{ $comic->genres->first()?->name ?? 'Truyện' }}</span>
                    <span>{{ $comic->latestChapter?->label ?? 'Đang ra' }}</span>
                  </div>
                </div>
              </a>
            @endforeach
          </div>
        </div>
        @endif

      </div>

      {{-- ── CỘT PHẢI (4 COLS): ASIDE BXH TOP HOT & LỊCH RA HÔM NAY ── --}}
      <aside style="display: flex; flex-direction: column; gap: 24px; position: sticky; top: calc(var(--header-height, 72px) + 20px);">
        
        <!-- Bảng Xếp Hạng Top Hot Widget -->
        <div class="mangakai-widget-card" id="trending-section">
          <div class="mangakai-widget-header">
            <div class="mangakai-widget-title">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; background: rgba(245, 158, 11, 0.2); color: #fbbf24; font-size: 14px;">🏆</span>
              <span class="hero-title" style="font-size: 14px; margin: 0;">Bảng Xếp Hạng</span>
            </div>
            <span style="font-size: 11px; font-family: monospace; color: #00f5d4; display: inline-flex; align-items: center; gap: 4px;">
              ● Cập nhật 10m
            </span>
          </div>

          <!-- Ranking Period Tabs -->
          <div class="mangakai-rank-tabs ranking-time-tabs" role="tablist">
            <button type="button" class="mangakai-rank-tab-btn rank-tab active" data-period="all">Ngày</button>
            <button type="button" class="mangakai-rank-tab-btn rank-tab" data-period="week">Tuần</button>
            <button type="button" class="mangakai-rank-tab-btn rank-tab" data-period="month">Tháng</button>
          </div>

          <!-- Top List -->
          <div id="trending-list" class="trending-list" style="display: flex; flex-direction: column; gap: 6px; overflow-x: visible;">
            @forelse($trendingComics as $comic)
              @php($rank = $loop->iteration)
              <a href="{{ route('comics.show', $comic->slug) }}" class="mangakai-rank-item trending-card card-shine-effect" style="width: auto;" title="{{ $comic->title }}">
                <span class="mangakai-rank-num rank-num rank-num-{{ $rank }} {{ $rank === 1 ? 'rank-num-1' : ($rank === 2 ? 'rank-num-2' : ($rank === 3 ? 'rank-num-3' : '')) }}" style="position: static; padding: 0; background: transparent; border: none;">
                  {{ $rank < 10 ? '0' . $rank : $rank }}
                </span>
                <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="mangakai-rank-thumb tcard-cover" style="width: 44px; height: 58px;" loading="lazy">
                <div class="mangakai-rank-info">
                  <div class="mangakai-rank-title tcard-title" style="margin: 0 0 3px;">{{ $comic->title }}</div>
                  <div class="mangakai-rank-meta tcard-genre" style="margin: 0;">
                    <span style="color: #00f5d4; font-weight: 700;">Ch.{{ $comic->latestChapter?->chapter_number ?? 1 }}</span>
                    <span>•</span>
                    <span style="font-family: monospace;">{{ $comic->formatted_views ?? number_format($comic->views) }}</span>
                  </div>
                </div>
                <span style="color: #64748b; font-size: 16px;">&rsaquo;</span>
              </a>
            @empty
              <p style="color: var(--text-sub); padding: 15px; text-align: center;">Chưa có dữ liệu thịnh hành.</p>
            @endforelse
          </div>
        </div>

        <!-- Weekly Schedule Widget Teaser -->
        <div class="mangakai-widget-card" style="background: linear-gradient(145deg, rgba(14, 21, 38, 0.85), rgba(21, 30, 52, 0.65));">
          <div class="mangakai-widget-header">
            <div class="mangakai-widget-title">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; background: rgba(255, 107, 53, 0.2); color: #ff6b35; font-size: 14px;">🔔</span>
              <span>Lịch Ra Hôm Nay</span>
            </div>
            <a href="{{ route('schedule') }}" style="font-size: 11.5px; font-weight: 700; color: #00f5d4; text-decoration: none;">Xem tuần &rarr;</a>
          </div>

          <div style="display: flex; flex-direction: column; gap: 8px;">
            @php($schedComics = $latestUpdates->take(3))
            @foreach($schedComics as $sc)
              <a href="{{ route('comics.show', $sc->slug) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 12px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); text-decoration: none; color: inherit; transition: all 0.2s ease;">
                <div style="min-width: 0; flex: 1; padding-right: 10px;">
                  <h4 style="font-size: 12.5px; font-weight: 700; color: #fff; margin: 0 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $sc->title }}</h4>
                  <span style="font-size: 10.5px; color: #94a3b8;">Ra mắt: 20:00 • Chap {{ ($sc->latestChapter?->chapter_number ?? 1) + 1 }}</span>
                </div>
                <span style="font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; background: rgba(0, 245, 212, 0.15); color: #00f5d4; border: 1px solid rgba(0, 245, 212, 0.3); white-space: nowrap;">
                  Hôm nay
                </span>
              </a>
            @endforeach
          </div>
        </div>

      </aside>

    </div>
  </section>

  {{-- ================= SECTION 5: KHU VỰC THÀNH VIÊN (@auth) ================= --}}
  @auth
  <section style="max-width: 1320px; margin: 0 auto; padding: 10px 20px 20px;">
    <div style="display: flex; align-items: flex-end; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; margin-bottom: 18px;">
      <div>
        <span class="cyber-badge-sub">TIỆN ÍCH CỦA BẠN</span>
        <h2 style="font-size: 20px; font-weight: 800; color: #fff; margin: 0;">👤 Khu Vực Thành Viên</h2>
      </div>
      <a href="{{ route('genres') }}" class="see-all">Khám Phá Thêm &rarr;</a>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px;">
      <a href="{{ route('user.library') }}" class="browse-card card-shine-effect" style="padding: 18px; text-decoration: none; border-radius: 16px; background: rgba(14,21,38,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 10px; font-weight: 700; color: #fff;">
        <span style="font-size: 22px;">📚</span> <span>Tủ Truyện</span>
      </a>
      <a href="{{ route('user.history') }}" class="browse-card card-shine-effect" style="padding: 18px; text-decoration: none; border-radius: 16px; background: rgba(14,21,38,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 10px; font-weight: 700; color: #fff;">
        <span style="font-size: 22px;">🕘</span> <span>Lịch Sử Đọc</span>
      </a>
      <a href="{{ route('user.likes') }}" class="browse-card card-shine-effect" style="padding: 18px; text-decoration: none; border-radius: 16px; background: rgba(14,21,38,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 10px; font-weight: 700; color: #fff;">
        <span style="font-size: 22px;">❤️</span> <span>Yêu Thích</span>
      </a>
      <a href="{{ route('user.comments') }}" class="browse-card card-shine-effect" style="padding: 18px; text-decoration: none; border-radius: 16px; background: rgba(14,21,38,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 10px; font-weight: 700; color: #fff;">
        <span style="font-size: 22px;">💬</span> <span>Bình Luận</span>
      </a>
      <a href="{{ route('user.ratings') }}" class="browse-card card-shine-effect" style="padding: 18px; text-decoration: none; border-radius: 16px; background: rgba(14,21,38,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 10px; font-weight: 700; color: #fff;">
        <span style="font-size: 22px;">⭐</span> <span>Đánh Giá</span>
      </a>
    </div>
  </section>
  @endauth

</main>
@endsection

@push('scripts')
<script>
  // Ranking Tab Switching Animation
  document.querySelectorAll('.rank-tab').forEach(tab => {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.rank-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      const list = document.getElementById('trending-list');
      if (list) {
        list.style.opacity = '0.4';
        list.style.transition = 'opacity 0.2s ease';
        setTimeout(() => {
          list.style.opacity = '1';
        }, 120);
      }
    });
  });

  // Banner Carousel Auto-Cycle & Controls
  (() => {
    const track = document.getElementById('banner-track');
    if (!track) return;
    const slides = track.querySelectorAll('.banner-slide');
    const dots = document.querySelectorAll('.banner-dot');
    const prevBtn = document.getElementById('banner-prev');
    const nextBtn = document.getElementById('banner-next');
    if (slides.length <= 1) return;

    let currentIndex = 0;
    const total = slides.length;

    const goTo = (idx) => {
      currentIndex = (idx + total) % total;
      slides.forEach((s, i) => s.classList.toggle('active', i === currentIndex));
      dots.forEach((d, i) => d.classList.toggle('active', i === currentIndex));
    };

    prevBtn?.addEventListener('click', () => goTo(currentIndex - 1));
    nextBtn?.addEventListener('click', () => goTo(currentIndex + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => goTo(i)));

    setInterval(() => goTo(currentIndex + 1), 6500);
  })();
</script>
@endpush
