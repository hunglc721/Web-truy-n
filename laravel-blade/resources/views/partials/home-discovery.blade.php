@php
  $discoverySections = [
    [
      'eyebrow' => 'Dành cho hôm nay',
      'title' => 'Gợi Ý Hôm Nay',
      'description' => 'Những bộ đáng mở ngay dựa trên độ nổi bật, chất lượng và nhịp cập nhật.',
      'icon' => '🎯',
      'items' => $dailyPicks ?? collect(),
      'link' => route('genres', ['sort' => 'rating']),
      'link_label' => 'Khám phá thêm',
    ],
    [
      'eyebrow' => 'Vừa xuất hiện',
      'title' => 'Truyện Mới Lên Kệ',
      'description' => 'Tác phẩm mới được thêm vào hệ thống, trước khi chúng bị thuật toán và đám đông giành hết sự chú ý.',
      'icon' => '🆕',
      'items' => $newArrivals ?? collect(),
      'link' => route('genres', ['sort' => 'latest']),
      'link_label' => 'Xem truyện mới',
    ],
  ];
@endphp

<style>
  .discovery-zone{padding:34px 0 8px}
  .discovery-zone-head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:6px}
  .discovery-zone-kicker{display:inline-flex;align-items:center;gap:7px;color:var(--primary);font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}
  .discovery-zone-title{margin:7px 0 0;font-size:clamp(24px,3vw,34px);letter-spacing:-.035em}
  .discovery-zone-copy{margin:7px 0 0;max-width:680px;color:var(--text-sub);line-height:1.65;font-size:13px}
  .discovery-feature{padding:28px 0}
  .discovery-head{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin-bottom:15px}
  .discovery-heading-wrap{min-width:0}
  .discovery-eyebrow{display:block;margin-bottom:5px;color:var(--text-muted);font-size:10px;font-weight:900;letter-spacing:.09em;text-transform:uppercase}
  .discovery-title{display:flex;align-items:center;gap:9px;margin:0;font-size:21px;letter-spacing:-.025em}
  .discovery-title-icon{display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:rgba(255,94,54,.11);border:1px solid rgba(255,94,54,.18);font-size:16px}
  .discovery-description{margin:6px 0 0;color:var(--text-sub);font-size:12px;line-height:1.55}
  .discovery-see-all{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;min-height:36px;padding:7px 12px;border-radius:999px;border:1px solid var(--border-color);background:var(--bg-surface-1);color:var(--text-main);font-size:11px;font-weight:850;text-decoration:none;transition:.2s}
  .discovery-see-all:hover{color:var(--primary);border-color:rgba(255,94,54,.42);transform:translateY(-1px)}
  .discovery-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px}
  .discovery-card{position:relative;min-width:0;text-decoration:none;color:inherit;border-radius:15px;background:var(--bg-surface-1);border:1px solid var(--border-color);overflow:hidden;transition:.22s}
  .discovery-card:hover{transform:translateY(-4px);border-color:rgba(255,94,54,.34);box-shadow:0 14px 28px rgba(0,0,0,.24)}
  .discovery-cover{position:relative;aspect-ratio:3/4;overflow:hidden;background:#0d1015}
  .discovery-cover::after{content:'';position:absolute;inset:auto 0 0;height:40%;background:linear-gradient(to top,rgba(7,9,13,.72),transparent)}
  .discovery-cover img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease}
  .discovery-card:hover .discovery-cover img{transform:scale(1.04)}
  .discovery-chapter,.discovery-rating,.discovery-rank{position:absolute;z-index:2;font-size:9.5px;font-weight:900;border-radius:999px;backdrop-filter:blur(8px)}
  .discovery-chapter{left:8px;bottom:8px;padding:5px 8px;background:rgba(255,94,54,.9);color:#fff}
  .discovery-rating{right:8px;top:8px;padding:5px 7px;background:rgba(7,9,13,.75);color:#fbbf24}
  .discovery-rank{left:8px;top:8px;width:29px;height:29px;display:grid;place-items:center;background:rgba(7,9,13,.8);color:#fff;border:1px solid rgba(255,255,255,.12);font-size:11px}
  .discovery-body{padding:11px 11px 12px}
  .discovery-card-title{margin:0;font-size:12.5px;line-height:1.45;font-weight:850;color:#fff;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:36px}
  .discovery-meta{display:flex;justify-content:space-between;gap:7px;margin-top:7px;color:var(--text-sub);font-size:9.5px;white-space:nowrap;overflow:hidden}
  .discovery-meta span{overflow:hidden;text-overflow:ellipsis}
  .discovery-genre-block{padding:24px 0 28px;border-top:1px solid rgba(255,255,255,.055)}
  .discovery-genre-block:first-of-type{margin-top:12px}

  @media(max-width:1180px){.discovery-grid{grid-template-columns:repeat(5,minmax(0,1fr))}.discovery-grid>.discovery-card:nth-child(n+6){display:none}}
  @media(max-width:900px){.discovery-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.discovery-grid>.discovery-card:nth-child(n+5){display:none}}
  @media(max-width:700px){
    .discovery-zone{padding-top:24px}
    .discovery-zone-head,.discovery-head{align-items:flex-start}
    .discovery-zone-copy{font-size:12px}
    .discovery-feature,.discovery-genre-block{padding:21px 0}
    .discovery-title{font-size:18px}
    .discovery-description{max-width:78vw}
    .discovery-grid{display:flex;overflow-x:auto;gap:10px;margin-left:-14px;margin-right:-14px;padding:2px 14px 10px;scroll-snap-type:x proximity;scrollbar-width:none}
    .discovery-grid::-webkit-scrollbar{display:none}
    .discovery-grid>.discovery-card:nth-child(n){display:block}
    .discovery-card{flex:0 0 142px;scroll-snap-align:start}
    .discovery-see-all{padding:6px 10px;font-size:10px}
  }
  @media(max-width:420px){.discovery-zone-title{font-size:24px}.discovery-description{display:none}.discovery-card{flex-basis:132px}}
</style>

<div class="roadmap-home-discovery discovery-zone" aria-label="Khám phá thêm">
  <div class="container">
    <header class="discovery-zone-head">
      <div>
        <span class="discovery-zone-kicker">✦ Khám phá theo nhịp đọc</span>
        <h2 class="discovery-zone-title">Tìm bộ tiếp theo mà không phải đào cả website</h2>
        <p class="discovery-zone-copy">Tách rõ truyện nên đọc hôm nay, truyện mới và những thể loại đang nóng để trang chủ có lý do tồn tại ngoài việc chứa rất nhiều ảnh bìa.</p>
      </div>
    </header>
  </div>

  @foreach($discoverySections as $section)
    @if($section['items']->isNotEmpty())
      <section class="comics-section discovery-feature">
        <div class="container">
          <div class="discovery-head">
            <div class="discovery-heading-wrap">
              <span class="discovery-eyebrow">{{ $section['eyebrow'] }}</span>
              <h2 class="discovery-title"><span class="discovery-title-icon">{{ $section['icon'] }}</span>{{ $section['title'] }}</h2>
              <p class="discovery-description">{{ $section['description'] }}</p>
            </div>
            <a href="{{ $section['link'] }}" class="discovery-see-all">{{ $section['link_label'] }} <span aria-hidden="true">→</span></a>
          </div>

          <div class="discovery-grid">
            @foreach($section['items'] as $comic)
              @php($chapter = $comic->latestChapter)
              <a href="{{ route('comics.show', $comic->slug) }}" class="discovery-card">
                <div class="discovery-cover">
                  <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" loading="lazy">
                  @if($chapter)<span class="discovery-chapter">{{ $chapter->label }}</span>@endif
                  <span class="discovery-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
                </div>
                <div class="discovery-body">
                  <h3 class="discovery-card-title">{{ $comic->title }}</h3>
                  <div class="discovery-meta">
                    <span>{{ $comic->genres->first()?->name ?? 'Truyện' }}</span>
                    <span>{{ $chapter?->time_ago ?? 'Mới' }}</span>
                  </div>
                </div>
              </a>
            @endforeach
          </div>
        </div>
      </section>
    @endif
  @endforeach

  @foreach(($hottestByGenre ?? collect()) as $group)
    @php($genre = $group['genre'])
    @php($items = $group['comics'])
    @if($items->isNotEmpty())
      <section class="comics-section discovery-genre-block">
        <div class="container">
          <div class="discovery-head">
            <div class="discovery-heading-wrap">
              <span class="discovery-eyebrow">Đang được đọc nhiều</span>
              <h2 class="discovery-title"><span class="discovery-title-icon">🔥</span>{{ $genre->name }} Nổi Bật</h2>
            </div>
            <a href="{{ route('genres', ['genre' => $genre->slug, 'sort' => 'hot']) }}" class="discovery-see-all">Xem {{ $genre->name }} <span aria-hidden="true">→</span></a>
          </div>

          <div class="discovery-grid">
            @foreach($items as $comic)
              @php($chapter = $comic->latestChapter)
              <a href="{{ route('comics.show', $comic->slug) }}" class="discovery-card">
                <div class="discovery-cover">
                  <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" loading="lazy">
                  <span class="discovery-rank">{{ $loop->iteration }}</span>
                  @if($chapter)<span class="discovery-chapter">{{ $chapter->label }}</span>@endif
                  <span class="discovery-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
                </div>
                <div class="discovery-body">
                  <h3 class="discovery-card-title">{{ $comic->title }}</h3>
                  <div class="discovery-meta"><span>{{ $genre->name }}</span><span>{{ $comic->formatted_views }} lượt đọc</span></div>
                </div>
              </a>
            @endforeach
          </div>
        </div>
      </section>
    @endif
  @endforeach
</div>
