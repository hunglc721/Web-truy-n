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

          {{-- Nút Đọc Tiếp / Đọc Từ Đầu thông minh --}}
          @php
            $lastHistory  = auth()->check() ? auth()->user()->readingHistoryForComic($comic->id) : null;
            $lastChapter  = $lastHistory?->chapter;
            $firstChapter = $comic->chapters->last();
          @endphp

          @if($lastChapter)
            {{-- Đã có lịch sử đọc → Hiện nút "Đọc Tiếp" với tiến độ % --}}
            <a href="{{ route('chapters.show', [$comic->slug, $lastChapter->slug]) }}" class="btn-spotlight-read" style="background: linear-gradient(135deg, #FF5E36, #FF2A6D); box-shadow: 0 4px 20px rgba(255, 94, 54, 0.4);">
              📖 Đọc Tiếp (Ch.{{ $lastChapter->chapter_number }}{{ $lastHistory->scroll_percent > 0 ? ' - ' . round($lastHistory->scroll_percent) . '%' : '' }})
            </a>
            @if($firstChapter && $firstChapter->id !== $lastChapter->id)
              <a href="{{ route('chapters.show', [$comic->slug, $firstChapter->slug]) }}" class="btn-spotlight-sub" style="text-decoration:none; padding:10px 18px; border-radius:10px; font-weight:700;">
                Đọc Từ Đầu (Ch.{{ $firstChapter->chapter_number }})
              </a>
            @endif
          @elseif($firstChapter)
            {{-- Chưa có lịch sử → Hiện nút "Đọc Từ Đầu" --}}
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

    {{-- RATINGS & REVIEWS SECTION --}}
    <div id="ratings-section" class="rating-section-card">
      <div class="section-header" style="margin-bottom: 20px;">
        <h2 class="section-title">⭐ Đánh Giá & Nhận Xét</h2>
      </div>

      {{-- Overview & Histogram --}}
      <div class="rating-grid">
        <div class="rating-score-box">
          <div class="rating-score-num" id="rating-avg-display">{{ number_format($comic->avg_rating, 1) }}</div>
          <div class="rating-stars-display" id="rating-stars-icons">
            @php
              $fullStars = floor($comic->avg_rating);
              $halfStar = ($comic->avg_rating - $fullStars) >= 0.5;
            @endphp
            @for($i = 1; $i <= 5; $i++)
              @if($i <= $fullStars)
                ★
              @elseif($i == $fullStars + 1 && $halfStar)
                ★
              @else
                ☆
              @endif
            @endfor
          </div>
          <div class="rating-score-total">Dựa trên <strong id="rating-total-display">{{ $comic->total_ratings }}</strong> lượt đánh giá</div>
        </div>

        {{-- Breakdown Histogram (1 -> 5 stars) --}}
        <div class="rating-histogram" id="rating-histogram-bars">
          @for($s = 5; $s >= 1; $s--)
            <div class="rating-bar-row">
              <span class="rating-bar-label">{{ $s }} ★</span>
              <div class="rating-bar-track">
                <div class="rating-bar-fill" id="bar-fill-{{ $s }}" style="width: 0%;"></div>
              </div>
              <span class="rating-bar-percent" id="bar-percent-{{ $s }}">0%</span>
            </div>
          @endfor
        </div>
      </div>

      {{-- User Interactive Rating Box --}}
      @auth
        <div class="user-rating-box">
          <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
            ✍️ Đánh giá của bạn
          </h3>
          <p style="font-size: 13px; color: var(--text-sub); margin-bottom: 10px;">
            Chọn số sao và để lại cảm nhận của bạn về bộ truyện này
          </p>

          <div class="star-rating-select" id="star-selector">
            <button type="button" class="star-btn" data-value="1" title="1 sao">★</button>
            <button type="button" class="star-btn" data-value="2" title="2 sao">★</button>
            <button type="button" class="star-btn" data-value="3" title="3 sao">★</button>
            <button type="button" class="star-btn" data-value="4" title="4 sao">★</button>
            <button type="button" class="star-btn" data-value="5" title="5 sao">★</button>
          </div>
          <input type="hidden" id="selected-score" value="0" />

          <textarea id="rating-review-input" class="rating-review-textarea" placeholder="Viết cảm nhận của bạn về truyện (tùy chọn)..." maxlength="1000"></textarea>

          <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" id="btn-submit-rating" class="btn-spotlight-read" style="padding: 8px 18px; font-size: 13.5px;">
              Gửi Đánh Giá
            </button>
            <button type="button" id="btn-delete-rating" class="btn-spotlight-sub" style="display: none; padding: 8px 16px; font-size: 13px; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">
              Xóa Đánh Giá
            </button>
          </div>
        </div>
      @else
        <div class="user-rating-box" style="text-align: center; padding: 24px;">
          <p style="color: var(--text-sub); font-size: 14px; margin-bottom: 12px;">
            Vui lòng đăng nhập để gửi đánh giá và nhận xét cho bộ truyện này.
          </p>
          <a href="{{ route('login') }}" class="btn-spotlight-read" style="display: inline-block; padding: 8px 20px; font-size: 13.5px;">
            Đăng Nhập Ngay
          </a>
        </div>
      @endauth

      {{-- Reviews List --}}
      <div id="reviews-list-container" style="margin-top: 28px;">
        <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 16px;">
          💬 Nhận xét gần đây
        </h3>
        <div id="reviews-items">
          <p style="color: var(--text-muted); font-size: 13.5px;">Đang tải nhận xét...</p>
        </div>
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

  // ─── RATING & REVIEWS COMPONENT LOGIC ─────────────────────────────
  const COMIC_ID = {{ $comic->id }};
  const isUserLoggedIn = {{ auth()->check() ? 'true' : 'false' }};

  // Helper render stars string
  function renderStars(score) {
    const full = Math.floor(score);
    const half = (score - full) >= 0.5;
    let stars = '';
    for (let i = 1; i <= 5; i++) {
      if (i <= full) stars += '★';
      else if (i === full + 1 && half) stars += '★';
      else stars += '☆';
    }
    return stars;
  }

  // Load Breakdown Histogram
  async function loadRatingSummary() {
    try {
      const res = await fetch(`/api/comics/${COMIC_ID}/ratings/summary`);
      if (!res.ok) return;
      const json = await res.json();
      if (json.status === 'success' && json.data) {
        const data = json.data;
        document.getElementById('rating-avg-display').textContent = Number(data.avg_rating).toFixed(1);
        document.getElementById('rating-stars-icons').textContent = renderStars(data.avg_rating);
        document.getElementById('rating-total-display').textContent = data.total_ratings;

        for (let s = 1; s <= 5; s++) {
          const starInfo = data.stars[s] || { count: 0, percentage: 0 };
          const fillEl = document.getElementById(`bar-fill-${s}`);
          const pctEl  = document.getElementById(`bar-percent-${s}`);
          if (fillEl) fillEl.style.width = `${starInfo.percentage}%`;
          if (pctEl)  pctEl.textContent  = `${starInfo.percentage}%`;
        }
      }
    } catch (err) {
      console.error('Lỗi load summary rating:', err);
    }
  }

  // Load Recent Reviews
  async function loadReviews() {
    const container = document.getElementById('reviews-items');
    if (!container) return;
    try {
      const res = await fetch(`/api/comics/${COMIC_ID}/ratings/reviews?per_page=5`);
      if (!res.ok) return;
      const json = await res.json();
      if (json.status === 'success' && json.data && json.data.data) {
        const list = json.data.data;
        if (list.length === 0) {
          container.innerHTML = '<p style="color: var(--text-muted); font-size: 13.5px;">Chưa có nhận xét nào. Hãy là người đầu tiên để lại cảm nhận!</p>';
          return;
        }
        container.innerHTML = list.map(item => `
          <div class="review-item">
            <div class="review-item-header">
              <span class="review-user-name">${item.user ? item.user.name : 'Độc giả'}</span>
              <span class="review-badge">★ ${Number(item.score).toFixed(1)}</span>
            </div>
            <p class="review-text">${item.review ? item.review.replace(/</g, "&lt;").replace(/>/g, "&gt;") : ''}</p>
          </div>
        `).join('');
      }
    } catch (err) {
      console.error('Lỗi load reviews:', err);
    }
  }

  // User Star Selector Logic
  const starBtns = document.querySelectorAll('.star-btn');
  const selectedScoreInput = document.getElementById('selected-score');
  const reviewInput = document.getElementById('rating-review-input');
  const btnDeleteRating = document.getElementById('btn-delete-rating');

  function highlightStars(count) {
    starBtns.forEach(btn => {
      const val = parseInt(btn.dataset.value, 10);
      if (val <= count) {
        btn.classList.add('hovered');
      } else {
        btn.classList.remove('hovered');
      }
    });
  }

  function setSelectedStars(count) {
    if (selectedScoreInput) selectedScoreInput.value = count;
    starBtns.forEach(btn => {
      const val = parseInt(btn.dataset.value, 10);
      if (val <= count) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }

  starBtns.forEach(btn => {
    btn.addEventListener('mouseenter', () => highlightStars(parseInt(btn.dataset.value, 10)));
    btn.addEventListener('mouseleave', () => {
      const current = parseInt(selectedScoreInput?.value || '0', 10);
      starBtns.forEach(b => b.classList.remove('hovered'));
      setSelectedStars(current);
    });
    btn.addEventListener('click', () => {
      const val = parseInt(btn.dataset.value, 10);
      setSelectedStars(val);
    });
  });

  // Load User's Previous Rating if logged in
  async function loadUserRating() {
    if (!isUserLoggedIn) return;
    try {
      const res = await fetch(`/api/comics/${COMIC_ID}/my-rating`);
      if (!res.ok) return;
      const json = await res.json();
      if (json.status === 'success' && json.has_rated) {
        setSelectedStars(Math.round(json.score));
        if (reviewInput && json.review) reviewInput.value = json.review;
        if (btnDeleteRating) btnDeleteRating.style.display = 'inline-block';
        const submitBtn = document.getElementById('btn-submit-rating');
        if (submitBtn) submitBtn.textContent = 'Cập Nhật Đánh Giá';
      }
    } catch (err) {
      console.error('Lỗi load user rating:', err);
    }
  }

  // Submit Rating
  document.getElementById('btn-submit-rating')?.addEventListener('click', async function () {
    const score = parseFloat(selectedScoreInput?.value || '0');
    if (!score || score < 1 || score > 5) {
      showToast('⚠️ Vui lòng chọn số sao từ 1 đến 5!', 'error');
      return;
    }

    const review = reviewInput ? reviewInput.value.trim() : '';
    const btn = this;
    btn.disabled = true;

    try {
      const res = await fetch(`/api/comics/${COMIC_ID}/ratings`, {
        method: 'POST',
        headers: {
          'Content-Type':     'application/json',
          'X-CSRF-TOKEN':     CSRF_TOKEN,
          'Accept':           'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ score, review }),
      });

      const data = await res.json();
      if (res.ok && data.status === 'success') {
        showToast(data.message || '⭐ Đã gửi đánh giá thành công!');
        if (btnDeleteRating) btnDeleteRating.style.display = 'inline-block';
        btn.textContent = 'Cập Nhật Đánh Giá';
        loadRatingSummary();
        loadReviews();
      } else {
        showToast(data.message || '❌ Không thể gửi đánh giá.', 'error');
      }
    } catch (err) {
      console.error('Lỗi gửi rating:', err);
      showToast('❌ Có lỗi xảy ra, vui lòng thử lại!', 'error');
    } finally {
      btn.disabled = false;
    }
  });

  // Delete Rating
  btnDeleteRating?.addEventListener('click', async function () {
    if (!confirm('Bạn có chắc chắn muốn xóa đánh giá này không?')) return;
    const btn = this;
    btn.disabled = true;

    try {
      const res = await fetch(`/api/comics/${COMIC_ID}/ratings`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN':     CSRF_TOKEN,
          'Accept':           'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const data = await res.json();
      if (res.ok && data.status === 'success') {
        showToast(data.message || 'Đã xóa đánh giá.');
        setSelectedStars(0);
        if (reviewInput) reviewInput.value = '';
        btn.style.display = 'none';
        const submitBtn = document.getElementById('btn-submit-rating');
        if (submitBtn) submitBtn.textContent = 'Gửi Đánh Giá';
        loadRatingSummary();
        loadReviews();
      } else {
        showToast(data.message || '❌ Không thể xóa đánh giá.', 'error');
      }
    } catch (err) {
      console.error('Lỗi xóa rating:', err);
      showToast('❌ Có lỗi xảy ra, vui lòng thử lại!', 'error');
    } finally {
      btn.disabled = false;
    }
  });

  // Initialize ratings on load
  loadRatingSummary();
  loadReviews();
  loadUserRating();
</script>
@endpush
