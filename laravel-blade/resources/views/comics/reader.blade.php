{{-- resources/views/comics/reader.blade.php --}}
@extends('layouts.main')

@section('title', $comic->title . ' - ' . ($chapter->title ?: 'Chapter ' . $chapter->chapter_number) . ' | WebComics Reader')

@push('styles')
<style>
  body {
    background: #0d0f14 !important;
  }

  .reader-page-wrapper {
    background: #0d0f14;
    color: #e0e0e0;
    min-height: 100vh;
  }

  .reader-toolbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(19, 22, 30, 0.95);
    backdrop-filter: blur(10px);
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 4px 20px rgba(0,0,0,0.6);
  }

  .reader-controls-btn {
    background: rgba(255,255,255,0.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.12);
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
  }

  .reader-controls-btn:hover {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
  }

  .reader-chapter-select {
    background: rgba(255,255,255,0.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.15);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13.5px;
    font-weight: 700;
    outline: none;
    cursor: pointer;
    max-width: 240px;
  }

  .comment-item-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 12px;
  }

  /* Hotkey hint popup */
  .hotkey-hint {
    font-size: 11.5px;
    color: var(--text-muted);
    background: rgba(255,255,255,0.06);
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid rgba(255,255,255,0.1);
  }
</style>
@endpush

