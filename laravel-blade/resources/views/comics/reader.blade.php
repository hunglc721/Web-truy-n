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

  /* ── FE-04: CÀI ĐẶT CHẾ ĐỘ ĐỌC & TÙY CHỈNH ── */
  .reader-settings-panel {
    position: absolute;
    top: 55px;
    right: 24px;
    background: rgba(19, 22, 30, 0.98);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 14px;
    padding: 18px 20px;
    width: 320px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.85);
    z-index: 1000;
    display: none;
    animation: fadeIn 0.2s ease;
  }

  .setting-btn {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: #e0e0e0;
    padding: 7px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
  }
  .setting-btn:hover {
    background: rgba(255,255,255,0.12);
    border-color: rgba(255,255,255,0.25);
    color: #fff;
  }
  .setting-btn.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    font-weight: 700;
    box-shadow: 0 2px 10px rgba(255, 94, 54, 0.35);
  }

  /* ── 3 CHẾ ĐỘ ĐỌC (WEBTOON / SINGLE / DOUBLE) ── */
  :root {
    --reader-width: 800px;
    --page-spacing: 0px;
    --reader-brightness: 100%;
  }

  /* Reading Progress Bar (Top) */
  #reader-progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    height: 4px;
    width: 0%;
    background: linear-gradient(90deg, #ff5e36, #ff2a6d);
    z-index: 99999;
    transition: width 0.15s ease-out;
  }

  /* Webtoon / Vertical Continuous */
  .reader-layout-vertical #reader-container {
    display: flex;
    flex-direction: column;
    gap: var(--page-spacing, 0px);
  }
  .reader-layout-vertical .comic-page-wrapper {
    display: block !important;
    width: 100%;
  }

  /* Single Page Mode */
  .reader-layout-single #reader-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 85vh;
    gap: 0;
  }
  .reader-layout-single .comic-page-wrapper {
    display: none !important;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
  }
  .reader-layout-single .comic-page-wrapper.active-page {
    display: flex !important;
  }

  /* Double Page (Manga Spread) Mode */
  .reader-layout-double #reader-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 85vh;
    gap: 10px;
    flex-direction: row;
    max-width: 1200px !important;
  }
  .reader-layout-double.reader-dir-rtl #reader-container {
    flex-direction: row-reverse;
  }
  .reader-layout-double .comic-page-wrapper {
    display: none !important;
    width: 49% !important;
    flex: 1 1 49%;
    max-width: 50%;
  }
  .reader-layout-double .comic-page-wrapper.active-page {
    display: flex !important;
    justify-content: center;
    align-items: center;
  }

  /* Fit Height Mode */
  .reader-fit-height img.comic-page-img {
    max-height: calc(100vh - 110px) !important;
    width: auto !important;
    object-fit: contain !important;
  }

  /* Fit Width Mode */
  .reader-fit-width #reader-container {
    max-width: 100% !important;
    width: 100% !important;
  }

  /* Night Mode / Dimming Filter */
  body.reader-night-mode {
    background: #000 !important;
  }
  body.reader-night-mode img.comic-page-img {
    filter: brightness(0.8) contrast(1.05) !important;
  }

  /* Sticky Bottom Dock */
  .reader-bottom-dock {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(19, 22, 30, 0.95);
    backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 40px;
    padding: 6px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.85);
    z-index: 999;
    transition: all 0.3s ease;
  }

  /* Immersive Zen UI Hidden */
  body.ui-hidden .reader-toolbar,
  body.ui-hidden #reader-hint-bar,
  body.ui-hidden .reader-footer,
  body.ui-hidden .reader-bottom-dock,
  body.ui-hidden #single-page-nav {
    opacity: 0 !important;
    pointer-events: none !important;
    transform: translateY(-100%);
    transition: all 0.3s ease;
  }
  body.ui-hidden .reader-bottom-dock {
    transform: translate(-50%, 150%) !important;
  }
  body.ui-hidden .reader-footer {
    transform: translateY(100%);
  }
</style>
@endpush

@section('content')
<!-- ── TOP READING PROGRESS BAR ── -->
<div id="reader-progress-bar"></div>

