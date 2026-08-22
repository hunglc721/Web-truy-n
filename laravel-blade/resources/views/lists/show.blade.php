@extends('layouts.main')

@section('title', $list->title . ' - Tuyển Tập Truyện - WebComics')

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <a href="{{ route('lists.index') }}">Tuyển Tập</a> &rsaquo; <span>{{ $list->title }}</span>
      </div>
    </div>

    <!-- Reading List Header Banner -->
    <div style="
      background: rgba(19, 22, 30, 0.9);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 28px;
      margin-bottom: 35px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    ">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div style="flex: 1; min-width: 260px;">
          <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 11px; background: rgba(255,94,54,0.2); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-weight: 800;">
              📚 TUYỂN TẬP CỦA {{ mb_strtoupper($list->user->name ?? 'THÀNH VIÊN') }}
            </span>
            <span style="font-size: 12px; color: var(--text-muted);">
              Cập nhật {{ $list->updated_at->diffForHumans() }}
            </span>
          </div>

          <h1 style="font-size: 24px; font-weight: 900; color: #fff; margin: 0 0 10px 0;">
            {{ $list->title }}
          </h1>

          <p style="color: var(--text-sub); font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">
            {{ $list->description ?: 'Danh sách các bộ truyện tranh xuất sắc được tuyển chọn.' }}
          </p>

          <div style="display: flex; gap: 18px; font-size: 13px; color: var(--text-sub);">
            <span>📖 <strong>{{ $list->comics->count() }}</strong> bộ truyện</span>
            <span>👁 <strong>{{ number_format($list->views_count) }}</strong> lượt xem</span>
            <span>❤️ <strong id="list-like-count">{{ number_format($list->likes_count) }}</strong> lượt thích</span>
          </div>
        </div>

        <div style="display: flex; gap: 10px;">
          @auth
            <button type="button" id="btn-like-list" data-list-id="{{ $list->id }}" class="btn-spotlight-sub" style="
              cursor: pointer;
              padding: 10px 20px;
              border-radius: 20px;
              font-weight: 700;
              transition: all 0.2s;
              {{ $isLiked ? 'background: #ef4444; color: #fff; border-color: #ef4444;' : 'background: rgba(255,255,255,0.08);' }}
            ">
              <span id="like-text">{{ $isLiked ? '❤️ Đã Thích' : '🤍 Yêu Thích' }}</span>
            </button>
          @else
            <a href="{{ route('login') }}" class="btn-spotlight-sub" style="text-decoration: none; padding: 10px 20px; border-radius: 20px; font-weight: 700;">
              🤍 Yêu Thích
            </a>
          @endauth

          <button type="button" onclick="navigator.clipboard.writeText(window.location.href); alert('Đã sao chép link danh sách vào bộ nhớ tạm!')" class="btn-spotlight-sub" style="cursor: pointer; padding: 10px 16px; border-radius: 20px;">
            🔗 Chia sẻ
          </button>
        </div>
      </div>
    </div>

    <!-- Comics in this Reading List -->
    <div class="section-header" style="margin-bottom: 20px;">
      <h2 class="section-title">✨ Danh Sách Truyện ({{ $list->comics->count() }})</h2>
    </div>

    @if($list->comics->isNotEmpty())
      <div class="comics-grid">
        @foreach($list->comics as $comic)
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
        Tuyển tập này chưa có bộ truyện nào.
      </div>
    @endif

  </div>
</main>

@push('scripts')
<script>
  const likeListBtn = document.getElementById('btn-like-list');
  if (likeListBtn) {
    likeListBtn.addEventListener('click', function() {
      const listId = this.getAttribute('data-list-id');
      const textEl = document.getElementById('like-text');
      const countEl = document.getElementById('list-like-count');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      likeListBtn.disabled = true;

      fetch(`/api/lists/${listId}/toggle-like`, {
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
        likeListBtn.disabled = false;
        if (data.status === 'success') {
          if (data.is_liked) {
            likeListBtn.style.background = '#ef4444';
            likeListBtn.style.borderColor = '#ef4444';
            likeListBtn.style.color = '#fff';
            if (textEl) textEl.textContent = '❤️ Đã Thích';
          } else {
            likeListBtn.style.background = 'rgba(255,255,255,0.08)';
            likeListBtn.style.borderColor = 'var(--border)';
            likeListBtn.style.color = 'var(--text-sub)';
            if (textEl) textEl.textContent = '🤍 Yêu Thích';
          }
          if (countEl) countEl.textContent = Number(data.likes_count).toLocaleString();
        }
      })
      .catch(err => {
        likeListBtn.disabled = false;
        console.debug("Like list error:", err);
      });
    });
  }
</script>
@endpush
@endsection
