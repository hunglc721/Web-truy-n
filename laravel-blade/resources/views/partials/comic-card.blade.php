@php($chapter = $comic->latestChapter)
<a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm" data-genre="{{ $comic->genres->first()?->slug }}">
  <div class="sm-cover"><img src="{{ $comic->cover_url }}" alt="Bìa {{ $comic->title }}" class="cover-img" loading="lazy">@if($chapter)<span class="sm-badge">{{ $chapter->label }}</span>@endif<span class="sm-rating" aria-label="Điểm đánh giá {{ number_format($comic->avg_rating, 1) }}">★ {{ number_format($comic->avg_rating, 1) }}</span></div>
  <div class="sm-info"><h3 class="sm-title">{{ $comic->title }}</h3><div class="sm-meta"><span>{{ $chapter?->label ?? 'Mới cập nhật' }}</span><span>{{ $chapter?->time_ago ?? '' }}</span></div></div>
</a>