<div class="reader-page-wrapper">

  <!-- ── 1. READER STICKY TOP TOOLBAR ── -->
  <div class="reader-toolbar" id="reader-top-bar">
    <div class="comic-info" style="display: flex; align-items: center; gap: 12px;">
      <a href="{{ route('comics.show', $comic->slug) }}" style="color: var(--primary); text-decoration: none; font-weight: 800; font-size: 15px;">
        ← {{ Str::limit($comic->title, 26) }}
      </a>
      <span style="color: rgba(255,255,255,0.2);">|</span>
      <strong style="color: #fff; font-size: 14px;">{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</strong>
    </div>

    <div class="reader-controls" style="display: flex; gap: 8px; align-items: center; position: relative;">

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
            Ch.{{ $item->chapter_number }} - {{ Str::limit($item->title, 22) }}
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

      {{-- Settings Button --}}
      <button type="button" class="reader-controls-btn" id="btn-open-settings" onclick="toggleSettingsPanel()" title="Cài đặt chế độ đọc & phím tắt" style="background: rgba(255,255,255,0.12);">
        ⚙️ Cài đặt
      </button>

      {{-- ── FLOATING SETTINGS PANEL (FE-04) ── --}}
      <div class="reader-settings-panel" id="reader-settings-panel" style="width: 330px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 8px;">
          <strong style="color: #fff; font-size: 14px;">⚙️ Tùy Chỉnh Chế Độ Đọc</strong>
          <button type="button" onclick="toggleSettingsPanel()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 16px; line-height: 1;">✕</button>
        </div>

        <!-- Reading Mode (3 Chế độ: Cuộn dọc / Từng trang / Trang đôi) -->
        <div style="margin-bottom: 14px;">
          <label style="display: block; font-size: 11.5px; color: var(--text-muted); margin-bottom: 6px; font-weight: 700;">CHẾ ĐỘ ĐỌC (PHÍM M)</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4px;">
            <button type="button" class="setting-btn active" id="btn-mode-vertical" onclick="setReadingLayout('vertical')">📜 Cuộn dọc</button>
            <button type="button" class="setting-btn" id="btn-mode-single" onclick="setReadingLayout('single')">📄 Từng trang</button>
            <button type="button" class="setting-btn" id="btn-mode-double" onclick="setReadingLayout('double')">📖 Trang đôi</button>
          </div>
        </div>

        <!-- Reading Direction -->
        <div style="margin-bottom: 14px;">
          <label style="display: block; font-size: 11.5px; color: var(--text-muted); margin-bottom: 6px; font-weight: 700;">HƯỚNG ĐỌC</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn active" id="btn-dir-ltr" onclick="setReadingDirection('ltr')">➡️ Trái qua Phải</button>
            <button type="button" class="setting-btn" id="btn-dir-rtl" onclick="setReadingDirection('rtl')">⬅️ Phải qua Trái (Manga)</button>
          </div>
        </div>

        <!-- Fit Mode & Width -->
        <div style="margin-bottom: 14px;">
          <label style="display: block; font-size: 11.5px; color: var(--text-muted); margin-bottom: 6px; font-weight: 700;">CĂN CHỈNH KHUNG ẢNH</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px;">
            <button type="button" class="setting-btn" id="btn-fit-width" onclick="setFitMode('fit-width')">↔️ Vừa chiều rộng</button>
            <button type="button" class="setting-btn" id="btn-fit-height" onclick="setFitMode('fit-height')">↕️ Vừa chiều cao</button>
          </div>
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px;">
            <button type="button" class="setting-btn" id="btn-w-680" onclick="setReaderWidth(680)">680px</button>
            <button type="button" class="setting-btn active" id="btn-w-800" onclick="setReaderWidth(800)">800px</button>
            <button type="button" class="setting-btn" id="btn-w-1000" onclick="setReaderWidth(1000)">1000px</button>
            <button type="button" class="setting-btn" id="btn-w-full" onclick="setReaderWidth('100%')">100%</button>
          </div>
        </div>

        <!-- Page Spacing -->
        <div style="margin-bottom: 14px;">
          <label style="display: block; font-size: 11.5px; color: var(--text-muted); margin-bottom: 6px; font-weight: 700;">KHOẢNG CÁCH TRANG</label>
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
            <button type="button" class="setting-btn active" id="btn-space-0" onclick="setPageSpacing(0)">0px (Liền)</button>
            <button type="button" class="setting-btn" id="btn-space-8" onclick="setPageSpacing(8)">8px</button>
            <button type="button" class="setting-btn" id="btn-space-16" onclick="setPageSpacing(16)">16px</button>
          </div>
        </div>

        <!-- Night Mode Toggle & Brightness Slider -->
        <div style="margin-bottom: 14px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label style="font-size: 11.5px; color: var(--text-muted); font-weight: 700;">🌙 CHẾ ĐỘ BAN ĐÊM & ĐỘ SÁNG</label>
            <span id="brightness-val" style="font-size: 12px; color: var(--primary); font-weight: 700;">100%</span>
          </div>
          <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
            <button type="button" class="setting-btn" id="btn-toggle-night" onclick="toggleNightMode()" style="flex: 1;">🌙 Giảm chói mắt</button>
          </div>
          <input type="range" id="brightness-slider" min="30" max="100" value="100" oninput="setBrightness(this.value)" style="width: 100%; accent-color: var(--primary); cursor: pointer;">
        </div>

        <!-- Hotkey Reference -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 10px; font-size: 11px; color: var(--text-muted); line-height: 1.6;">
          <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">⌨️ Phím tắt nhanh:</div>
          <div>• <code>←</code> / <code>→</code> hoặc <code>A</code> / <code>D</code>: Lật trang / Chap</div>
          <div>• <code>M</code>: Đổi chế độ đọc (Webtoon / Single / Double)</div>
          <div>• <code>H</code>: Ẩn/Hiện giao diện (Zen Mode)</div>
          <div>• <code>F</code>: Bật/Tắt Toàn màn hình</div>
          <div>• <code>J</code> / <code>K</code> hoặc <code>Space</code>: Cuộn mượt</div>
        </div>
      </div>

    </div>
  </div>

  {{-- Phím tắt nhắc nhở --}}
  <div id="reader-hint-bar" style="text-align: center; padding: 8px 0; background: rgba(0,0,0,0.4); border-bottom: 1px solid rgba(255,255,255,0.05); transition: all 0.3s ease;">
    <span class="hotkey-hint">💡 Phím tắt: <strong>←/→/A/D</strong> lật trang/chap • <strong>M</strong> đổi chế độ đọc • <strong>H</strong> ẩn giao diện • <strong>F</strong> toàn màn hình</span>
  </div>

  <!-- ── FLOATING SINGLE PAGE NAVIGATOR (Khi ở chế độ từng trang) ── -->
  <div id="single-page-nav" style="
    display: none;
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(19, 22, 30, 0.95);
    backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 40px;
    padding: 8px 18px;
    z-index: 999;
    align-items: center;
    gap: 12px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.8);
  ">
    <button type="button" onclick="prevPage()" class="reader-controls-btn" id="btn-single-prev" style="padding: 6px 14px; border-radius: 20px;">
      ◀ Trang trước
    </button>
    <span id="single-page-counter" style="font-size: 13px; font-weight: 700; color: #fff; min-width: 80px; text-align: center;">
      1 / 1
    </span>
    <button type="button" onclick="nextPage()" class="reader-controls-btn" id="btn-single-next" style="padding: 6px 14px; border-radius: 20px;">
      Trang sau ▶
    </button>
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
      $pagesWithDim = $chapter->pages_with_dimensions;
    @endphp

    @if(!empty($pagesWithDim))
      @foreach($pagesWithDim as $index => $page)
        <div class="comic-page-wrapper" id="page-{{ $index + 1 }}" style="
          position: relative;
          width: 100%;
          background: #050505;
          aspect-ratio: {{ $page['width'] }} / {{ $page['height'] }};
          contain: layout;
        ">
          <img
            src="{{ $page['url'] }}"
            width="{{ $page['width'] }}"
            height="{{ $page['height'] }}"
            alt="{{ $comic->title }} - Chapter {{ $chapter->chapter_number }} - Trang {{ $index + 1 }}"
            loading="{{ $index < 2 ? 'eager' : 'lazy' }}"
            decoding="async"
            data-page-index="{{ $index }}"
            data-original-src="{{ $page['url'] }}"
            data-retries="0"
            class="comic-page-img"
            style="
              width: 100%;
              height: auto;
              display: block;
              margin: 0 auto;
              aspect-ratio: {{ $page['width'] }} / {{ $page['height'] }};
            "
            onerror="handleImageError(this)"
          />
          <div style="position: absolute; bottom: 6px; right: 10px; background: rgba(0,0,0,0.6); color: rgba(255,255,255,0.6); font-size: 10px; padding: 2px 6px; border-radius: 4px; pointer-events: none;">
            {{ $index + 1 }} / {{ count($pagesWithDim) }}
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
      <a href="{{ route('chapters.show', [$comic->slug, $nextChapter->slug]) }}" class="reader-controls-btn" id="footer-btn-next-chap" style="padding:10px 20px; background:var(--primary); border-color:var(--primary)">
        Chapter Sau (Ch.{{ $nextChapter->chapter_number }}) →
      </a>
    @else
      <a href="{{ route('comics.show', $comic->slug) }}" class="reader-controls-btn" style="background:#22c55e; border-color:#22c55e">
        ✅ Đã Đọc Hết Chapter Mới Nhất
      </a>
    @endif
  </div>

  <!-- ── STICKY BOTTOM DOCK (FE-04 & Navigation) ── -->
  <div class="reader-bottom-dock" id="reader-bottom-dock">
    @if($prevChapter)
      <a href="{{ route('chapters.show', [$comic->slug, $prevChapter->slug]) }}" class="reader-controls-btn" style="padding: 6px 12px; border-radius: 20px;" title="Chương trước">
        ◀ Chap trước
      </a>
    @endif

    <div id="dock-page-nav" style="display: none; align-items: center; gap: 6px;">
      <button type="button" onclick="prevPage()" class="reader-controls-btn" style="padding: 5px 10px; border-radius: 20px;">◀</button>
      <span id="dock-page-counter" style="font-size: 12px; font-weight: 700; color: #fff; min-width: 50px; text-align: center;">1 / 1</span>
      <button type="button" onclick="nextPage()" class="reader-controls-btn" style="padding: 5px 10px; border-radius: 20px;">▶</button>
    </div>

    <select onchange="if(this.value) location.href = this.value;" class="reader-chapter-select" style="padding: 6px 12px; max-width: 170px; font-size: 12.5px;">
      @foreach($allChapters as $item)
        <option value="{{ route('chapters.show', [$comic->slug, $item->slug]) }}" {{ $item->id == $chapter->id ? 'selected' : '' }}>
          Ch.{{ $item->chapter_number }} - {{ Str::limit($item->title, 18) }}
        </option>
      @endforeach
    </select>

    @if($nextChapter)
      <a href="{{ route('chapters.show', [$comic->slug, $nextChapter->slug]) }}" class="reader-controls-btn" id="dock-btn-next-chap" style="padding: 6px 12px; border-radius: 20px;" title="Chương sau">
        Chap sau ▶
      </a>
    @endif

    <button type="button" onclick="toggleSettingsPanel()" class="reader-controls-btn" style="padding: 6px 10px; border-radius: 20px;" title="Cài đặt (⚙️)">⚙️</button>
    <button type="button" onclick="toggleFullscreen()" class="reader-controls-btn" style="padding: 6px 10px; border-radius: 20px;" title="Toàn màn hình (F)">⛶</button>
    <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })" class="reader-controls-btn" style="padding: 6px 10px; border-radius: 20px;" title="Lên đầu trang">⬆️</button>
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
          <div class="comment-item-card" id="comment-{{ $cmt->id }}">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
              <strong style="color: var(--primary); font-size: 13.5px;">{{ $cmt->user->name ?? 'Thành viên' }}</strong>
              <span style="font-size: 11.5px; color: var(--text-muted);">{{ $cmt->time_ago }}</span>
            </div>
            <p style="color: var(--text-main); margin: 0; font-size: 13.5px; line-height: 1.5;">{{ $cmt->content }}</p>

            {{-- Render Replies nếu có (đã eager-loaded with('replies.user') chống N+1) --}}
            @if($cmt->replies && $cmt->replies->isNotEmpty())
              <div class="replies-list" style="margin-top: 10px; padding-left: 14px; border-left: 2px solid rgba(255,255,255,0.1);">
                @foreach($cmt->replies as $reply)
                  <div style="background: rgba(255,255,255,0.02); border-radius: 6px; padding: 8px 12px; margin-top: 6px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                      <strong style="color: #60a5fa; font-size: 12.5px;">{{ $reply->user->name ?? 'Thành viên' }}</strong>
                      <span style="font-size: 11px; color: var(--text-muted);">{{ $reply->time_ago }}</span>
                    </div>
                    <p style="color: var(--text-main); margin: 0; font-size: 13px; line-height: 1.4;">{{ $reply->content }}</p>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      @else
        <p id="no-comments-msg" style="color: var(--text-muted); font-style: italic; font-size: 13.5px; text-align: center; padding: 20px 0;">
          Chưa có bình luận nào ở chương này. Hãy là người đầu tiên để lại ý kiến nhé!
        </p>
      @endif
    </div>
  </div>

  <!-- ── 5. FLOATING RESUME SCROLL TOAST ── -->
  <div id="resume-scroll-toast" style="
    display: none;
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(19, 22, 30, 0.95);
    backdrop-filter: blur(14px);
    border: 1px solid var(--primary);
    box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 20px rgba(255, 94, 54, 0.35);
    color: #fff;
    padding: 10px 18px;
    border-radius: 50px;
    z-index: 9999;
    align-items: center;
    gap: 12px;
    font-size: 13.5px;
    font-weight: 700;
  ">
    <span id="resume-text">📖 Tiếp tục từ 0%</span>
    <button type="button" onclick="scrollToTopChapter()" style="
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.2);
      color: #fff;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    " onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">
      ⬆️ Về đầu chương
    </button>
    <button type="button" onclick="dismissResumeToast()" style="
      background: transparent;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      font-size: 15px;
      line-height: 1;
      padding: 0 4px;
    " title="Đóng">✕</button>
  </div>

