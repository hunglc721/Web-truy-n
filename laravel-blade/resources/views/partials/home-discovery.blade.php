@php
  $discoverySections = [
    ['title' => '🎯 Gợi Ý Hôm Nay', 'items' => $dailyPicks ?? collect(), 'link' => route('genres', ['sort' => 'rating'])],
    ['title' => '🆕 Truyện Mới Lên Kệ', 'items' => $newArrivals ?? collect(), 'link' => route('genres', ['sort' => 'latest'])],
  ];
@endphp

<div class="roadmap-home-discovery" aria-label="Khám phá thêm">
  @foreach($discoverySections as $section)
    @if($section['items']->isNotEmpty())
      <section class="comics-section roadmap-discovery-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">{{ $section['title'] }}</h2>
            <a href="{{ $section['link'] }}" class="see-all">Xem Tất Cả →</a>
          </div>
          <div class="comics-grid">
            @foreach($section['items'] as $comic)
              @php($chapter = $comic->latestChapter)
              <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm">
                <div class="sm-cover">
                  <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
                  @if($chapter)<span class="sm-badge">{{ $chapter->label }}</span>@endif
                  <span class="sm-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
                </div>
                <div class="sm-info">
                  <h3 class="sm-title">{{ $comic->title }}</h3>
                  <div class="sm-meta">
                    <span class="sm-genre">{{ $comic->genres->first()?->name ?? 'Truyện' }}</span>
                    <span class="sm-time">{{ $chapter?->time_ago ?? 'Mới' }}</span>
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
      <section class="comics-section roadmap-discovery-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">🔥 {{ $genre->name }} Nổi Bật</h2>
            <a href="{{ route('genres', ['genre' => $genre->slug, 'sort' => 'hot']) }}" class="see-all">Xem {{ $genre->name }} →</a>
          </div>
          <div class="comics-grid roadmap-genre-hot-grid">
            @foreach($items as $comic)
              @php($chapter = $comic->latestChapter)
              <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm">
                <div class="sm-cover">
                  <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
                  @if($chapter)<span class="sm-badge hot-badge">{{ $chapter->label }}</span>@endif
                  <span class="sm-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
                </div>
                <div class="sm-info">
                  <h3 class="sm-title">{{ $comic->title }}</h3>
                  <div class="sm-meta">
                    <span class="sm-genre">{{ $genre->name }}</span>
                    <span class="sm-time">{{ $comic->formatted_views }} lượt đọc</span>
                  </div>
                </div>
              </a>
            @endforeach
          </div>
        </div>
      </section>
    @endif
  @endforeach
</div>
