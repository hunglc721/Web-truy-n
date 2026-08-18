{{--
  ============================================================
  FILE: resources/views/comics/show.blade.php
  Route: /truyen/{slug}
  Variables từ ComicController::show():
    $comic         — Comic (with genres, authors, tags, chapters x20, ratings)
    $relatedComics — Collection<Comic>
  ============================================================
--}}
@extends('layouts.main')

@section('title', $comic->title.' - Read Free Online | WebComics')

@section('meta')
  <meta name="description" content="{{ Str::limit($comic->description, 160) }}" />
@endsection

@section('content')
<main class="page-container">
  <div class="container">

    {{-- Breadcrumb --}}
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Home</a> &rsaquo;
        <a href="{{ route('genres') }}">Comics</a> &rsaquo;
        <span>{{ $comic->title }}</span>
      </div>
    </div>

    {{-- COMIC DETAIL HERO --}}
    <div class="orig-spotlight-card" style="margin-bottom:48px;">

      <div class="spotlight-cover">
        <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" />
        @if($comic->is_original)
          <span class="spotlight-badge">ORIGINAL</span>
        @endif
      </div>

      <div class="spotlight-details">
        {{-- Tags --}}
        <div class="spotlight-tags">
          @foreach($comic->genres as $genre)
            <a href="{{ route('genres', ['genre' => $genre->slug]) }}" class="genre-tag">
              {{ $genre->name }}
            </a>
          @endforeach
          @foreach($comic->tags as $tag)
            <span class="orig-tag">{{ $tag->name }}</span>
          @endforeach
        </div>

        <h1 class="spotlight-title">{{ $comic->title }}</h1>

        {{-- Stats bar --}}
        <p class="spotlight-author">
          By {{ $comic->authors->pluck('name')->join(' · ') ?: 'Unknown Author' }}
          &middot; ★ {{ number_format($comic->avg_rating, 1) }} ({{ $comic->total_ratings }} ratings)
          &middot; {{ $comic->formatted_views }} Views
          &middot; {{ ucfirst($comic->status) }}
        </p>

        {{-- Likes counter --}}
        @php
          $likeCount  = $comic->likes()->count();
          $isLiked    = auth()->check() ? $comic->hasLikedBy(auth()->id()) : false;
          $isSaved    = auth()->check() ? auth()->user()->hasInLibrary($comic->id) : false;
        @endphp

        <div style="display:inline-flex; align-items:center; gap:6px; margin-bottom:8px; font-size:13.5px; color:var(--text-sub);">
          <span id="like-count" style="font-weight:700; font-size:18px; color:{{ $isLiked ? '#ef4444' : 'var(--text-main)' }};">
            {{ number_format($likeCount) }}
          </span>
          lượt thích
        </div>

        <p class="spotlight-desc">{{ $comic->description }}</p>

        {{-- ACTION BUTTONS --}}
        <div class="spotlight-actions" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-top:16px;">

          {{-- Đọc từ đầu --}}
          @if($comic->chapters->isNotEmpty())
            @php $firstChapter = $comic->chapters->last(); @endphp
            <a href="{{ route('chapters.show', [$comic->slug, $firstChapter->slug]) }}" class="btn-spotlight-read">
              🚀 Đọc Từ Đầu (Ch.{{ $firstChapter->chapter_number }})
            </a>
          @endif

          @auth
            {{-- ── NÚT TỦ SÁCH ──────────────────────────────── --}}
            <button type="button"
              id="btn-toggle-library"
              data-comic="{{ $comic->id }}"
              data-saved="{{ $isSaved ? '1' : '0' }}"
              style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; border:2px solid; transition:all .2s ease;
                     {{ $isSaved
                        ? 'background:#16a34a; border-color:#16a34a; color:#fff;'
                        : 'background:transparent; border-color:var(--border,rgba(255,255,255,.2)); color:var(--text-main);' }}"
              onmouseover="if(this.dataset.saved==='0'){this.style.borderColor='#16a34a';this.style.color='#16a34a';}"
              onmouseout="if(this.dataset.saved==='0'){this.style.borderColor='var(--border,rgba(255,255,255,.2))';this.style.color='var(--text-main)';}">
              <svg id="lib-icon" width="17" height="17" viewBox="0 0 24 24" fill="{{ $isSaved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
              </svg>
              <span id="lib-label">{{ $isSaved ? '✓ Đã Theo Dõi' : '+ Thêm Vào Tủ Sách' }}</span>
            </button>

            {{-- ── NÚT THÍCH ────────────────────────────────── --}}
            <button type="button"
              id="btn-toggle-like"
              data-comic="{{ $comic->id }}"
              data-liked="{{ $isLiked ? '1' : '0' }}"
              style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; border:2px solid; transition:all .2s ease;
                     {{ $isLiked
                        ? 'background:#ef4444; border-color:#ef4444; color:#fff;'
                        : 'background:transparent; border-color:var(--border,rgba(255,255,255,.2)); color:var(--text-main);' }}"
              onmouseover="if(this.dataset.liked==='0'){this.style.borderColor='#ef4444';this.style.color='#ef4444';}"
              onmouseout="if(this.dataset.liked==='0'){this.style.borderColor='var(--border,rgba(255,255,255,.2))';this.style.color='var(--text-main)';}">
              <svg id="like-icon" width="17" height="17" viewBox="0 0 24 24" fill="{{ $isLiked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
              </svg>
              <span id="like-label">{{ $isLiked ? '❤️ Đã Thích' : '🤍 Yêu Thích' }}</span>
            </button>

          @else
            {{-- Chưa đăng nhập → dẫn tới login --}}
            <a href="{{ route('login') }}" class="btn-spotlight-sub" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
              📚 Theo Dõi Truyện
            </a>
            <a href="{{ route('login') }}" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:10px; font-size:14px; font-weight:700; border:2px solid rgba(255,255,255,.2); color:var(--text-main); text-decoration:none; transition:.2s;">
              🤍 Yêu Thích
            </a>
          @endauth
        </div>

        {{-- Toast notification --}}
        <div id="action-toast" style="display:none; margin-top:12px; padding:10px 16px; border-radius:8px; font-size:13px; font-weight:600; transition:all .3s;"></div>
      </div>
    </div>

    {{-- CHAPTERS LIST --}}
    <div id="chapters" class="comics-section">
      <div class="section-header">
        <h2 class="section-title">📖 Danh Sách Chapter ({{ $comic->chapters_count }})</h2>
      </div>

      <div style="display:flex; flex-direction:column; gap:8px;">
        @forelse($comic->chapters as $chapter)
          <a href="{{ route('chapters.show', [$comic->slug, $chapter->slug]) }}"
             class="browse-card"
             style="padding:16px 20px; text-decoration:none; align-items:center;">
            <div class="browse-info" style="padding:0;">
              <h3 class="browse-title" style="font-size:15px;">
                {{ $chapter->label }}
                @if($chapter->title) — {{ $chapter->title }} @endif
              </h3>
              <p class="browse-meta" style="margin:4px 0 0;">
                {{ $chapter->time_ago }}
                @if(!$chapter->is_free)
                  &middot; <span style="color:var(--accent-gold);">🔒 Premium</span>
                @endif
              </p>
            </div>
          </a>
        @empty
          <p style="color:var(--text-sub); padding:20px 0;">Chưa có chương nào được đăng.</p>
        @endforelse
      </div>
    </div>

    {{-- RELATED COMICS --}}
    @if($relatedComics->isNotEmpty())
      <div class="comics-section" style="padding-top:48px;">
        <div class="section-header">
          <h2 class="section-title">🔗 You May Also Like</h2>
        </div>
        <div class="comics-grid">
          @foreach($relatedComics as $related)
            <a href="{{ route('comics.show', $related->slug) }}" class="comic-card-sm">
              <div class="sm-cover">
                <img src="{{ $related->cover_image }}" alt="{{ $related->title }}" class="cover-img" loading="lazy" />
                <span class="sm-badge">★ {{ number_format($related->avg_rating, 1) }}</span>
              </div>
              <p class="sm-title">{{ $related->title }}</p>
              <p class="sm-meta">{{ $related->genres->first()?->name }}</p>
            </a>
          @endforeach
        </div>
      </div>
    @endif

  </div>