</div>
@endsection

@push('scripts')
<script>
  // 0. Khôi phục vị trí đọc dở (Resume Scroll Position)
  let initialScrollPercent = {{ (float) ($lastScrollPercent ?? 0) }};

  @guest
  try {
    const list = JSON.parse(localStorage.getItem('webcomics_guest_history') || '[]');
    const item = list.find(i => i.comicId === {{ $comic->id }} && i.chapterNum === {{ $chapter->chapter_number }});
    if (item && item.percent) {
      initialScrollPercent = parseFloat(item.percent);
    }
  } catch(e) {}
  @endguest

  function scrollToTopChapter() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    dismissResumeToast();
  }

  function dismissResumeToast() {
    const toast = document.getElementById('resume-scroll-toast');
    if (toast) toast.style.display = 'none';
  }

  function calculateCurrentScrollPercent() {
    if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
      if (totalPagesCount <= 1) return 100;
      let currentIndex = currentSinglePageIndex;
      if (readerSettings.layout === 'double') {
        currentIndex = Math.min(currentIndex + 1, totalPagesCount - 1);
      }
      const percent = (currentIndex / (totalPagesCount - 1)) * 100;
      return Math.min(Math.max(Math.round(percent * 100) / 100, 0), 100);
    } else {
      const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
      if (maxScroll <= 0) return 100;
      const percent = (window.scrollY / maxScroll) * 100;
      return Math.min(Math.max(Math.round(percent * 100) / 100, 0), 100);
    }
  }

  function updateProgress() {
    const percent = calculateCurrentScrollPercent();
    const progressBar = document.getElementById('reader-progress-bar');
    if (progressBar) progressBar.style.width = percent + '%';
    @guest
    saveGuestReadingHistory(percent);
    @endguest
  }

  const restorePosition = function() {
    if (initialScrollPercent < 3) return;

    if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
      if (totalPagesCount > 1) {
        let targetIndex = Math.round((initialScrollPercent / 100) * (totalPagesCount - 1));
        showPage(targetIndex);
        showResumeToast();
      }
    } else {
      const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
      if (maxScroll > 50) {
        const targetTop = (initialScrollPercent / 100) * maxScroll;
        window.scrollTo({ top: targetTop, behavior: 'instant' });
        showResumeToast();
      }
    }
  };

  function showResumeToast() {
    const toast = document.getElementById('resume-scroll-toast');
    const text  = document.getElementById('resume-text');
    if (toast && text) {
      text.textContent = `📖 Tiếp tục từ ${Math.round(initialScrollPercent)}%`;
      toast.style.display = 'inline-flex';
      setTimeout(dismissResumeToast, 8000);
    }
  }

  if (document.readyState === 'complete') {
    setTimeout(restorePosition, 100);
  } else {
    window.addEventListener('load', function() {
      setTimeout(restorePosition, 150);
    });
  }

  // ── FE-04: CÀI ĐẶT CHẾ ĐỘ ĐỌC & TÙY CHỈNH (localStorage Persistent) ──
  let readerSettings = {
    layout: 'vertical',    // 'vertical' | 'single' | 'double'
    direction: 'ltr',      // 'ltr' | 'rtl'
    width: 800,            // 680 | 800 | 1000 | '100%'
    spacing: 0,            // 0 | 8 | 16
    brightness: 100,       // 30..100
    night: false           // true | false
  };

  let currentSinglePageIndex = 0;
  const pageWrappers = document.querySelectorAll('.comic-page-wrapper');
  const totalPagesCount = pageWrappers.length;

  function loadReaderSettings() {
    try {
      const saved = localStorage.getItem('webcomics_reader_settings');
      if (saved) {
        readerSettings = { ...readerSettings, ...JSON.parse(saved) };
      }
    } catch (e) {
      console.debug('Error reading reader settings:', e);
    }
  }

  function saveReaderSettings() {
    try {
      localStorage.setItem('webcomics_reader_settings', JSON.stringify(readerSettings));
    } catch (e) {
      console.debug('Error saving reader settings:', e);
    }
  }

  function applyAllReaderSettings() {
    setReadingLayout(readerSettings.layout || 'vertical', false);
    setReadingDirection(readerSettings.direction || 'ltr', false);
    setReaderWidth(readerSettings.width || 800, false);
    setPageSpacing(readerSettings.spacing || 0, false);
    setBrightness(readerSettings.brightness || 100, false);
    if (readerSettings.night) {
      document.body.classList.add('reader-night-mode');
      document.getElementById('btn-toggle-night')?.classList.add('active');
    }
  }

  function toggleSettingsPanel() {
    const panel = document.getElementById('reader-settings-panel');
    if (panel) {
      panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
    }
  }

  document.addEventListener('click', function(e) {
    const panel = document.getElementById('reader-settings-panel');
    const btn = document.getElementById('btn-open-settings');
    const dockBtn = document.querySelector('.reader-bottom-dock button[title*="Cài đặt"]');
    if (panel && panel.style.display === 'block') {
      if (!panel.contains(e.target) && !btn?.contains(e.target) && !dockBtn?.contains(e.target)) {
        panel.style.display = 'none';
      }
    }
  });

  // 1. CHẾ ĐỘ ĐỌC (Vertical / Single / Double Page)
  function setReadingLayout(mode, persist = true) {
    readerSettings.layout = mode;
    const body = document.body;
    const singleNav = document.getElementById('single-page-nav');
    const dockPageNav = document.getElementById('dock-page-nav');
    const btnVert = document.getElementById('btn-mode-vertical');
    const btnSingle = document.getElementById('btn-mode-single');
    const btnDouble = document.getElementById('btn-mode-double');

    body.classList.remove('reader-layout-vertical', 'reader-layout-single', 'reader-layout-double');
    btnVert?.classList.remove('active');
    btnSingle?.classList.remove('active');
    btnDouble?.classList.remove('active');

    if (mode === 'single') {
      body.classList.add('reader-layout-single');
      btnSingle?.classList.add('active');
      if (singleNav) singleNav.style.display = 'inline-flex';
      if (dockPageNav) dockPageNav.style.display = 'inline-flex';
      showPage(currentSinglePageIndex);
    } else if (mode === 'double') {
      body.classList.add('reader-layout-double');
      btnDouble?.classList.add('active');
      if (singleNav) singleNav.style.display = 'inline-flex';
      if (dockPageNav) dockPageNav.style.display = 'inline-flex';
      showPage(currentSinglePageIndex);
    } else {
      body.classList.add('reader-layout-vertical');
      btnVert?.classList.add('active');
      if (singleNav) singleNav.style.display = 'none';
      if (dockPageNav) dockPageNav.style.display = 'none';
      pageWrappers.forEach(w => w.classList.remove('active-page'));
    }

    if (persist) saveReaderSettings();
  }

  function showPage(index) {
    if (totalPagesCount === 0) return;

    if (readerSettings.layout === 'double') {
      // Làm tròn về số chẵn (0, 2, 4...)
      currentSinglePageIndex = Math.floor(Math.max(0, Math.min(index, totalPagesCount - 1)) / 2) * 2;
      pageWrappers.forEach((wrapper, idx) => {
        if (idx === currentSinglePageIndex || idx === currentSinglePageIndex + 1) {
          wrapper.classList.add('active-page');
        } else {
          wrapper.classList.remove('active-page');
        }
      });
      const endIdx = Math.min(currentSinglePageIndex + 2, totalPagesCount);
      const text = `${currentSinglePageIndex + 1}-${endIdx} / ${totalPagesCount}`;
      const singleCounter = document.getElementById('single-page-counter');
      const dockCounter = document.getElementById('dock-page-counter');
      if (singleCounter) singleCounter.textContent = text;
      if (dockCounter) dockCounter.textContent = text;
    } else {
      currentSinglePageIndex = Math.min(Math.max(index, 0), totalPagesCount - 1);
      pageWrappers.forEach((wrapper, idx) => {
        if (idx === currentSinglePageIndex) {
          wrapper.classList.add('active-page');
        } else {
          wrapper.classList.remove('active-page');
        }
      });
      const text = `${currentSinglePageIndex + 1} / ${totalPagesCount}`;
      const singleCounter = document.getElementById('single-page-counter');
      const dockCounter = document.getElementById('dock-page-counter');
      if (singleCounter) singleCounter.textContent = text;
      if (dockCounter) dockCounter.textContent = text;
    }

    window.scrollTo({ top: 0, behavior: 'instant' });
    setTimeout(updateProgress, 50);
  }

  function nextPage() {
    if (readerSettings.layout === 'double') {
      if (currentSinglePageIndex + 2 < totalPagesCount) {
        showPage(currentSinglePageIndex + 2);
      } else {
        const nextBtn = document.getElementById('btn-next-chap') || document.getElementById('footer-btn-next-chap');
        if (nextBtn && nextBtn.href) window.location.href = nextBtn.href;
      }
    } else if (readerSettings.layout === 'single') {
      if (currentSinglePageIndex < totalPagesCount - 1) {
        showPage(currentSinglePageIndex + 1);
      } else {
        const nextBtn = document.getElementById('btn-next-chap') || document.getElementById('footer-btn-next-chap');
        if (nextBtn && nextBtn.href) window.location.href = nextBtn.href;
      }
    }
  }

  function prevPage() {
    if (readerSettings.layout === 'double') {
      if (currentSinglePageIndex >= 2) {
        showPage(currentSinglePageIndex - 2);
      } else {
        const prevBtn = document.getElementById('btn-prev-chap');
        if (prevBtn && prevBtn.href) window.location.href = prevBtn.href;
      }
    } else if (readerSettings.layout === 'single') {
      if (currentSinglePageIndex > 0) {
        showPage(currentSinglePageIndex - 1);
      } else {
        const prevBtn = document.getElementById('btn-prev-chap');
        if (prevBtn && prevBtn.href) window.location.href = prevBtn.href;
      }
    }
  }

  // 2. HƯỚNG ĐỌC (LTR vs RTL Manga)
  function setReadingDirection(dir, persist = true) {
    readerSettings.direction = dir;
    const body = document.body;
    const btnLtr = document.getElementById('btn-dir-ltr');
    const btnRtl = document.getElementById('btn-dir-rtl');

    if (dir === 'rtl') {
      body.classList.add('reader-dir-rtl');
      btnLtr?.classList.remove('active');
      btnRtl?.classList.add('active');
    } else {
      body.classList.remove('reader-dir-rtl');
      btnLtr?.classList.add('active');
      btnRtl?.classList.remove('active');
    }

    if (persist) saveReaderSettings();
  }

  // 3. CĂN CHỈNH KHUNG ẢNH & ĐỘ RỘNG (Fit Mode / Width)
  function setFitMode(mode, persist = true) {
    const body = document.body;
    const btnWidth = document.getElementById('btn-fit-width');
    const btnHeight = document.getElementById('btn-fit-height');

    body.classList.remove('reader-fit-width', 'reader-fit-height');

    if (mode === 'fit-width') {
      body.classList.add('reader-fit-width');
      btnWidth?.classList.add('active');
      btnHeight?.classList.remove('active');
    } else if (mode === 'fit-height') {
      body.classList.add('reader-fit-height');
      btnWidth?.classList.remove('active');
      btnHeight?.classList.add('active');
    } else {
      btnWidth?.classList.remove('active');
      btnHeight?.classList.remove('active');
    }
  }

  function setReaderWidth(w, persist = true) {
    readerSettings.width = w;
    setFitMode('custom', false);
    const container = document.getElementById('reader-container');
    const btns = {
      '680': document.getElementById('btn-w-680'),
      '800': document.getElementById('btn-w-800'),
      '1000': document.getElementById('btn-w-1000'),
      '100%': document.getElementById('btn-w-full'),
    };

    Object.values(btns).forEach(b => b?.classList.remove('active'));
    btns[String(w)]?.classList.add('active');

    if (container) {
      container.style.maxWidth = (w === '100%') ? '100%' : (w + 'px');
    }

    if (persist) saveReaderSettings();
  }

  // 4. KHOẢNG CÁCH TRANG (Page Spacing)
  function setPageSpacing(spacing, persist = true) {
    readerSettings.spacing = spacing;
    const container = document.getElementById('reader-container');
    const btns = {
      '0': document.getElementById('btn-space-0'),
      '8': document.getElementById('btn-space-8'),
      '16': document.getElementById('btn-space-16'),
    };

    Object.values(btns).forEach(b => b?.classList.remove('active'));
    btns[String(spacing)]?.classList.add('active');

    if (container) {
      container.style.gap = spacing + 'px';
      document.documentElement.style.setProperty('--page-spacing', spacing + 'px');
    }

    if (persist) saveReaderSettings();
  }

  // 5. CHẾ ĐỘ BAN ĐÊM & ĐỘ SÁNG
  function toggleNightMode(persist = true) {
    readerSettings.night = !readerSettings.night;
    const btn = document.getElementById('btn-toggle-night');
    if (readerSettings.night) {
      document.body.classList.add('reader-night-mode');
      btn?.classList.add('active');
    } else {
      document.body.classList.remove('reader-night-mode');
      btn?.classList.remove('active');
    }
    if (persist) saveReaderSettings();
  }

  function setBrightness(val, persist = true) {
    const num = Math.min(Math.max(parseInt(val, 10) || 100, 30), 100);
    readerSettings.brightness = num;

    const container = document.getElementById('reader-container');
    if (container) {
      container.style.filter = `brightness(${num}%)`;
    }

    const slider = document.getElementById('brightness-slider');
    const valLabel = document.getElementById('brightness-val');
    if (slider) slider.value = num;
    if (valLabel) valLabel.textContent = `${num}%`;

    if (persist) saveReaderSettings();
  }

  // 6. TOÀN MÀN HÌNH (Fullscreen)
  function toggleFullscreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {});
    } else {
      document.exitFullscreen().catch(() => {});
    }
  }

  // 7. ẨN / HIỆN UI (Zen Mode)
  function toggleUI() {
    document.body.classList.toggle('ui-hidden');
  }

  document.addEventListener('click', function(e) {
    const isInteractive = e.target.closest('button, a, input, select, textarea, .reader-toolbar, .reader-settings-panel, .reader-bottom-dock, #single-page-nav, #resume-scroll-toast, .broken-image-box');
    if (!isInteractive && e.target.closest('.reader-page-wrapper')) {
      toggleUI();
    }
  });

  // 8. BỘ PHÍM TẮT ĐIỀU HƯỚNG
  document.addEventListener('keydown', function(e) {
    const activeEl = document.activeElement;
    if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'SELECT')) {
      return;
    }

    const key = e.key;

    // Phím M: Đổi chế độ đọc
    if (key === 'm' || key === 'M') {
      e.preventDefault();
      const current = readerSettings.layout || 'vertical';
      const next = current === 'vertical' ? 'single' : (current === 'single' ? 'double' : 'vertical');
      setReadingLayout(next);
      return;
    }

    // Phím H: Zen mode
    if (key === 'h' || key === 'H') {
      e.preventDefault();
      toggleUI();
      return;
    }

    // Phím F: Fullscreen
    if (key === 'f' || key === 'F') {
      e.preventDefault();
      toggleFullscreen();
      return;
    }

    // Phím J hoặc Space: Cuộn mượt xuống / Trang sau
    if (key === 'j' || key === 'J' || (key === ' ' && !e.shiftKey)) {
      e.preventDefault();
      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.direction === 'rtl') prevPage(); else nextPage();
      } else {
        window.scrollBy({ top: 380, behavior: 'smooth' });
      }
      return;
    }

    // Phím K hoặc Shift+Space: Cuộn mượt lên / Trang trước
    if (key === 'k' || key === 'K' || (key === ' ' && e.shiftKey)) {
      e.preventDefault();
      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.direction === 'rtl') nextPage(); else prevPage();
      } else {
        window.scrollBy({ top: -380, behavior: 'smooth' });
      }
      return;
    }

    // Phím Mũi tên Trái / A
    if (key === 'ArrowLeft' || key === 'Left' || key === 'a' || key === 'A') {
      e.preventDefault();
      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.direction === 'rtl') nextPage(); else prevPage();
      } else {
        const prevBtn = document.getElementById('btn-prev-chap');
        if (prevBtn && prevBtn.href) window.location.href = prevBtn.href;
      }
      return;
    }

    // Phím Mũi tên Phải / D
    if (key === 'ArrowRight' || key === 'Right' || key === 'd' || key === 'D') {
      e.preventDefault();
      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.direction === 'rtl') prevPage(); else nextPage();
      } else {
        const nextBtn = document.getElementById('btn-next-chap') || document.getElementById('footer-btn-next-chap');
        if (nextBtn && nextBtn.href) window.location.href = nextBtn.href;
      }
      return;
    }
  });

  // Cập nhật thanh tiến độ đọc (Top Progress Bar) & Guest history
  window.addEventListener('scroll', updateProgress, { passive: true });

  function saveGuestReadingHistory(scrollPercent) {
    try {
      const currentItem = {
        comicId: {{ $comic->id }},
        title: @json($comic->title),
        cover: @json($comic->cover_image),
        chapterNum: {{ $chapter->chapter_number }},
        chapterTitle: @json($chapter->title ?: 'Chapter ' . $chapter->chapter_number),
        url: window.location.pathname,
        comicUrl: "{{ route('comics.show', $comic->slug) }}",
        percent: scrollPercent,
        time: Date.now()
      };
      let list = JSON.parse(localStorage.getItem('webcomics_guest_history') || '[]');
      list = list.filter(i => i.comicId !== currentItem.comicId);
      list.unshift(currentItem);
      localStorage.setItem('webcomics_guest_history', JSON.stringify(list.slice(0, 8)));
    } catch (_) {}
  }

  // Khởi động các thiết lập ngay khi tải trang
  loadReaderSettings();
  applyAllReaderSettings();

  // Hash anchor jump
  if (window.location.hash && window.location.hash.startsWith('#page-')) {
    const targetPageNum = parseInt(window.location.hash.replace('#page-', ''), 10);
    if (!isNaN(targetPageNum) && targetPageNum >= 1) {
      setTimeout(() => {
        if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
          showPage(targetPageNum - 1);
        } else {
          const el = document.getElementById('page-' + targetPageNum);
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 300);
    }
  }

  // 3. Tự động ghi nhận Lịch sử đọc (Throttled 25s + sendBeacon khi rời trang)
  @auth
  (function() {
    let lastSavedTime = 0;
    let isPendingSave = false;
    const THROTTLE_INTERVAL_MS = 25000; // Tối đa 1 request mỗi 25 giây khi đang đọc

    function sendHistoryPing(useBeacon = false) {
      const scrollPercent = calculateCurrentScrollPercent();
      const payload = {
        comic_id: {{ $comic->id }},
        chapter_id: {{ $chapter->id }},
        scroll_percent: scrollPercent
      };
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const url = "{{ route('history.save') }}";

      if (useBeacon && navigator.sendBeacon) {
        const formData = new FormData();
        formData.append('comic_id', payload.comic_id);
        formData.append('chapter_id', payload.chapter_id);
        formData.append('scroll_percent', payload.scroll_percent);
        formData.append('_token', csrfToken);
        navigator.sendBeacon(url, formData);
        isPendingSave = false;
        lastSavedTime = Date.now();
        return;
      }

      fetch(url, {
        method: "POST",
        keepalive: true,
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        isPendingSave = false;
        lastSavedTime = Date.now();
      })
      .catch(err => console.debug("Save history ping:", err));
    }

    // Lắng nghe cuộn trang với throttle 25s
    let scrollTimeout;
    window.addEventListener('scroll', function() {
      const scrollPercent = calculateCurrentScrollPercent();
      if (scrollPercent >= 5) {
        isPendingSave = true;
        const now = Date.now();
        if (now - lastSavedTime >= THROTTLE_INTERVAL_MS) {
          sendHistoryPing();
        } else {
          clearTimeout(scrollTimeout);
          scrollTimeout = setTimeout(function() {
            if (isPendingSave && Date.now() - lastSavedTime >= THROTTLE_INTERVAL_MS) {
              sendHistoryPing();
            }
          }, THROTTLE_INTERVAL_MS - (now - lastSavedTime));
        }
      }
    }, { passive: true });

    // Gửi beacon khi rời trang hoặc ẩn tab nếu có tiến độ chưa lưu
    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'hidden' && isPendingSave) {
        sendHistoryPing(true);
      }
    });

    window.addEventListener('pagehide', function() {
      if (isPendingSave) {
        sendHistoryPing(true);
      }
    });
  })();
  @endauth

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
      .catch(err => {
        console.debug("Lỗi khi gửi bình luận:", err);
      });
    });
  }

  // 5. SMART IMAGE PREFETCH & PRELOAD ENGINE (Preload 3 ảnh kế tiếp & ảnh đầu chương sau)
  (function() {
    const pageUrls = @json(collect($pagesWithDim)->pluck('url'));
    const nextChapFirstUrl = @json($nextChapter ? ($nextChapter->pages_with_dimensions[0]['url'] ?? null) : null);
    const prefetchedUrls = new Set();

    function preloadImage(url) {
      if (!url || prefetchedUrls.has(url)) return;
      prefetchedUrls.add(url);

      // 1. Thêm link prefetch tag
      const link = document.createElement('link');
      link.rel = 'prefetch';
      link.as = 'image';
      link.href = url;
      document.head.appendChild(link);

      // 2. Preload ngầm qua Image object
      const img = new Image();
      img.src = url;
    }

    // A. Ngay khi load: Preload 3 ảnh kế tiếp đầu tiên (trang 1, 2, 3)
    const initialPreloadCount = Math.min(4, pageUrls.length);
    for (let i = 0; i < initialPreloadCount; i++) {
      preloadImage(pageUrls[i]);
    }

    // B. Preload ảnh đầu tiên của chương sau (sau khi trang đã ổn định)
    if (nextChapFirstUrl) {
      setTimeout(function() {
        preloadImage(nextChapFirstUrl);
      }, 1500);
    }

    // C. Khi cuộn tới ảnh thứ N: Preload 3 ảnh kế tiếp (N+1, N+2, N+3)
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            const index = parseInt(entry.target.getAttribute('data-page-index') || '0', 10);
            for (let offset = 1; offset <= 3; offset++) {
              if (index + offset < pageUrls.length) {
                preloadImage(pageUrls[index + offset]);
              }
            }

            // Khi đọc tới gần cuối chương (còn 3 trang), đảm bảo đã prefetch ảnh đầu chương sau
            if (index >= pageUrls.length - 3 && nextChapFirstUrl) {
              preloadImage(nextChapFirstUrl);
            }
          }
        });
      }, {
        rootMargin: '600px 0px', // Đón đầu trước 600px
        threshold: 0.01
      });

      document.querySelectorAll('.comic-page-img').forEach(function(img) {
        observer.observe(img);
      });
    }
  })();

  // 6. XỬ LÝ ẢNH LỖI (Retry 2 lần + Báo lỗi tại chỗ cho Admin)
  function handleImageError(img) {
    let retries = parseInt(img.getAttribute('data-retries') || '0', 10);
    const originalSrc = img.getAttribute('data-original-src') || img.src;

    if (retries < 2) {
      retries++;
      img.setAttribute('data-retries', retries);
      // Thử lại sau 800ms kèm cache-buster
      setTimeout(function() {
        const sep = originalSrc.includes('?') ? '&' : '?';
        img.src = originalSrc + sep + '_retry=' + retries + '&_t=' + Date.now();
      }, retries * 800);
    } else {
      // Quá 2 lần thử lại thất bại → Render khung báo lỗi tại chỗ
      img.style.display = 'none';
      const wrapper = img.closest('.comic-page-wrapper');
      if (wrapper && !wrapper.querySelector('.broken-image-box')) {
        const pageIndex = parseInt(img.getAttribute('data-page-index') || '0', 10);
        const pageNumber = pageIndex + 1;

        const box = document.createElement('div');
        box.className = 'broken-image-box';
        box.style.cssText = `
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 40px 20px;
          background: rgba(239, 68, 68, 0.08);
          border: 2px dashed rgba(239, 68, 68, 0.4);
          border-radius: 12px;
          margin: 20px auto;
          max-width: 90%;
          text-align: center;
          color: #fff;
        `;
        box.innerHTML = `
          <div style="font-size: 36px; margin-bottom: 8px;">⚠️</div>
          <h4 style="font-size: 16px; font-weight: 700; color: #f87171; margin: 0 0 6px;">
            Ảnh lỗi — Trang ${pageNumber}
          </h4>
          <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 16px; max-width: 400px; line-height: 1.5;">
            Không thể tải hình ảnh trang này sau 2 lần thử lại. Hãy báo ngay cho ban quản trị để sửa ảnh!
          </p>
          <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
            <button type="button" onclick="manualRetryPage(this, ${pageIndex})" style="
              background: rgba(255,255,255,0.12);
              border: 1px solid rgba(255,255,255,0.25);
              color: #fff;
              padding: 8px 18px;
              border-radius: 8px;
              font-size: 13px;
              font-weight: 600;
              cursor: pointer;
              transition: all 0.2s;
            ">🔄 Thử lại lần nữa</button>
            <button type="button" onclick="reportBrokenImage(this, ${pageNumber}, '${originalSrc}')" style="
              background: #ef4444;
              border: 1px solid #dc2626;
              color: #fff;
              padding: 8px 20px;
              border-radius: 8px;
              font-size: 13px;
              font-weight: 700;
              cursor: pointer;
              box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);
              transition: all 0.2s;
            ">🚨 Báo lỗi cho Admin</button>
          </div>
        `;
        wrapper.appendChild(box);
      }
    }
  }

  function manualRetryPage(btn, pageIndex) {
    const wrapper = btn.closest('.comic-page-wrapper');
    if (!wrapper) return;
    const img = wrapper.querySelector('.comic-page-img');
    const box = wrapper.querySelector('.broken-image-box');
    if (img) {
      if (box) box.remove();
      img.setAttribute('data-retries', '0');
      img.style.display = 'block';
      const originalSrc = img.getAttribute('data-original-src') || img.src;
      const sep = originalSrc.includes('?') ? '&' : '?';
      img.src = originalSrc + sep + '_retry=manual&_t=' + Date.now();
    }
  }

  function reportBrokenImage(btn, pageNumber, imageUrl) {
    btn.disabled = true;
    btn.innerHTML = '⏳ Đang gửi báo cáo...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch("{{ route('reports.store') }}", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken,
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      },
      body: JSON.stringify({
        comic_id: {{ $comic->id }},
        chapter_id: {{ $chapter->id }},
        page_number: pageNumber,
        image_url: imageUrl,
        type: 'broken_image',
        description: `Ảnh bị lỗi không tải được tại trang ${pageNumber} chương {{ $chapter->chapter_number }}`
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        btn.style.background = '#16a34a';
        btn.style.borderColor = '#16a34a';
        btn.innerHTML = '✓ Đã báo lỗi thành công!';
      } else {
        btn.disabled = false;
        btn.innerHTML = '🚨 Báo lỗi cho Admin';
        alert(data.message || 'Không thể gửi báo lỗi, vui lòng thử lại!');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '🚨 Báo lỗi cho Admin';
      alert('Lỗi kết nối khi gửi báo cáo!');
    });
  }
</script>
@endpush
