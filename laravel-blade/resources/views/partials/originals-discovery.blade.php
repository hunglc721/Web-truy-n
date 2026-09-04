<div class="roadmap-originals-discovery">
  @foreach([
    ['title' => '🔥 Original Đang Thịnh Hành', 'items' => $latestTrends ?? collect()],
    ['title' => '🆕 Original Vừa Cập Nhật', 'items' => $recentOriginalUpdates ?? collect()],
  ] as $section)
    @if($section['items']->isNotEmpty())
      <section class="comics-section roadmap-discovery-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">{{ $section['title'] }}</h2>
            <a href="{{ route('originals') }}" class="see-all">Tất Cả Originals →</a>
          </div>
          <div class="comics-grid roadmap-genre-hot-grid">
            @foreach($section['items'] as $comic)
              @php($chapter = $comic->latestChapter)
              <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm">
                <div class="sm-cover">
                  <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
                  <span class="sm-badge hot-badge">ORIGINAL</span>
                  <span class="sm-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
                </div>
                <div class="sm-info">
                  <h3 class="sm-title">{{ $comic->title }}</h3>
                  <div class="sm-meta">
                    <span class="sm-genre">{{ $comic->genres->first()?->name ?? 'Original' }}</span>
                    <span class="sm-time">{{ $chapter?->time_ago ?? $comic->formatted_views . ' lượt đọc' }}</span>
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