</main>
@endsection

@push('scripts')
<script>
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

  // ─── Helper: Show Toast ─────────────────────────────────────────
  function showToast(message, type = 'success') {
    const toast = document.getElementById('action-toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.display = 'block';
    toast.style.background = type === 'success' ? 'rgba(22,163,74,.2)' : 'rgba(239,68,68,.2)';
    toast.style.color       = type === 'success' ? '#4ade80' : '#f87171';
    toast.style.border      = `1px solid ${type === 'success' ? 'rgba(22,163,74,.4)' : 'rgba(239,68,68,.4)'}`;
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 3500);
  }

  // ─── Helper: AJAX POST ──────────────────────────────────────────
  async function postAction(url) {
    const res = await fetch(url, {
      method:  'POST',
      headers: {
        'X-CSRF-TOKEN':     CSRF_TOKEN,
        'Accept':           'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
  }

  // ─── NÚT TỦ SÁCH (Toggle Library) ───────────────────────────────
  document.getElementById('btn-toggle-library')?.addEventListener('click', async function () {
    const btn      = this;
    const comicId  = btn.dataset.comic;
    const isSaved  = btn.dataset.saved === '1';
    btn.disabled   = true;

    try {
      // Dùng route mới ưu tiên, fallback về route cũ
      const url  = `/api/comics/${comicId}/toggle-library`;
      const data = await postAction(url);

      if (data.status === 'success') {
        const nowSaved = data.in_library ?? data.is_followed;
        btn.dataset.saved = nowSaved ? '1' : '0';

        // Label
        document.getElementById('lib-label').textContent = nowSaved ? '✓ Đã Theo Dõi' : '+ Thêm Vào Tủ Sách';

        // Icon fill
        document.getElementById('lib-icon').setAttribute('fill', nowSaved ? 'currentColor' : 'none');

        // Button colors
        if (nowSaved) {
          btn.style.background   = '#16a34a';
          btn.style.borderColor  = '#16a34a';
          btn.style.color        = '#fff';
        } else {
          btn.style.background  = 'transparent';
          btn.style.borderColor = 'var(--border,rgba(255,255,255,.2))';
          btn.style.color       = 'var(--text-main)';
        }

        showToast(data.message ?? (nowSaved ? '📚 Đã thêm vào Tủ Sách!' : '📚 Đã bỏ theo dõi!'));
      }
    } catch (err) {
      console.error('Lỗi khi theo dõi truyện:', err);
      showToast('❌ Có lỗi xảy ra, vui lòng thử lại!', 'error');
    } finally {
      btn.disabled = false;
    }
  });

  // ─── NÚT THÍCH (Toggle Like) ────────────────────────────────────
  document.getElementById('btn-toggle-like')?.addEventListener('click', async function () {
    const btn     = this;
    const comicId = btn.dataset.comic;
    const isLiked = btn.dataset.liked === '1';
    btn.disabled  = true;

    // Optimistic UI update (đổi ngay trước khi nhận kết quả server)
    const optimisticLiked = !isLiked;
    const likeCountEl     = document.getElementById('like-count');
    const currentCount    = parseInt(likeCountEl.textContent.replace(/,/g, ''), 10);
    likeCountEl.textContent = (currentCount + (optimisticLiked ? 1 : -1)).toLocaleString();

    // Heart icon animate
    const likeIcon = document.getElementById('like-icon');
    likeIcon.style.transform = 'scale(1.4)';
    setTimeout(() => { likeIcon.style.transform = 'scale(1)'; }, 200);

    try {
      const data = await postAction(`/api/comics/${comicId}/toggle-like`);

      if (data.status === 'success') {
        // Dùng giá trị chính xác từ server
        btn.dataset.liked = data.is_liked ? '1' : '0';
        likeCountEl.textContent = Number(data.like_count).toLocaleString();
        likeCountEl.style.color = data.is_liked ? '#ef4444' : 'var(--text-main)';

        document.getElementById('like-label').textContent = data.is_liked ? '❤️ Đã Thích' : '🤍 Yêu Thích';
        likeIcon.setAttribute('fill', data.is_liked ? 'currentColor' : 'none');

        if (data.is_liked) {
          btn.style.background  = '#ef4444';
          btn.style.borderColor = '#ef4444';
          btn.style.color       = '#fff';
        } else {
          btn.style.background  = 'transparent';
          btn.style.borderColor = 'var(--border,rgba(255,255,255,.2))';
          btn.style.color       = 'var(--text-main)';
        }

        showToast(data.message ?? (data.is_liked ? '❤️ Đã thích!' : '🤍 Đã bỏ thích!'));
      }
    } catch (err) {
      console.error('Lỗi khi like truyện:', err);
      // Rollback optimistic UI
      likeCountEl.textContent = currentCount.toLocaleString();
      showToast('❌ Có lỗi xảy ra, vui lòng thử lại!', 'error');
    } finally {
      btn.disabled = false;
    }
  });
</script>
@endpush