@section('content')
<div class="reader-page-wrapper">

  <!-- ── 1. READER STICKY TOP TOOLBAR ── -->
  <div class="reader-toolbar" id="reader-top-bar">
    <div class="comic-info" style="display: flex; align-items: center; gap: 12px;">
      <a href="{{ route('comics.show', $comic->slug) }}" style="color: var(--primary); text-decoration: none; font-weight: 800; font-size: 15px;">
        ← {{ Str::limit($comic->title, 30) }}
      </a>
      <span style="color: rgba(255,255,255,0.2);">|</span>
      <strong style="color: #fff; font-size: 14.5px;">{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</strong>
    </div>

    <div class="reader-controls" style="display: flex; gap: 10px; align-items: center;">

      {{-- Prev Chapter --}}
      @if($prevChapter)
        <a href="{{ route('chapters.show', [$comic->slug, $prevChapter->slug]) }}" class="reader-controls-btn" id="btn-prev-chap" title="Chương trước (Phím ←)">
          ← Chap trước
        </a>
      @else
        <button class="reader-controls-btn" disabled style="opacity:0.4; cursor:not-allowed">← Chap trước</button>
      @endif

      {{-- Select Chapter Dropdown --}}
      <select onchange="if(this.value) location.href = this.value;" class="reader-chapter-select">
        @foreach($allChapters as $item)
          <option value="{{ route('chapters.show', [$comic->slug, $item->slug]) }}"
                  {{ $item->id == $chapter->id ? 'selected' : '' }}>
            Ch.{{ $item->chapter_number }} - {{ Str::limit($item->title, 25) }}
          </option>
        @endforeach
      </select>

      {{-- Next Chapter --}}
      @if($nextChapter)
        <a href="{{ route('chapters.show', [$comic->slug, $nextChapter->slug]) }}" class="reader-controls-btn" id="btn-next-chap" title="Chương sau (Phím →)">
          Chap sau →
        </a>
      @else
        <button class="reader-controls-btn" disabled style="opacity:0.4; cursor:not-allowed">Chap sau →</button>
      @endif

      {{-- Adjust Image Width Control --}}
      <div style="display:flex; gap:4px; margin-left:8px;">
        <button onclick="adjustWidth(-60)" class="reader-controls-btn" title="Thu nhỏ khung ảnh">A-</button>
        <button onclick="adjustWidth(60)" class="reader-controls-btn" title="Phóng to khung ảnh">A+</button>
      </div>

    </div>
  </div>

  {{-- Phím tắt nhắc nhở --}}
  <div style="text-align:center; padding:10px 0; background:rgba(0,0,0,0.4); border-bottom:1px solid rgba(255,255,255,0.05)">
    <span class="hotkey-hint">💡 Mẹo: Dùng phím mũi tên <strong>← Trái</strong> / <strong>Phải →</strong> trên bàn phím để chuyển nhanh Chapter!</span>
  </div>

  <!-- ── 2. CHAPTER IMAGE READER CONTAINER ── -->
  <div id="reader-container" style="
    max-width: 800px;
    margin: 0 auto;
    transition: max-width 0.25s ease;
    min-height: 600px;
    background: #000;
    box-shadow: 0 10px 40px rgba(0,0,0,0.8);
  ">
    @php
      $pages = is_array($chapter->pages) ? $chapter->pages : (json_decode($chapter->pages, true) ?? []);
    @endphp

    @if(!empty($pages))
      @foreach($pages as $index => $pagePath)
        @php
          $imgUrl = str_starts_with($pagePath, 'http') ? $pagePath : asset('storage/' . $pagePath);
        @endphp
        <div class="comic-page-wrapper" style="position:relative; min-height:300px; background:#050505">
          <img
            src="{{ $imgUrl }}"
            alt="{{ $comic->title }} - Chapter {{ $chapter->chapter_number }} - Trang {{ $index + 1 }}"
            loading="lazy"
            class="comic-page-img"
            style="width: 100%; display: block; margin: 0 auto;"
            onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1578632767115-351597cf2477?w=800&auto=format&fit=crop&q=80';"
          />
          <div style="position:absolute; bottom:6px; right:10px; background:rgba(0,0,0,0.6); color:rgba(255,255,255,0.6); font-size:10px; padding:2px 6px; border-radius:4px">
            {{ $index + 1 }} / {{ count($pages) }}
          </div>
        </div>
      @endforeach
    @else
      <div style="text-align: center; padding: 100px 20px; color: var(--text-muted);">
        <p style="font-size: 42px; margin-bottom: 12px;">📖</p>
        <p style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 6px;">Nội dung chương đang được cập nhật hình ảnh.</p>
        <p style="font-size: 14px;">Vui lòng quay lại sau ít phút hoặc thử chuyển sang chapter khác!</p>
      </div>
    @endif
  </div>

  <!-- ── 3. BOTTOM NAVIGATION BAR ── -->
  <div class="reader-footer" style="
    max-width: 800px;
    margin: 30px auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 15px;
    flex-wrap: wrap;
    gap: 12px;
  ">
    @if($prevChapter)
      <a href="{{ route('chapters.show', [$comic->slug, $prevChapter->slug]) }}" class="reader-controls-btn" style="padding:10px 20px; background:var(--primary); border-color:var(--primary)">
        ← Chapter Trước (Ch.{{ $prevChapter->chapter_number }})
      </a>
    @else
      <div></div>
    @endif

    <a href="{{ route('comics.show', $comic->slug) }}" class="reader-controls-btn">
      📋 Danh Sách Chương
    </a>

    @if($nextChapter)
      <a href="{{ route('chapters.show', [$comic->slug, $nextChapter->slug]) }}" class="reader-controls-btn" style="padding:10px 20px; background:var(--primary); border-color:var(--primary)">
        Chapter Sau (Ch.{{ $nextChapter->chapter_number }}) →
      </a>
    @else
      <a href="{{ route('comics.show', $comic->slug) }}" class="reader-controls-btn" style="background:#22c55e; border-color:#22c55e">
        ✅ Đã Đọc Hết Chapter Mới Nhất
      </a>
    @endif
  </div>

  <!-- ── 4. COMMENT SECTION VỚI AJAX ── -->
  <div class="comments-section" style="
    max-width: 800px;
    margin: 40px auto 80px auto;
    padding: 28px;
    background: rgba(19, 22, 30, 0.8);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.08);
  ">
    <h3 style="color: #fff; border-bottom: 2px solid var(--primary); padding-bottom: 10px; margin-bottom: 20px; font-size: 17px; font-weight: 800;">
      💬 Bình Luận Chương {{ $chapter->chapter_number }}
    </h3>

    @auth
      <form id="comment-form" style="margin-bottom: 25px;">
        @csrf
        <input type="hidden" name="comic_id" value="{{ $comic->id }}">
        <input type="hidden" name="chapter_id" value="{{ $chapter->id }}">
        <textarea
          name="content" id="comment-content" rows="3"
          placeholder="Viết bình luận của bạn về chương này..."
          required
          style="width: 100%; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.12); padding: 12px; border-radius: 8px; box-sizing: border-box; outline: none; font-family: inherit; font-size: 14px;"
        ></textarea>
        <button type="submit" class="btn btn-login" style="margin-top: 10px; padding: 10px 24px; font-weight: 700;">
          🚀 Gửi bình luận
        </button>
      </form>
    @else
      <div style="background: rgba(255,255,255,0.04); padding: 16px; border-radius: 8px; margin-bottom: 20px; text-align: center; color: var(--text-muted); font-size: 13.5px;">
        Vui lòng <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 700; text-decoration: underline;">Đăng nhập</a> để tham gia bình luận cùng cộng đồng.
      </div>
    @endauth

    <!-- Danh sách bình luận -->
    <div id="comments-list">
      @if(isset($comments) && count($comments) > 0)
        @foreach($comments as $cmt)
          <div class="comment-item-card">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
              <strong style="color: var(--primary); font-size: 13.5px;">{{ $cmt->user->name ?? 'Thành viên' }}</strong>
              <span style="font-size: 11.5px; color: var(--text-muted);">{{ $cmt->time_ago }}</span>
            </div>
            <p style="color: var(--text-main); margin: 0; font-size: 13.5px; line-height: 1.5;">{{ $cmt->content }}</p>
          </div>
        @endforeach
      @else
        <p id="no-comments-msg" style="color: var(--text-muted); font-style: italic; font-size: 13.5px; text-align: center; padding: 20px 0;">
          Chưa có bình luận nào ở chương này. Hãy là người đầu tiên để lại ý kiến nhé!
        </p>
      @endif
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  // 1. Chỉnh độ rộng khung đọc truyện (Image Container Width Control)
  let currentWidth = parseInt(localStorage.getItem('webcomics_reader_width') || '800');
  const container = document.getElementById('reader-container');
  if (container) container.style.maxWidth = currentWidth + 'px';

  function adjustWidth(delta) {
    currentWidth = Math.min(Math.max(currentWidth + delta, 500), 1200);
    if (container) container.style.maxWidth = currentWidth + 'px';
    localStorage.setItem('webcomics_reader_width', currentWidth);
  }

  // 2. PHÍM TẮT BÀN PHÍM (Arrow Left / Arrow Right) Chuyển chương nhanh
  document.addEventListener('keydown', function(e) {
    // Không kích hoạt phím tắt khi đang nhập liệu trong ô input/textarea/select
    const activeEl = document.activeElement;
    if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'SELECT')) {
      return;
    }

    if (e.key === 'ArrowLeft' || e.key === 'Left') {
      const prevBtn = document.getElementById('btn-prev-chap');
      if (prevBtn && prevBtn.href) {
        window.location.href = prevBtn.href;
      }
    } else if (e.key === 'ArrowRight' || e.key === 'Right') {
      const nextBtn = document.getElementById('btn-next-chap');
      if (nextBtn && nextBtn.href) {
        window.location.href = nextBtn.href;
      }
    }
  });

  // 3. Tự động ghi nhận Lịch sử đọc qua Fetch API khi cuộn lướt quá 50% trang truyện
  let historySaved = false;
  window.addEventListener('scroll', function() {
    if (historySaved) return;
    const scrollPercent = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight;

    if (scrollPercent > 0.45) {
      historySaved = true;
      @auth
        fetch("{{ route('history.save') }}", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest"
          },
          body: JSON.stringify({
            comic_id: {{ $comic->id }},
            chapter_id: {{ $chapter->id }}
          })
        })
        .then(res => res.json())
        .then(data => console.log("✅ Lịch sử đọc đã được tự động lưu:", data))
        .catch(err => console.error("Lỗi tự động lưu lịch sử đọc:", err));
      @endauth
    }
  });

  // 4. Gửi bình luận AJAX
  const cmtForm = document.getElementById('comment-form');
  if (cmtForm) {
    cmtForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const contentInput = document.getElementById('comment-content');
      const content = contentInput.value.trim();
      if (!content) return;

      fetch("{{ route('comments.store') }}", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          comic_id: {{ $comic->id }},
          chapter_id: {{ $chapter->id }},
          content: content
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          const cmtList = document.getElementById('comments-list');
          const noMsg = document.getElementById('no-comments-msg');
          if (noMsg) noMsg.remove();

          const newCmtHtml = `
            <div class="comment-item-card" style="animation: fadeIn 0.3s ease;">
              <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <strong style="color: var(--primary); font-size: 13.5px;">${data.comment.user_name}</strong>
                <span style="font-size: 11.5px; color: var(--text-muted);">${data.comment.time_ago}</span>
              </div>
              <p style="color: var(--text-main); margin: 0; font-size: 13.5px; line-height: 1.5;">${data.comment.content}</p>
            </div>
          `;
          cmtList.insertAdjacentHTML('afterbegin', newCmtHtml);
          contentInput.value = '';
        }
      })
      .catch(err => alert("Có lỗi xảy ra khi gửi bình luận!"));
    });
  }
</script>
@endpush
