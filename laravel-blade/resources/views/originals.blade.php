{{--
  ============================================================
  FILE: resources/views/originals.blade.php  (DYNAMIC VERSION)
  Variables từ OriginalsController::index():
    $spotlight — ?Comic (is_featured=true hoặc avg_rating cao nhất)
    $originals — Collection<Comic>
    $genres    — Collection<Genre> (chỉ genres có trong originals)
  ============================================================
--}}
@extends('layouts.main')

@section('title', 'WebComics Originals - Exclusive Free Manga & Webtoons')

@section('meta')
  <meta name="description" content="Discover official WebComics Originals! Exclusive high-quality Webtoons, Manhua & Manga across Romance, Fantasy, BL, Drama, and Action genres." />
@endsection

@section('content')

{{-- ORIGINALS HERO BANNER --}}
<section class="orig-hero-section">
  <div class="orig-hero-glow"></div>
  <div class="container">
    <div class="orig-hero-inner">
      <div class="orig-hero-badge">✨ EXCLUSIVE WEBCOMICS ORIGINALS</div>
      <h1 class="orig-hero-title">
        Discover Official <span class="purple-gradient-text">Original Series</span>
      </h1>
      <p class="orig-hero-sub">
        Read hundreds of exclusive high-definition webtoons and manga created by top international artists.
        Updated daily only on WebComics!
      </p>
    </div>
  </div>
</section>

{{-- STICKY ORIGINALS TAB BAR — dynamic genres --}}
<div class="genre-section">
  <div class="container">
    <div class="genre-tabs" id="orig-tabs">
      <a href="{{ route('originals') }}"
         class="genre-tab active">Editor's Pick</a>

      {{-- ✅ @foreach genres có trong originals --}}
      @foreach($genres as $genre)
        <a href="{{ route('genres', ['genre' => $genre->slug]) }}"
           class="genre-tab">
          {{ $genre->name }}
        </a>
      @endforeach
    </div>
  </div>
</div>

{{-- MAIN ORIGINALS CONTENT --}}
<main class="page-container" style="padding-top:32px;">
  <div class="container">

    {{-- SPOTLIGHT FEATURED BANNER --}}
    @if($spotlight)
      @php
        $spotlightGenres  = $spotlight->genres->pluck('name');
        $spotlightAuthors = $spotlight->authors;
        $spotlightChapter = $spotlight->latestChapter;
      @endphp

      <div class="orig-spotlight-card">
        <div class="spotlight-cover">
          <img src="{{ $spotlight->cover_image }}"
               alt="{{ $spotlight->title }}"
               class="cover-img" />
          <span class="spotlight-badge">
            {{ $spotlight->is_featured ? '#1 EDITOR\'S CHOICE' : 'TOP ORIGINAL' }}
          </span>
        </div>

        <div class="spotlight-details">
          <div class="spotlight-tags">
            <span class="orig-tag">ORIGINAL</span>
            {{-- ✅ @foreach genres của spotlight --}}
            @foreach($spotlightGenres->take(2) as $genre)
              <span class="genre-tag">{{ $genre }}</span>
            @endforeach
          </div>

          <h2 class="spotlight-title">{{ $spotlight->title }}</h2>

          <p class="spotlight-author">
            By {{ $spotlightAuthors->pluck('name')->join(' · ') }}
            &middot; ★ {{ number_format($spotlight->avg_rating, 1) }} Rating
            &middot; {{ $spotlight->formatted_views }} Reads
          </p>

          <p class="spotlight-desc">{{ $spotlight->description }}</p>

          <div class="spotlight-actions">
            <a href="{{ route('comics.show', $spotlight->slug) }}"
               class="btn-spotlight-read">
              🚀 Start Reading
              @if($spotlightChapter) {{ $spotlightChapter->label }} @endif
            </a>
            <button class="btn-spotlight-sub">+ Add to Library</button>
          </div>
        </div>
      </div>
    @endif

    {{-- EDITOR'S PICK GRID --}}
    <div class="comics-section" style="padding-top:36px;">
      <div class="section-header">
        <h2 class="section-title">✨ Top Editor's Picks</h2>
        <span class="results-count">
          {{ $originals->count() }} exclusive series
        </span>
      </div>

      <div class="originals-full-grid">

        {{-- ✅ @forelse thay thế 9 thẻ div.orig-full-card hardcode --}}
        @forelse($originals as $comic)
          @php
            $chapter = $comic->latestChapter;
          @endphp

          <div class="orig-full-card">
            <div class="of-cover">
              <a href="{{ route('comics.show', $comic->slug) }}">
                <img src="{{ $comic->cover_image }}"
                     alt="{{ $comic->title }}"
                     class="cover-img"
                     loading="lazy" />
              </a>
              <span class="of-badge">ORIGINAL</span>
              <span class="of-rating">★ {{ number_format($comic->avg_rating, 1) }}</span>
            </div>

            <div class="of-body">
              <h3 class="of-title">
                <a href="{{ route('comics.show', $comic->slug) }}">{{ $comic->title }}</a>
              </h3>
              {{-- "Fantasy · Mystery" --}}
              <p class="of-genre">
                {{ $comic->genres->pluck('name')->join(' · ') }}
              </p>
              {{-- "👥 8.6M Readers · 590 Chapters" --}}
              <p class="of-stats">
                👥 {{ $comic->formatted_views }} Readers
                &middot;
                @if($chapter) {{ $chapter->label }} @endif
              </p>
            </div>
          </div>
        @empty
          <div style="grid-column:1/-1; text-align:center; padding:60px; color:var(--text-sub);">
            <p>No originals found.</p>
          </div>
        @endforelse

      </div>
    </div>

  </div>
</main>
@endsection
