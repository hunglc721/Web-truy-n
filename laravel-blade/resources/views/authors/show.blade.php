@extends('layouts.main')

@section('title', 'Tác giả: ' . $author->name . ' - WebComics')

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Tác giả</span> &rsaquo; <span>{{ $author->name }}</span>
      </div>
    </div>

    <!-- Author Spotlight Header Card -->
    <div style="
      background: rgba(19, 22, 30, 0.85);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 28px;
      margin-bottom: 35px;
      display: flex;
      gap: 24px;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    ">
      <div style="
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff5e36, #ff2a6d);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        font-weight: 900;
        color: #fff;
        box-shadow: 0 8px 24px rgba(255, 94, 54, 0.35);
        flex-shrink: 0;
      ">
        @if($author->avatar)
          <img src="{{ $author->avatar }}" alt="{{ $author->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
        @else
          {{ mb_strtoupper(mb_substr($author->name, 0, 1)) }}
        @endif
      </div>

      <div style="flex: 1; min-width: 250px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 8px;">
          <h1 style="font-size: 24px; font-weight: 900; color: #fff; margin: 0;">{{ $author->name }}</h1>
          
          @auth
            <button type="button" id="btn-follow-author" data-author-id="{{ $author->id }}" class="btn-spotlight-sub" style="
              cursor: pointer;
              padding: 8px 18px;
              border-radius: 20px;
              font-weight: 700;
              transition: all 0.2s;
              {{ $isFollowed ? 'background: #16a34a; color: #fff; border-color: #16a34a;' : 'background: rgba(255,255,255,0.08);' }}
            ">
              <span id="follow-text">{{ $isFollowed ? '✓ Đang Theo Dõi' : '🔔 Theo Dõi Tác Giả' }}</span>
            </button>
          @else
            <a href="{{ route('login') }}" class="btn-spotlight-sub" style="text-decoration: none; padding: 8px 18px; border-radius: 20px; font-weight: 700;">
              🔔 Theo Dõi Tác Giả
            </a>
          @endauth
        </div>

        <p style="color: var(--text-sub); font-size: 13.5px; line-height: 1.6; margin: 0 0 12px 0;">
          {{ $author->bio ?: 'Thông tin tiểu sử của tác giả đang được cập nhật.' }}
        </p>

        <div style="display: flex; gap: 16px; font-size: 13px; color: var(--text-sub);">
          <span>📚 <strong>{{ $author->comics->count() }}</strong> tác phẩm</span>
          <span>👥 <strong id="followers-count">{{ number_format($author->followers_count) }}</strong> người theo dõi</span>
        </div>
      </div>
    </div>

    <!-- Danh sách tác phẩm của Tác giả -->
    <div class="section-header" style="margin-bottom: 20px;">
      <h2 class="section-title">✨ Tác Phẩm Của {{ $author->name }}</h2>
    </div>

    @if($author->comics->isNotEmpty())
      <div class="comics-grid">
        @foreach($author->comics as $comic)
          <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm">
            <div class="sm-cover">
              <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
              <span class="sm-badge">★ {{ number_format($comic->avg_rating, 1) }}</span>
            </div>
            <p class="sm-title">{{ $comic->title }}</p>
          </a>
        @endforeach
      </div>
    @else
      <div style="padding: 40px; text-align: center; border: 1px dashed var(--border); border-radius: 12px; color: var(--text-sub);">
        Tác giả này chưa có tác phẩm nào trên hệ thống.
      </div>
    @endif

  </div>
</main>

@push('scripts')
<script>
  const followBtn = document.getElementById('btn-follow-author');
  if (followBtn) {
    followBtn.addEventListener('click', function() {
      const authorId = this.getAttribute('data-author-id');
      const textEl = document.getElementById('follow-text');
      const countEl = document.getElementById('followers-count');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      followBtn.disabled = true;

      fetch(`/api/authors/${authorId}/follow`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      })
      .then(res => res.json())
      .then(data => {
        followBtn.disabled = false;
        if (data.status === 'success') {
          if (data.is_followed) {
            followBtn.style.background = '#16a34a';
            followBtn.style.borderColor = '#16a34a';
            followBtn.style.color = '#fff';
            if (textEl) textEl.textContent = '✓ Đang Theo Dõi';
          } else {
            followBtn.style.background = 'rgba(255,255,255,0.08)';
            followBtn.style.borderColor = 'var(--border)';
            followBtn.style.color = 'var(--text-sub)';
            if (textEl) textEl.textContent = '🔔 Theo Dõi Tác Giả';
          }
          if (countEl) countEl.textContent = Number(data.followers_count).toLocaleString();
        }
      })
      .catch(err => {
        followBtn.disabled = false;
        console.debug("Follow author error:", err);
      });
    });
  }
</script>
@endpush
@endsection
