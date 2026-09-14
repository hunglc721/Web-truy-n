{{-- resources/views/comics/reader.blade.php --}}
@extends('layouts.main')

@section('title', $comic->title . ' - ' . ($chapter->title ?: 'Chapter ' . $chapter->chapter_number) . ' | WebComics Reader')

@push('styles')
<style>
  html,
  body {
    background: #0d0f14 !important;
    overflow-x: clip !important;
    overflow-y: visible !important;
  }

  .reader-page-wrapper {
    background: #0d0f14;
    color: #e0e0e0;
    min-height: 100vh;
  }

  .reader-toolbar {
    position: sticky;
    top: var(--header-height, 72px);
    z-index: 900;
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

  /* ── FE-04: CÀI ĐẶT CHẾ ĐỘ ĐỌC & TÙY CHỈNH (STICKY / FLOATING) ── */
  .reader-main-wrapper {
    display: flex;
    justify-content: center;
    align-items: stretch;
    position: relative;
    width: 100%;
    min-height: 600px;
    margin: 0 auto;
    transition: all 0.25s ease;
  }

  @media (min-width: 1480px) {
    .reader-main-wrapper::before {
      content: '';
      display: none;
      width: 350px;
      margin-right: 20px;
      flex-shrink: 0;
    }
    body.reader-panel-open .reader-main-wrapper::before {
      display: block;
    }
  }

  .reader-settings-track {
    width: 350px;
    flex-shrink: 0;
    margin-left: 20px;
    position: relative;
    display: none;
    z-index: 990;
  }

  body.reader-panel-open .reader-settings-track {
    display: block;
  }

  .reader-settings-panel {
    position: sticky;
    top: calc(var(--header-height, 72px) + 68px);
    width: 350px;
    max-height: calc(100vh - 150px);
    overflow-y: auto;
    overscroll-behavior: contain;
    background: rgba(19, 22, 30, 0.96);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.85);
    z-index: 990;
    display: none;
    transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .reader-settings-panel.is-open {
    display: block;
    animation: fadeIn 0.2s ease;
  }

  .reader-settings-panel::-webkit-scrollbar {
    width: 5px;
  }
  .reader-settings-panel::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.04);
    border-radius: 4px;
  }
  .reader-settings-panel::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
  }
  .reader-settings-panel::-webkit-scrollbar-thumb:hover {
    background: var(--primary);
  }

  .sheet-grab-handle {
    display: none;
  }

  .reader-settings-backdrop {
    display: none;
  }

  .reader-settings-close-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    padding: 4px 8px;
    border-radius: 4px;
    transition: color 0.15s ease, background 0.15s ease;
  }
  .reader-settings-close-btn:hover {
    color: #fff;
    background: rgba(255,255,255,0.08);
  }

  .setting-btn:focus-visible,
  .reader-settings-close-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
  }

  @media (max-width: 1023.98px) {
    .reader-main-wrapper {
      display: block;
    }
    #reader-container {
      margin: 0 auto !important;
    }
    .reader-settings-track {
      position: static;
      width: 0;
      margin: 0;
      display: block;
    }
  }

  @media (min-width: 768px) and (max-width: 1023.98px) {
    .reader-settings-panel {
      position: fixed !important;
      top: calc(var(--header-height, 72px) + 68px) !important;
      right: 16px !important;
      left: auto !important;
      bottom: auto !important;
      width: 350px !important;
      max-height: calc(100vh - 160px) !important;
      z-index: 1001 !important;
    }
  }

  @media (max-width: 767.98px) {
    .reader-settings-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      z-index: 1000;
    }
    .reader-settings-backdrop.is-open {
      display: block;
      animation: fadeIn 0.2s ease;
    }
    .sheet-grab-handle {
      display: block;
      width: 40px;
      height: 4px;
      background: rgba(255, 255, 255, 0.25);
      border-radius: 4px;
      margin: 0 auto 12px auto;
    }
    .reader-settings-panel {
      position: fixed !important;
      top: auto !important;
      left: 0 !important;
      right: 0 !important;
      bottom: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
      max-height: 82dvh !important;
      border-radius: 20px 20px 0 0 !important;
      border-bottom: none !important;
      border-left: none !important;
      border-right: none !important;
      z-index: 1001 !important;
      padding: 12px 18px 24px 18px !important;
      box-shadow: 0 -12px 40px rgba(0, 0, 0, 0.85) !important;
      animation: sheetSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
  }

  @keyframes sheetSlideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
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
    --progress-size: 4px;
  }

  /* Reading Progress Bar (Top / Bottom / Left / Right) */
  #reader-progress-bar {
    position: fixed;
    z-index: 99999;
    background: linear-gradient(90deg, #ff5e36, #ff2a6d);
    transition: width 0.15s ease-out, height 0.15s ease-out;
  }
  #reader-progress-bar.pos-top {
    top: 0; left: 0; right: auto; bottom: auto;
    height: var(--progress-size, 4px);
    width: 0%;
  }
  #reader-progress-bar.pos-bottom {
    top: auto; left: 0; right: auto; bottom: 0;
    height: var(--progress-size, 4px);
    width: 0%;
  }
  #reader-progress-bar.pos-left {
    top: 0; left: 0; right: auto; bottom: auto;
    width: var(--progress-size, 4px);
    height: 0%;
    background: linear-gradient(180deg, #ff5e36, #ff2a6d);
  }
  #reader-progress-bar.pos-right {
    top: 0; left: auto; right: 0; bottom: auto;
    width: var(--progress-size, 4px);
    height: 0%;
    background: linear-gradient(180deg, #ff5e36, #ff2a6d);
  }
  #reader-progress-bar.is-hidden {
    display: none !important;
  }

  /* Header visibility */
  body.reader-header-hidden .site-header,
  body.reader-header-hidden .reader-toolbar,
  body.reader-header-hidden #reader-hint-bar {
    display: none !important;
  }

  /* Reader Background Colors */
  body.reader-bg-white {
    background: #ffffff !important;
    color: #111827 !important;
  }
  body.reader-bg-white .reader-page-wrapper {
    background: #ffffff !important;
    color: #111827 !important;
  }
  body.reader-bg-white #reader-container {
    background: #ffffff !important;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1) !important;
  }
  body.reader-bg-white .comic-page-wrapper {
    background: #f9fafb !important;
  }
  body.reader-bg-black {
    background: #000000 !important;
    color: #e0e0e0 !important;
  }
  body.reader-bg-black .reader-page-wrapper {
    background: #000000 !important;
  }
  body.reader-bg-black #reader-container {
    background: #000000 !important;
  }
  body.reader-bg-black .comic-page-wrapper {
    background: #000000 !important;
  }

  /* Image Filters & Stretch */
  body.reader-grayscale img.comic-page-img {
    filter: grayscale(100%) !important;
  }
  body.reader-dim img.comic-page-img {
    opacity: 0.65 !important;
  }
  body.reader-stretch-small img.comic-page-img {
    min-width: 100% !important;
    width: 100% !important;
  }

  /* Image Max-height limits */
  .comic-page-img.max-h-70vh {
    max-height: 70vh !important;
    width: auto !important;
    object-fit: contain !important;
  }
  .comic-page-img.max-h-85vh {
    max-height: 85vh !important;
    width: auto !important;
    object-fit: contain !important;
  }
  .comic-page-img.max-h-100vh {
    max-height: 100vh !important;
    width: auto !important;
    object-fit: contain !important;
  }

  /* Cursor hints */
  body.reader-cursor-overlay #reader-container {
    position: relative;
  }
  body.reader-cursor-pointer #reader-container {
    cursor: pointer;
  }

  /* ── FULL / ADVANCED SETTINGS MODAL & TABS ── */
  .reader-advanced-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 1050;
    display: none;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
  }
  .reader-advanced-backdrop.is-open {
    display: block;
    opacity: 1;
    pointer-events: auto;
  }

  .reader-advanced-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.96);
    width: 92%;
    max-width: 680px;
    max-height: 88vh;
    background: #11141d;
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 16px;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.9);
    z-index: 1060;
    display: none;
    flex-direction: column;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .reader-advanced-modal.is-open {
    display: flex;
    opacity: 1;
    pointer-events: auto;
    transform: translate(-50%, -50%) scale(1);
  }

  .reader-advanced-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.02);
  }
  .reader-advanced-close-btn {
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: #fff;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s ease;
  }
  .reader-advanced-close-btn:hover {
    background: rgba(239, 68, 68, 0.8);
  }

  .reader-tabs-nav {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 4px;
    padding: 10px 16px;
    margin: 0;
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }
  .reader-tab-btn {
    background: transparent;
    border: none;
    color: var(--text-muted);
    font-size: 11.5px;
    font-weight: 700;
    padding: 8px 4px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    white-space: nowrap;
  }
  .reader-tab-btn:hover:not(.active) {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
  }
  .reader-tab-btn.active {
    background: var(--primary);
    color: #fff;
    box-shadow: 0 2px 8px rgba(255, 94, 54, 0.35);
  }

  .reader-advanced-body {
    padding: 20px;
    overflow-y: auto;
    overscroll-behavior: contain;
    flex: 1;
  }
  .reader-advanced-body::-webkit-scrollbar {
    width: 6px;
  }
  .reader-advanced-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
  }

  .reader-advanced-modal .reader-tab-pane {
    display: none !important;
    padding-bottom: 0;
    margin-bottom: 0;
    border-bottom: none;
  }
  .reader-advanced-modal .reader-tab-pane.active {
    display: block !important;
    animation: fadeIn 0.2s ease;
  }

  @media (max-width: 768px) {
    .reader-advanced-modal {
      top: 0 !important;
      left: 0 !important;
      transform: none !important;
      width: 100% !important;
      height: 100% !important;
      max-height: 100vh !important;
      border-radius: 0 !important;
      border: none !important;
    }
  }

  .setting-section {
    margin-bottom: 12px;
  }
  .reader-settings-panel .setting-section {
    margin-bottom: 5px;
  }
  .reader-settings-panel .setting-label {
    margin-bottom: 3px;
  }
  .setting-label {
    display: block;
    font-size: 11px;
    color: var(--text-muted);
    margin-bottom: 5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }
  .setting-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    margin-bottom: 6px;
    cursor: pointer;
    font-size: 11.5px;
    color: #e0e0e0;
    transition: background 0.15s;
  }
  .setting-toggle-row:hover {
    background: rgba(255,255,255,0.06);
    color: #fff;
  }
  .setting-toggle-row input[type="checkbox"] {
    accent-color: var(--primary);
    width: 15px;
    height: 15px;
    cursor: pointer;
    margin: 0;
  }

  /* Keybinds Table */
  .keybind-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 6px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    font-size: 11.5px;
    gap: 6px;
  }
  .keybind-row:last-child {
    border-bottom: none;
  }
  .keybind-action-name {
    color: #e0e0e0;
    flex: 1;
    min-width: 0;
    font-weight: 500;
  }
  .keybind-keys-group {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
    justify-content: flex-end;
  }
  .keybind-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.18);
    color: #fff;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 10.5px;
    font-weight: 700;
  }
  .keybind-del-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 11px;
    line-height: 1;
    padding: 0 2px;
  }
  .keybind-del-btn:hover {
    color: #ff4d4f;
  }
  .keybind-add-btn {
    background: rgba(255,255,255,0.06);
    border: 1px dashed rgba(255,255,255,0.22);
    color: var(--primary);
    border-radius: 4px;
    padding: 2px 7px;
    font-size: 10.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
  }
  .keybind-add-btn:hover {
    background: rgba(255,94,54,0.15);
    border-color: var(--primary);
  }
  .keybind-add-btn.listening {
    background: var(--primary);
    color: #fff;
    border-style: solid;
    border-color: var(--primary);
    animation: pulse 1s infinite;
  }
  .keybind-reset-row-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 11px;
    padding: 2px;
  }
  .keybind-reset-row-btn:hover {
    color: #fff;
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
    min-height: 80vh;
    gap: 0 !important;
  }
  .reader-layout-single .comic-page-wrapper {
    display: none !important;
    justify-content: center;
    align-items: center;
    width: 100%;
    min-height: auto;
  }
  .reader-layout-single .comic-page-wrapper.active-page {
    display: flex !important;
  }

  /* Double Page (Manga Spread) Mode */
  .reader-layout-double #reader-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    gap: var(--page-spacing, 0px);
    flex-direction: row;
    max-width: 1200px !important;
  }
  .reader-layout-double.reader-dir-rtl #reader-container {
    flex-direction: row-reverse;
  }
  .reader-layout-double .comic-page-wrapper {
    display: none !important;
    width: calc(50% - (var(--page-spacing, 0px) / 2)) !important;
    flex: 1 1 calc(50% - (var(--page-spacing, 0px) / 2));
    max-width: 50%;
  }
  .reader-layout-double .comic-page-wrapper.active-page {
    display: flex !important;
    justify-content: center;
    align-items: center;
  }
  .reader-layout-double .comic-page-wrapper.single-spread {
    width: 100% !important;
    max-width: 680px !important;
    flex: 0 1 auto;
    margin: 0 auto;
  }
  @media (max-width: 767.98px) {
    .reader-layout-double #reader-container {
      flex-direction: column !important;
      gap: var(--page-spacing, 0px) !important;
    }
    .reader-layout-double .comic-page-wrapper {
      width: 100% !important;
      max-width: 100% !important;
      flex: 1 1 100% !important;
    }
  }

  /* Fit Height Mode */
  .reader-fit-height img.comic-page-img {
    max-height: calc(100vh - 120px) !important;
    width: auto !important;
    object-fit: contain !important;
  }
  .reader-fit-height .comic-page-wrapper {
    display: flex !important;
    justify-content: center;
    align-items: center;
    aspect-ratio: auto !important;
    height: auto !important;
    max-height: calc(100vh - 120px) !important;
    width: auto !important;
  }
  .reader-fit-height #reader-container {
    max-width: 100% !important;
    width: auto !important;
  }

  /* Fit Width Mode */
  .reader-fit-width #reader-container {
    max-width: 100% !important;
    width: 100% !important;
  }
  .reader-fit-width .comic-page-wrapper {
    width: 100% !important;
    max-width: 100% !important;
  }
  .reader-fit-width img.comic-page-img {
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    max-height: none !important;
  }

  /* Night Mode / Dimming Filter */
  body.reader-night-mode {
    background: #000 !important;
  }
  body.reader-night-mode .reader-page-wrapper {
    background: #000 !important;
  }
  body.reader-night-mode #reader-container {
    background: #000 !important;
  }
  body.reader-night-mode img.comic-page-img {
    filter: contrast(0.95) !important;
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
  body.ui-hidden .site-header,
  body.ui-hidden .reader-toolbar,
  body.ui-hidden #reader-hint-bar,
  body.ui-hidden .reader-footer,
  body.ui-hidden .reader-bottom-dock,
  body.ui-hidden #single-page-nav,
  body.ui-hidden .reader-settings-panel,
  body.ui-hidden .reader-settings-track,
  body.ui-hidden .reader-settings-backdrop {
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
  body.ui-hidden .reader-settings-panel {
    transform: scale(0.95);
  }
</style>
<link rel="stylesheet" href="{{ asset('css/reader.css') }}">
@endpush

@section('content')
<!-- ── TOP READING PROGRESS BAR ── -->
<div id="reader-progress-bar" class="pos-top"></div>

<!-- ── FLOATING CONTROLS FOR EXTRAS ── -->
<button type="button" id="floating-menu-btn" class="reader-controls-btn" onclick="toggleSettingsPanel(true)" style="display:none; position:fixed; top:16px; left:16px; z-index:9998; background:rgba(19,22,30,0.92); border:1px solid rgba(255,255,255,0.2); box-shadow:0 6px 20px rgba(0,0,0,0.7); padding:8px 14px; border-radius:20px; font-weight:700;">⚙️ Cài đặt</button>
<div id="reader-floating-page-number" style="display:none; position:fixed; bottom:24px; right:24px; background:rgba(19,22,30,0.92); color:#fff; font-size:12px; font-weight:700; padding:6px 14px; border-radius:20px; border:1px solid rgba(255,255,255,0.2); z-index:998; box-shadow:0 6px 20px rgba(0,0,0,0.7); pointer-events:none;"><span id="floating-page-counter-text">1 / 1</span></div>

<div class="reader-page-wrapper">

  <!-- ── 1. READER STICKY TOP TOOLBAR ── -->
  <div class="reader-toolbar" id="reader-top-bar">
    <div class="comic-info" style="display: flex; align-items: center; gap: 12px;">
      <a href="{{ route('comics.show', $comic->slug) }}" style="color: var(--primary); text-decoration: none; font-weight: 800; font-size: 15px;">
        ← {{ $comic->title }}
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
        <button class="reader-controls-btn" id="btn-prev-chap" disabled aria-label="Chương trước" style="opacity:0.4; cursor:not-allowed">← Chap trước</button>
      @endif

      {{-- Select Chapter Dropdown --}}
      <button type="button" class="reader-chapter-select" data-open-chapter-picker
              aria-haspopup="dialog" aria-controls="reader-chapter-picker" aria-expanded="false"
              title="Ch.{{ $chapter->chapter_number }} — {{ $chapter->title }}">
        Ch.{{ $chapter->chapter_number }} — {{ $chapter->title }} ▾
      </button>

      {{-- Next Chapter --}}
      @if($nextChapter)
        <a href="{{ route('chapters.show', [$comic->slug, $nextChapter->slug]) }}" class="reader-controls-btn" id="btn-next-chap" title="Chương sau (Phím →)">
          Chap sau →
        </a>
      @else
        <button class="reader-controls-btn" id="btn-next-chap" disabled aria-label="Chương sau" style="opacity:0.4; cursor:not-allowed">Chap sau →</button>
      @endif

      {{-- Settings Button --}}
      <button type="button" class="reader-controls-btn" id="btn-open-settings" onclick="toggleSettingsPanel()" title="Cài đặt chế độ đọc & phím tắt" style="background: rgba(255,255,255,0.12);">
        ⚙️ Cài đặt
      </button>

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

  <!-- ── 2. CHAPTER IMAGE READER CONTAINER & STICKY SETTINGS SIDEBAR ── -->
  <div class="reader-main-wrapper" id="reader-main-wrapper">
  <div id="reader-container" style="
    max-width: 800px;
    margin: 0 auto;
    transition: max-width 0.25s ease;
    min-height: 600px;
    background: #000;
    box-shadow: 0 10px 40px rgba(0,0,0,0.8);
  ">
    @php
      $pagesWithDim = $chapter->reader_pages;
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
          <picture>
          @if(count($page['variants']))
            {{-- Cap small-screen downloads at the smallest reader variant even on high-DPR phones. --}}
            <source media="(max-width: 480px)" type="image/webp" sizes="100vw"
                    {{ $index < 2 ? 'srcset' : 'data-srcset' }}="{{ $page['variants'][0]['url'] }} {{ $page['variants'][0]['width'] }}w">
          @endif
          <img
            {{ $index < 2 ? 'src' : 'data-src' }}="{{ $page['variants'][0]['url'] ?? $page['url'] }}"
            @if($page['srcset'])
            {{ $index < 2 ? 'srcset' : 'data-srcset' }}="{{ $page['srcset'] }}"
            sizes="(max-width: 800px) 100vw, 800px"
            @endif
            width="{{ $page['width'] }}"
            height="{{ $page['height'] }}"
            alt="{{ $comic->title }} - Chapter {{ $chapter->chapter_number }} - Trang {{ $index + 1 }}"
            loading="{{ $index < 2 ? 'eager' : 'lazy' }}"
            fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
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
            onerror="if(window.handleImageError) { handleImageError(this); } else { this.dataset.earlyError = '1'; }"
          />
          </picture>
          @if($index >= 2)
            <noscript><img src="{{ $page['url'] }}" loading="lazy" width="{{ $page['width'] }}" height="{{ $page['height'] }}" alt="Trang {{ $index + 1 }}" style="width:100%;height:auto"></noscript>
          @endif
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

  <!-- ── 1. STICKY / FLOATING QUICK SETTINGS PANEL ── -->
  <aside class="reader-settings-track" id="reader-settings-track">
    <div class="reader-settings-panel" id="reader-settings-panel" role="dialog" aria-modal="false" aria-label="Tùy chỉnh chế độ đọc">
      <div class="sheet-grab-handle"></div>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 6px;">
        <strong style="color: #fff; font-size: 13px; display: flex; align-items: center; gap: 6px;">
          <span>⚙️</span>
          <span>Cài Đặt Trình Đọc <span style="font-size:11px;opacity:0.75;font-weight:normal;">(Tùy Chỉnh Chế Độ Đọc)</span></span>
        </strong>
        <button type="button" class="reader-settings-close-btn" onclick="toggleSettingsPanel(false)" aria-label="Đóng cài đặt" title="Đóng cài đặt (Phím Esc)">✕</button>
      </div>

      <!-- PRESET STATUS BAR (⭐ Khuyến nghị / ⚙ Tùy chỉnh) -->
      <div id="reader-preset-bar" style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 4px 8px; margin-bottom: 7px;">
        <div id="reader-preset-badge" style="font-size: 11.5px; font-weight: 700; display: flex; align-items: center; gap: 4px;">
          <span id="preset-badge-icon" style="color: #fbbf24; font-size: 12px;">⭐</span>
          <span id="preset-badge-text" style="color: #f3f4f6;">Chế độ: Khuyến nghị</span>
        </div>
        <button type="button" id="btn-restore-recommended-quick" onclick="applyRecommendedPreset()" style="display: none; background: rgba(255,94,54,0.12); border: 1px solid rgba(255,94,54,0.3); border-radius: 6px; color: var(--primary); font-size: 10.5px; font-weight: 600; padding: 2px 7px; cursor: pointer; transition: all 0.15s ease;" title="Quay lại cài đặt khuyến nghị">
          ↺ Khôi phục khuyến nghị
        </button>
      </div>

      <!-- A. CHẾ ĐỘ ĐỌC (3 MODE) -->
      <div class="setting-section" id="section-reading-mode">
        <label class="setting-label">CHẾ ĐỘ ĐỌC (PHÍM M)</label>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4px;">
          <button type="button" class="setting-btn active" id="btn-mode-vertical" onclick="setReadingLayout('vertical')" aria-pressed="true">📜 Cuộn dọc</button>
          <button type="button" class="setting-btn" id="btn-mode-single" onclick="setReadingLayout('single')" aria-pressed="false" title="Từng trang">📄 Trang đơn <span style="font-size: 10px; opacity: 0.7;">(Từng trang)</span></button>
          <button type="button" class="setting-btn" id="btn-mode-double" onclick="setReadingLayout('double')" aria-pressed="false">📖 Trang đôi</button>
        </div>
      </div>

      <!-- B. HƯỚNG ĐỌC (Chỉ hiển thị khi Trang đơn hoặc Trang đôi, ẩn khi Cuộn dọc) -->
      <div class="setting-section" style="display: none;" id="section-reading-direction">
        <label class="setting-label">HƯỚNG ĐỌC</label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
          <button type="button" class="setting-btn active" id="btn-dir-ltr" onclick="setReadingDirection('ltr')" aria-pressed="true">➡️ Trái → Phải</button>
          <button type="button" class="setting-btn" id="btn-dir-rtl" onclick="setReadingDirection('rtl')" aria-pressed="false" title="Phải qua Trái">⬅️ Phải → Trái (Manga)<span style="display:none">Phải qua Trái</span></button>
        </div>
      </div>

      <!-- C. HIỂN THỊ ẢNH -->
      <div class="setting-section" id="section-quick-fit">
        <label class="setting-label">HIỂN THỊ ẢNH</label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
          <button type="button" class="setting-btn" id="btn-fit-width" onclick="setFitMode('fit-width')" aria-pressed="false">↔️ Vừa chiều rộng</button>
          <button type="button" class="setting-btn" id="btn-fit-height" onclick="setFitMode('fit-height')" aria-pressed="false">↕️ Vừa chiều cao</button>
        </div>
      </div>

      <!-- D. KHOẢNG CÁCH ẢNH (Ẩn khi Trang đơn, Hiện khi Cuộn dọc hoặc Trang đôi) -->
      <div class="setting-section" id="section-page-spacing">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
          <label class="setting-label" id="label-page-spacing" style="margin-bottom: 0;">KHOẢNG CÁCH GIỮA ẢNH</label>
          <span id="label-margin-val" style="font-size: 11px; color: var(--primary); font-weight: 700;">0px</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px;">
          <button type="button" class="setting-btn active" id="btn-space-0" onclick="setPageSpacing(0)" aria-pressed="true">0px</button>
          <button type="button" class="setting-btn" id="btn-space-8" onclick="setPageSpacing(8)" aria-pressed="false">8px</button>
          <button type="button" class="setting-btn" id="btn-space-16" onclick="setPageSpacing(16)" aria-pressed="false">16px</button>
        </div>
      </div>

      <!-- E & F. ĐỘ SÁNG & GIẢM CHÓI -->
      <div class="setting-section" id="section-quick-brightness">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
          <label class="setting-label" style="margin-bottom: 0;">🌙 ĐỘ SÁNG & GIẢM CHÓI</label>
          <span id="brightness-val" style="font-size: 11px; color: var(--primary); font-weight: 700;">Độ sáng: 100%</span>
        </div>
        <div style="margin-bottom: 6px;">
          <button type="button" class="setting-btn" id="btn-toggle-night" onclick="toggleNightMode()" style="width: 100%; padding: 6px 10px;" aria-pressed="false">
            🌙 Giảm chói mắt
          </button>
        </div>
        <input type="range" id="brightness-slider" min="30" max="100" value="100" oninput="setBrightness(this.value)" style="width: 100%; accent-color: var(--primary); cursor: pointer;" aria-label="Độ sáng ảnh truyện">
      </div>

      <!-- G. NÚT MỞ FULL / ADVANCED SETTINGS -->
      <div class="setting-section" style="margin-top: 10px; margin-bottom: 0; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.08);">
        <button type="button" id="btn-open-advanced-modal" onclick="openAdvancedModal()" style="
          width: 100%;
          padding: 10px 14px;
          background: linear-gradient(135deg, rgba(255,94,54,0.18), rgba(255,42,109,0.18));
          border: 1px solid rgba(255,94,54,0.5);
          border-radius: 8px;
          color: #fff;
          font-size: 12.5px;
          font-weight: 700;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 6px;
          transition: all 0.2s ease;
          box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        " onmouseover="this.style.background='var(--primary)';this.style.borderColor='var(--primary)'" onmouseout="this.style.background='linear-gradient(135deg, rgba(255,94,54,0.18), rgba(255,42,109,0.18))';this.style.borderColor='rgba(255,94,54,0.5)'">
          <span>⚙️</span>
          <span>Cài đặt nâng cao</span>
        </button>
      </div>
    </div>
  </aside>
  </div>

  <!-- ── 2. FULL / ADVANCED SETTINGS MODAL ── -->
  <div class="reader-advanced-backdrop" id="reader-advanced-backdrop" onclick="closeAdvancedModal()"></div>
  <div class="reader-advanced-modal" id="reader-advanced-modal" role="dialog" aria-modal="true" aria-label="Cài đặt trình đọc nâng cao">
    <div class="reader-advanced-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">⚙️</span>
        <strong style="color: #fff; font-size: 15px; font-weight: 700;">Cài Đặt Trình Đọc Nâng Cao</strong>
      </div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" id="btn-restore-recommended-modal" onclick="applyRecommendedPreset()" style="background: rgba(255, 94, 54, 0.12); border: 1px solid rgba(255, 94, 54, 0.35); border-radius: 6px; color: var(--primary); font-size: 11.5px; font-weight: 600; padding: 5px 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s ease;" title="Quay lại cài đặt khuyến nghị">
          <span>↺</span>
          <span>Dùng cài đặt khuyến nghị</span>
        </button>
        <button type="button" class="reader-advanced-close-btn" id="btn-close-advanced-modal" onclick="closeAdvancedModal()" aria-label="Đóng cài đặt nâng cao" title="Đóng cài đặt nâng cao (Phím Esc)">✕</button>
      </div>
    </div>

    <!-- 5 TABS NAVIGATION -->
    <div class="reader-tabs-nav reader-advanced-tabs" role="tablist">
      <button type="button" class="reader-tab-btn active" id="tab-btn-layout" role="tab" aria-selected="true" onclick="switchReaderTab('layout')">Bố cục</button>
      <button type="button" class="reader-tab-btn" id="tab-btn-image" role="tab" aria-selected="false" onclick="switchReaderTab('image')">Hiển thị ảnh</button>
      <button type="button" class="reader-tab-btn" id="tab-btn-keybinds" role="tab" aria-selected="false" onclick="switchReaderTab('keybinds')">Phím tắt</button>
      <button type="button" class="reader-tab-btn" id="tab-btn-behaviors" role="tab" aria-selected="false" onclick="switchReaderTab('behaviors')">Hành vi</button>
      <button type="button" class="reader-tab-btn" id="tab-btn-other" role="tab" aria-selected="false" onclick="switchReaderTab('other')">Khác</button>
    </div>

    <div class="reader-advanced-body">
      <!-- ── TAB 1: BỐ CỤC ── -->
      <div class="reader-tab-pane active" id="tab-pane-layout" role="tabpanel">
        <!-- Chế độ đọc -->
        <div class="setting-section">
          <label class="setting-label">KIỂU HIỂN THỊ TRANG</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn active" id="adv-btn-mode-vertical" onclick="setReadingLayout('vertical')" aria-pressed="true">📜 Cuộn dọc</button>
            <button type="button" class="setting-btn" id="adv-btn-mode-single" onclick="setReadingLayout('single')" aria-pressed="false">📄 Trang đơn</button>
            <button type="button" class="setting-btn" id="adv-btn-mode-double" onclick="setReadingLayout('double')" aria-pressed="false">📖 Trang đôi</button>
          </div>
        </div>

        <!-- Hướng đọc (Context-aware: ẩn khi cuộn dọc) -->
        <div class="setting-section" id="adv-section-reading-direction" style="display: none;">
          <label class="setting-label">HƯỚNG ĐỌC</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn active" id="adv-btn-dir-ltr" onclick="setReadingDirection('ltr')" aria-pressed="true">➡️ Trái sang phải</button>
            <button type="button" class="setting-btn" id="adv-btn-dir-rtl" onclick="setReadingDirection('rtl')" aria-pressed="false">⬅️ Phải sang trái (Manga)</button>
          </div>
        </div>

        <!-- Hiển thị đầu trang -->
        <div class="setting-section">
          <label class="setting-label">HIỂN THỊ ĐẦU TRANG</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn active" id="btn-header-show" onclick="setHeaderVisibility('show')" aria-pressed="true">Hiện đầu trang</button>
            <button type="button" class="setting-btn" id="btn-header-hide" onclick="setHeaderVisibility('hide')" aria-pressed="false">Ẩn đầu trang</button>
          </div>
        </div>

        <!-- Kiểu thanh tiến trình -->
        <div class="setting-section">
          <label class="setting-label">KIỂU THANH TIẾN TRÌNH</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn" id="btn-progress-hidden" onclick="setProgressBarStyle('hidden')" aria-pressed="false">Ẩn thanh</button>
            <button type="button" class="setting-btn" id="btn-progress-light" onclick="setProgressBarStyle('light')" aria-pressed="false">Thanh mảnh</button>
            <button type="button" class="setting-btn active" id="btn-progress-normal" onclick="setProgressBarStyle('normal')" aria-pressed="true">Bình thường</button>
          </div>
        </div>

        <!-- Vị trí thanh tiến trình -->
        <div class="setting-section">
          <label class="setting-label">VỊ TRÍ THANH TIẾN TRÌNH</label>
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
            <button type="button" class="setting-btn active" id="btn-progress-pos-top" onclick="setProgressBarPosition('top')" aria-pressed="true">Trên</button>
            <button type="button" class="setting-btn" id="btn-progress-pos-bottom" onclick="setProgressBarPosition('bottom')" aria-pressed="false">Dưới</button>
            <button type="button" class="setting-btn" id="btn-progress-pos-left" onclick="setProgressBarPosition('left')" aria-pressed="false">Trái</button>
            <button type="button" class="setting-btn" id="btn-progress-pos-right" onclick="setProgressBarPosition('right')" aria-pressed="false">Phải</button>
          </div>
        </div>

        <!-- Kích thước thanh tiến trình -->
        <div class="setting-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <label class="setting-label" style="margin-bottom: 0;">KÍCH THƯỚC THANH TIẾN TRÌNH</label>
            <span id="progress-size-val" style="font-size: 11px; color: var(--primary); font-weight: 700;">4px</span>
          </div>
          <input type="range" id="progress-size-slider" min="1" max="16" value="4" oninput="setProgressBarSize(this.value)" style="width: 100%; accent-color: var(--primary); cursor: pointer;" aria-label="Kích thước pixel thanh tiến trình">
        </div>

        <!-- Màu nền Reader -->
        <div class="setting-section">
          <label class="setting-label">MÀU NỀN TRÌNH ĐỌC</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn active" id="btn-bg-theme" onclick="setReaderBackground('theme')" aria-pressed="true">Theo giao diện</button>
            <button type="button" class="setting-btn" id="btn-bg-white" onclick="setReaderBackground('white')" aria-pressed="false">Trắng</button>
            <button type="button" class="setting-btn" id="btn-bg-black" onclick="setReaderBackground('black')" aria-pressed="false">Đen</button>
          </div>
        </div>
      </div>

      <!-- ── TAB 2: HIỂN THỊ ẢNH ── -->
      <div class="reader-tab-pane" id="tab-pane-image" role="tabpanel">
        <!-- Căn chỉnh khung ảnh -->
        <div class="setting-section">
          <label class="setting-label">CĂN CHỈNH KHUNG ẢNH</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn" id="adv-btn-fit-width" onclick="setFitMode('fit-width')" aria-pressed="false">↔️ Vừa chiều rộng</button>
            <button type="button" class="setting-btn" id="adv-btn-fit-height" onclick="setFitMode('fit-height')" aria-pressed="false">↕️ Vừa chiều cao</button>
          </div>
        </div>

        <!-- Kéo giãn ảnh nhỏ -->
        <div class="setting-section">
          <label class="setting-label">KÉO GIÃN ẢNH NHỎ</label>
          <button type="button" class="setting-btn" id="btn-stretch-small" onclick="toggleStretchSmall()" style="width: 100%; text-align: left; padding: 8px 12px;" aria-pressed="false">
            🔍 Phóng to ảnh nhỏ để vừa khung
          </button>
        </div>

        <!-- Giới hạn chiều rộng tối đa -->
        <div class="setting-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <label class="setting-label" style="margin-bottom: 0;">GIỚI HẠN CHIỀU RỘNG TỐI ĐA</label>
            <span id="max-width-val" style="font-size: 11px; color: var(--primary); font-weight: 700;">800px</span>
          </div>
          <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px;">
            <button type="button" class="setting-btn" id="btn-w-680" onclick="setReaderWidth(680)" aria-pressed="false">680px</button>
            <button type="button" class="setting-btn active" id="btn-w-800" onclick="setReaderWidth(800)" aria-pressed="true">800px</button>
            <button type="button" class="setting-btn" id="btn-w-1000" onclick="setReaderWidth(1000)" aria-pressed="false">1000px</button>
            <button type="button" class="setting-btn" id="btn-w-full" onclick="setReaderWidth('100%')" aria-pressed="false">100%</button>
            <button type="button" class="setting-btn" id="btn-w-none" onclick="setReaderWidth('none')" aria-pressed="false">Không</button>
          </div>
        </div>

        <!-- Giới hạn chiều cao tối đa -->
        <div class="setting-section">
          <label class="setting-label">GIỚI HẠN CHIỀU CAO TỐI ĐA</label>
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
            <button type="button" class="setting-btn active" id="btn-h-none" onclick="setMaxHeightLimit('none')" aria-pressed="true">Không</button>
            <button type="button" class="setting-btn" id="btn-h-70vh" onclick="setMaxHeightLimit('70vh')" aria-pressed="false">70vh</button>
            <button type="button" class="setting-btn" id="btn-h-85vh" onclick="setMaxHeightLimit('85vh')" aria-pressed="false">85vh</button>
            <button type="button" class="setting-btn" id="btn-h-100vh" onclick="setMaxHeightLimit('100vh')" aria-pressed="false">100vh</button>
          </div>
        </div>

        <!-- Bộ lọc ảnh: Thang xám & Làm mờ -->
        <div class="setting-section">
          <label class="setting-label">BỘ LỌC HÌNH ẢNH</label>
          <label class="setting-toggle-row">
            <span>Hiển thị trang ở chế độ thang xám</span>
            <input type="checkbox" id="toggle-extra-grayscale" onchange="setReaderExtra('grayscale', this.checked)">
          </label>
          <label class="setting-toggle-row">
            <span>Làm mờ trang</span>
            <input type="checkbox" id="toggle-extra-dim" onchange="setReaderExtra('dim', this.checked)">
          </label>
        </div>

        <!-- Độ sáng trong modal -->
        <div class="setting-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <label class="setting-label" style="margin-bottom: 0;">ĐỘ SÁNG TRÌNH ĐỌC</label>
            <span id="adv-brightness-val" style="font-size: 11px; color: var(--primary); font-weight: 700;">100%</span>
          </div>
          <input type="range" id="adv-brightness-slider" min="30" max="100" value="100" oninput="setBrightness(this.value)" style="width: 100%; accent-color: var(--primary); cursor: pointer;" aria-label="Độ sáng ảnh truyện">
        </div>
      </div>

      <!-- ── TAB 3: PHÍM TẮT ── -->
      <div class="reader-tab-pane" id="tab-pane-keybinds" role="tabpanel">
        <div class="setting-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <label class="setting-label" style="margin-bottom: 0;">QUẢN LÝ PHÍM TẮT (10 HÀNH ĐỘNG)</label>
            <button type="button" id="btn-reset-keybinds" onclick="resetAllKeybinds()" class="keybind-reset-row-btn" title="Đặt lại toàn bộ phím tắt mặc định" style="color: var(--primary); font-size: 11px; font-weight: 700; cursor: pointer; background: none; border: none;">
              ↺ Đặt lại mặc định
            </button>
          </div>
          <div id="keybinds-list-container" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; max-height: 320px; overflow-y: auto;">
            <!-- Render bằng JS -->
          </div>
        </div>

        <div id="reader-hotkey-box" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 10px 12px; font-size: 11.5px; color: var(--text-muted); line-height: 1.6;">
          <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">⌨️ Hướng dẫn phím tắt:</div>
          <div>• Bấm nút <strong>+</strong> để lắng nghe phím gán mới, bấm <strong>✕</strong> để xóa</div>
          <div>• Nhấn <strong>Esc</strong> khi đang chờ gán để hủy bỏ</div>
        </div>
      </div>

      <!-- ── TAB 4: HÀNH VI ── -->
      <div class="reader-tab-pane" id="tab-pane-behaviors" role="tabpanel">
        <!-- Tự động sang chap -->
        <div class="setting-section">
          <label class="setting-label">TỰ ĐỘNG SANG CHAP TIẾP THEO Ở TRANG CUỐI</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn" id="btn-auto-advance-off" onclick="setAutoAdvanceChapter(false)" aria-pressed="false">Tắt</button>
            <button type="button" class="setting-btn active" id="btn-auto-advance-on" onclick="setAutoAdvanceChapter(true)" aria-pressed="true">Bật</button>
          </div>
        </div>

        <!-- Chế độ lịch sử -->
        <div class="setting-section">
          <label class="setting-label">CHẾ ĐỘ LỊCH SỬ READER</label>
          <div style="display: flex; flex-direction: column; gap: 5px;">
            <button type="button" class="setting-btn" id="btn-history-none" onclick="setHistoryMode('none')" style="text-align: left; padding: 8px 12px;" aria-pressed="false">
              • Không cập nhật URL và tiêu đề
            </button>
            <button type="button" class="setting-btn active" id="btn-history-replace" onclick="setHistoryMode('replace')" style="text-align: left; padding: 8px 12px;" aria-pressed="true">
              • Cập nhật URL và tiêu đề
            </button>
            <button type="button" class="setting-btn" id="btn-history-push" onclick="setHistoryMode('push')" style="text-align: left; padding: 8px 12px;" aria-pressed="false">
              • Cập nhật URL + hỗ trợ Back/Forward
            </button>
          </div>
        </div>

        <!-- Chuyển trang chạm -->
        <div class="setting-section">
          <label class="setting-label">CHUYỂN TRANG BẰNG CHẠM</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn active" id="btn-tap-directional" onclick="setTapTurnMode('directional')" aria-pressed="true">Hướng chạm</button>
            <button type="button" class="setting-btn" id="btn-tap-always" onclick="setTapTurnMode('always')" aria-pressed="false">Luôn tiến</button>
            <button type="button" class="setting-btn" id="btn-tap-none" onclick="setTapTurnMode('none')" aria-pressed="false">Tắt</button>
          </div>
        </div>

        <!-- Chuyển trang cuộn -->
        <div class="setting-section">
          <label class="setting-label">CHUYỂN TRANG BẰNG CUỘN</label>
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
            <button type="button" class="setting-btn" id="btn-scroll-none" onclick="setScrollTurnMode('none')" aria-pressed="false">Tắt</button>
            <button type="button" class="setting-btn" id="btn-scroll-wheel" onclick="setScrollTurnMode('wheel')" aria-pressed="false">Chuột</button>
            <button type="button" class="setting-btn" id="btn-scroll-keys" onclick="setScrollTurnMode('keys')" aria-pressed="false">Phím</button>
            <button type="button" class="setting-btn active" id="btn-scroll-both" onclick="setScrollTurnMode('both')" aria-pressed="true">Cả hai</button>
          </div>
        </div>

        <!-- Vuốt swipe trên mobile -->
        <div class="setting-section">
          <label class="setting-label">VUỐT CHẠM (SWIPE) TRÊN THIẾT BỊ DI ĐỘNG</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn" id="btn-swipe-turn-off" onclick="setSwipeTurnMode(false)" aria-pressed="false">Tắt</button>
            <button type="button" class="setting-btn active" id="btn-swipe-turn-on" onclick="setSwipeTurnMode(true)" aria-pressed="true">Bật</button>
          </div>
        </div>

        <!-- Nhấp đúp toàn màn hình -->
        <div class="setting-section">
          <label class="setting-label">NHẤP ĐÚP ĐỂ BẬT/TẮT TOÀN MÀN HÌNH</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn" id="btn-dblclick-fs-off" onclick="setDblClickFullscreen(false)" aria-pressed="false">Tắt</button>
            <button type="button" class="setting-btn active" id="btn-dblclick-fs-on" onclick="setDblClickFullscreen(true)" aria-pressed="true">Bật</button>
          </div>
        </div>

        <!-- Tự cuộn lên khi đổi chế độ ảnh -->
        <div class="setting-section">
          <label class="setting-label">TỰ CUỘN LÊN KHI ĐỔI CHẾ ĐỘ ẢNH</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn" id="btn-autoscroll-width" onclick="setAutoScrollFitMode('width')" aria-pressed="false">Chiều rộng</button>
            <button type="button" class="setting-btn" id="btn-autoscroll-height" onclick="setAutoScrollFitMode('height')" aria-pressed="false">Chiều cao</button>
            <button type="button" class="setting-btn active" id="btn-autoscroll-none" onclick="setAutoScrollFitMode('none')" aria-pressed="true">Không</button>
          </div>
        </div>

        <!-- Độ lệch tự cuộn -->
        <div class="setting-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <label class="setting-label" style="margin-bottom: 0;">ĐỘ LỆCH TỰ CUỘN (PIXEL)</label>
            <span id="scroll-offset-val" style="font-size: 11px; color: var(--primary); font-weight: 700;">0px</span>
          </div>
          <input type="number" id="input-scroll-offset" min="0" max="500" value="0" oninput="setAutoScrollOffset(this.value)" style="width: 100%; box-sizing: border-box; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 7px 10px; border-radius: 8px; font-size: 13px;" aria-label="Độ lệch pixel khi tự cuộn">
        </div>
      </div>

      <!-- ── TAB 5: KHÁC ── -->
      <div class="reader-tab-pane" id="tab-pane-other" role="tabpanel">
        <!-- Gợi ý con trỏ -->
        <div class="setting-section">
          <label class="setting-label">GỢI Ý THAO TÁC CON TRỎ</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
            <button type="button" class="setting-btn active" id="btn-cursor-none" onclick="setCursorHints('none')" aria-pressed="true">Không</button>
            <button type="button" class="setting-btn" id="btn-cursor-overlay" onclick="setCursorHints('overlay')" aria-pressed="false">Lớp phủ</button>
            <button type="button" class="setting-btn" id="btn-cursor-pointer" onclick="setCursorHints('pointer')" aria-pressed="false">Con trỏ</button>
          </div>
        </div>

        <!-- Tùy chọn bổ sung -->
        <div class="setting-section">
          <label class="setting-label">TÙY CHỌN BỔ SUNG (READER EXTRAS)</label>
          <label class="setting-toggle-row">
            <span>Hiện nút menu khi menu đang ghim và đầu trang bị ẩn</span>
            <input type="checkbox" id="toggle-extra-menu-btn" onchange="setReaderExtra('menuBtn', this.checked)">
          </label>
          <label class="setting-toggle-row">
            <span>Hiện số trang khi thanh tiến trình bị ẩn</span>
            <input type="checkbox" id="toggle-extra-page-num" onchange="setReaderExtra('pageNum', this.checked)">
          </label>
        </div>

        <!-- Khôi phục cài đặt mặc định -->
        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; gap: 8px;">
          <button type="button" id="btn-restore-recommended-tab5" onclick="applyRecommendedPreset()" class="setting-btn" style="width: 100%; padding: 10px; font-weight: 700; color: #38bdf8; background: rgba(56,189,248,0.08); border-color: rgba(56,189,248,0.3);">
            ↺ Dùng cài đặt khuyến nghị
          </button>
          <button type="button" id="btn-reset-all-settings" onclick="resetAllSettingsToDefault()" class="setting-btn" style="width: 100%; padding: 10px; font-weight: 700; color: #ff8f70; background: rgba(255,94,54,0.08); border-color: rgba(255,94,54,0.25);">
            🔄 Khôi phục toàn bộ cài đặt mặc định
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="reader-settings-backdrop" id="reader-settings-backdrop" onclick="toggleSettingsPanel(false)"></div>

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
      <button type="button" id="dock-btn-page-prev" onclick="handleNavPrev()" class="reader-controls-btn" style="padding: 5px 10px; border-radius: 20px;" aria-label="Trang trước" title="Trang trước">◀</button>
      <span id="dock-page-counter" style="font-size: 12px; font-weight: 700; color: #fff; min-width: 50px; text-align: center;">1 / 1</span>
      <button type="button" id="dock-btn-page-next" onclick="handleNavNext()" class="reader-controls-btn" style="padding: 5px 10px; border-radius: 20px;" aria-label="Trang sau" title="Trang sau">▶</button>
    </div>

    <button type="button" class="reader-chapter-select" data-open-chapter-picker
            aria-haspopup="dialog" aria-controls="reader-chapter-picker" aria-expanded="false">
      Ch.{{ $chapter->chapter_number }} ▾
    </button>

    @if($nextChapter)
      <a href="{{ route('chapters.show', [$comic->slug, $nextChapter->slug]) }}" class="reader-controls-btn" id="dock-btn-next-chap" style="padding: 6px 12px; border-radius: 20px;" title="Chương sau">
        Chap sau ▶
      </a>
    @endif

    <button type="button" onclick="toggleSettingsPanel()" class="reader-controls-btn" style="padding: 6px 10px; border-radius: 20px;" title="Cài đặt (⚙️)">⚙️</button>
    <button type="button" onclick="toggleFullscreen()" class="reader-controls-btn" style="padding: 6px 10px; border-radius: 20px;" title="Toàn màn hình (F)">⛶</button>
    <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })" class="reader-controls-btn" style="padding: 6px 10px; border-radius: 20px;" title="Lên đầu trang">⬆️</button>
  </div>

  @include('comics.partials.chapter-picker')

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
<script src="{{ asset('js/reader-images.js') }}"></script>
<script src="{{ asset('js/reader-picker.js') }}"></script>
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

  // ── READER STATE & PRESETS (⭐ KHUYẾN NGHỊ & ⚙ TÙY CHỈNH) ──
  const RECOMMENDED_PRESET_VERSION = 1;

  const recommendedReaderSettings = {
    layout: 'vertical',            // 'vertical' | 'single' | 'double'
    spacing: 0,                   // 0 | 8 | 16 | 24 (px)
    direction: 'ltr',             // 'ltr' | 'rtl'
    headerVisibility: 'shown',    // 'shown' | 'hidden'
    progressBarStyle: 'light',    // 'hidden' | 'light' | 'normal'
    progressBarPos: 'bottom',     // 'top' | 'bottom' | 'left' | 'right'
    progressBarSize: 3,           // px (1..16)
    cursorHints: 'none',          // 'none' | 'overlay' | 'cursor'
    readerExtras: {
      show_menu_btn: true,
      show_page_number: false,
      greyscale: false,
      dim: false
    },
    readerBg: 'theme',            // 'theme' | 'white' | 'black'
    fit: 'fit-width',             // 'custom' | 'fit-width' | 'fit-height'
    width: '100%',                // 680 | 800 | 1000 | '100%'
    stretchSmall: false,
    maxWidthLimit: 'none',        // '680' | '800' | '1000' | 'full' | 'none'
    maxHeightLimit: 'none',       // '70vh' | '85vh' | '100vh' | 'none'
    night: false,
    brightness: 100,              // 30..100
    autoAdvanceChapter: false,
    historyMode: 'push',          // 'none' | 'replace' | 'push'
    tapTurnMode: 'direction',     // 'direction' | 'always_forward' | 'off'
    scrollTurnMode: 'both',       // 'off' | 'wheel' | 'keyboard' | 'both'
    dblClickFullscreen: true,
    swipeTurnMode: true,
    autoScrollFitMode: 'none',    // 'width' | 'height' | 'none'
    autoScrollOffset: 0,
    presetMode: 'recommended',    // 'recommended' | 'custom'
    panelOpen: false,
    activeTab: 'layout'
  };

  const defaultReaderSettings = JSON.parse(JSON.stringify(recommendedReaderSettings));

  const defaultKeybinds = {
    toggle_menu: ['m', 'M'],
    page_right: ['ArrowRight', 'd', 'D'],
    page_left: ['ArrowLeft', 'a', 'A'],
    scroll_up: ['PageUp', 'k', 'K'],
    scroll_down: ['PageDown', 'j', 'J', ' '],
    chapter_forward: [']'],
    chapter_backward: ['['],
    toggle_fullscreen: ['f', 'F'],
    cycle_fit_mode: ['w', 'W'],
    toggle_direction: ['r', 'R']
  };

  const keybindActionLabels = {
    toggle_menu: 'Bật/tắt menu',
    page_right: 'Sang trang phải',
    page_left: 'Sang trang trái',
    scroll_up: 'Cuộn lên',
    scroll_down: 'Cuộn xuống',
    chapter_forward: 'Chap tiếp theo',
    chapter_backward: 'Chap trước',
    toggle_fullscreen: 'Bật/tắt toàn màn hình',
    cycle_fit_mode: 'Chuyển chế độ hiển thị ảnh',
    toggle_direction: 'Đổi hướng đọc / lật trang đôi'
  };

  let readerSettings = JSON.parse(JSON.stringify(defaultReaderSettings));
  let keybindSettings = JSON.parse(JSON.stringify(defaultKeybinds));
  let listeningAction = null;
  let wheelLock = false;
  let currentSinglePageIndex = 0;
  const pageWrappers = document.querySelectorAll('.comic-page-wrapper');
  const totalPagesCount = pageWrappers.length;

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
    if (progressBar) {
      if (readerSettings.progressBarStyle === 'hidden') {
        progressBar.style.display = 'none';
      } else {
        progressBar.style.display = 'block';
        if (readerSettings.progressBarPos === 'left' || readerSettings.progressBarPos === 'right') {
          progressBar.style.height = percent + '%';
          progressBar.style.width = '';
        } else {
          progressBar.style.width = percent + '%';
          progressBar.style.height = '';
        }
      }
    }

    const floatingNum = document.getElementById('reader-floating-page-number');
    const floatingText = document.getElementById('floating-page-counter-text') || floatingNum;
    if (floatingNum) {
      const showPageNum = readerSettings.readerExtras?.show_page_number || readerSettings.readerExtras?.pageNum;
      if (readerSettings.progressBarStyle === 'hidden' && showPageNum) {
        floatingNum.style.display = 'block';
        let cur = currentSinglePageIndex + 1;
        if (readerSettings.layout === 'double' && cur < totalPagesCount) {
          if (floatingText) floatingText.textContent = `${cur}-${cur + 1} / ${totalPagesCount}`;
        } else {
          if (floatingText) floatingText.textContent = `${cur} / ${totalPagesCount}`;
        }
      } else {
        floatingNum.style.display = 'none';
      }
    }

    // Auto advance ở vertical mode khi cuộn tới cuối trang
    if (readerSettings.layout === 'vertical' && readerSettings.autoAdvanceChapter && percent >= 99.8) {
      if (!window.__autoAdvanceTriggered) {
        window.__autoAdvanceTriggered = true;
        setTimeout(() => {
          advanceToNextChapter();
        }, 600);
      }
    }

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

  // ── LOAD & SAVE PERSISTENT SETTINGS ──
  function loadReaderSettings() {
    try {
      const savedMode = localStorage.getItem('reader_settings_mode');
      const savedVersion = parseInt(localStorage.getItem('reader_preset_version'), 10) || 0;
      const saved = localStorage.getItem('webcomics_reader_settings');
      const hasExistingKeys = localStorage.getItem('reader_mode') || localStorage.getItem('image_fit') || localStorage.getItem('page_gap') || localStorage.getItem('brightness');

      if (!savedMode && !saved && !hasExistingKeys) {
        // User mới tinh: Áp dụng Recommended Preset
        readerSettings = JSON.parse(JSON.stringify(recommendedReaderSettings));
        readerSettings.presetMode = 'recommended';
        localStorage.setItem('reader_settings_mode', 'recommended');
        localStorage.setItem('reader_preset_version', String(RECOMMENDED_PRESET_VERSION));
      } else if (savedMode === 'recommended') {
        // User đang ở preset Khuyến nghị
        readerSettings = JSON.parse(JSON.stringify(recommendedReaderSettings));
        readerSettings.presetMode = 'recommended';
        if (savedVersion < RECOMMENDED_PRESET_VERSION) {
          localStorage.setItem('reader_preset_version', String(RECOMMENDED_PRESET_VERSION));
        }
      } else {
        // User có cấu hình tùy chỉnh (custom)
        readerSettings = JSON.parse(JSON.stringify(recommendedReaderSettings));
        readerSettings.presetMode = 'custom';
        if (saved) {
          try {
            readerSettings = { ...readerSettings, ...JSON.parse(saved) };
            readerSettings.presetMode = 'custom';
          } catch (_) {}
        }
        // Fallback các key độc lập
        if (localStorage.getItem('reader_mode')) readerSettings.layout = localStorage.getItem('reader_mode');
        if (localStorage.getItem('reading_direction')) readerSettings.direction = localStorage.getItem('reading_direction');
        if (localStorage.getItem('header_visibility')) readerSettings.headerVisibility = localStorage.getItem('header_visibility');
        if (localStorage.getItem('progress_bar_style')) readerSettings.progressBarStyle = localStorage.getItem('progress_bar_style');
        if (localStorage.getItem('progress_bar_position')) readerSettings.progressBarPos = localStorage.getItem('progress_bar_position');
        if (localStorage.getItem('progress_bar_size')) readerSettings.progressBarSize = parseInt(localStorage.getItem('progress_bar_size'), 10) || 3;
        if (localStorage.getItem('cursor_hints')) readerSettings.cursorHints = localStorage.getItem('cursor_hints');
        if (localStorage.getItem('reader_extras')) {
          try { readerSettings.readerExtras = { ...readerSettings.readerExtras, ...JSON.parse(localStorage.getItem('reader_extras')) }; } catch (_) {}
        }
        if (localStorage.getItem('reader_background')) readerSettings.readerBg = localStorage.getItem('reader_background');
        if (localStorage.getItem('image_fit')) readerSettings.fit = localStorage.getItem('image_fit');
        if (localStorage.getItem('image_fit_width') === '1') readerSettings.fit = 'fit-width';
        if (localStorage.getItem('image_fit_height') === '1') readerSettings.fit = 'fit-height';
        if (localStorage.getItem('image_width')) {
          const w = localStorage.getItem('image_width');
          readerSettings.width = (w === '100%') ? '100%' : (parseInt(w, 10) || 800);
        }
        if (localStorage.getItem('stretch_small_pages')) readerSettings.stretchSmall = localStorage.getItem('stretch_small_pages') === '1';
        if (localStorage.getItem('max_width_limit')) readerSettings.maxWidthLimit = localStorage.getItem('max_width_limit');
        if (localStorage.getItem('max_height_limit')) readerSettings.maxHeightLimit = localStorage.getItem('max_height_limit');
        if (localStorage.getItem('page_gap')) readerSettings.spacing = parseInt(localStorage.getItem('page_gap'), 10) || 0;
        if (localStorage.getItem('night_mode')) readerSettings.night = localStorage.getItem('night_mode') === '1';
        if (localStorage.getItem('brightness')) readerSettings.brightness = parseInt(localStorage.getItem('brightness'), 10) || 100;
        if (localStorage.getItem('auto_advance_chapter')) readerSettings.autoAdvanceChapter = localStorage.getItem('auto_advance_chapter') === '1';
        if (localStorage.getItem('history_mode')) readerSettings.historyMode = localStorage.getItem('history_mode');
        if (localStorage.getItem('tap_turn_mode')) readerSettings.tapTurnMode = localStorage.getItem('tap_turn_mode');
        if (localStorage.getItem('scroll_turn_mode')) readerSettings.scrollTurnMode = localStorage.getItem('scroll_turn_mode');
        if (localStorage.getItem('fullscreen_toggle')) readerSettings.dblClickFullscreen = localStorage.getItem('fullscreen_toggle') === '1';
        if (localStorage.getItem('mobile_swipe')) readerSettings.swipeTurn = localStorage.getItem('mobile_swipe') === '1';
        if (localStorage.getItem('auto_scroll_fit_mode')) readerSettings.autoScrollFitMode = localStorage.getItem('auto_scroll_fit_mode');
        if (localStorage.getItem('auto_scroll_offset')) readerSettings.autoScrollOffset = parseInt(localStorage.getItem('auto_scroll_offset'), 10) || 0;
      }

      const savedKeys = localStorage.getItem('webcomics_reader_keybinds') || localStorage.getItem('keybinds');
      if (savedKeys) {
        try { keybindSettings = { ...defaultKeybinds, ...JSON.parse(savedKeys) }; } catch (_) {}
      }
    } catch (e) {
      console.debug('Error reading reader settings:', e);
    }
  }

  function saveReaderSettings() {
    try {
      localStorage.setItem('webcomics_reader_settings', JSON.stringify(readerSettings));
      localStorage.setItem('webcomics_reader_keybinds', JSON.stringify(keybindSettings));
      localStorage.setItem('reader_settings_mode', readerSettings.presetMode || 'recommended');
      localStorage.setItem('reader_preset_version', String(RECOMMENDED_PRESET_VERSION));
      localStorage.setItem('reader_mode', readerSettings.layout);
      localStorage.setItem('reading_direction', readerSettings.direction);
      localStorage.setItem('header_visibility', readerSettings.headerVisibility);
      localStorage.setItem('progress_bar_style', readerSettings.progressBarStyle);
      localStorage.setItem('progress_bar_position', readerSettings.progressBarPos);
      localStorage.setItem('progress_bar_size', String(readerSettings.progressBarSize));
      localStorage.setItem('cursor_hints', readerSettings.cursorHints);
      localStorage.setItem('reader_extras', JSON.stringify(readerSettings.readerExtras));
      localStorage.setItem('reader_background', readerSettings.readerBg);
      localStorage.setItem('image_fit', readerSettings.fit || 'custom');
      localStorage.setItem('image_fit_width', readerSettings.fit === 'fit-width' ? '1' : '0');
      localStorage.setItem('image_fit_height', readerSettings.fit === 'fit-height' ? '1' : '0');
      localStorage.setItem('image_width', String(readerSettings.width));
      localStorage.setItem('stretch_small_pages', readerSettings.stretchSmall ? '1' : '0');
      localStorage.setItem('max_width_limit', readerSettings.maxWidthLimit);
      localStorage.setItem('max_height_limit', readerSettings.maxHeightLimit);
      localStorage.setItem('page_gap', String(readerSettings.spacing));
      localStorage.setItem('night_mode', readerSettings.night ? '1' : '0');
      localStorage.setItem('brightness', String(readerSettings.brightness));
      localStorage.setItem('auto_advance_chapter', readerSettings.autoAdvanceChapter ? '1' : '0');
      localStorage.setItem('history_mode', readerSettings.historyMode);
      localStorage.setItem('tap_turn_mode', readerSettings.tapTurnMode);
      localStorage.setItem('scroll_turn_mode', readerSettings.scrollTurnMode);
      localStorage.setItem('fullscreen_toggle', readerSettings.dblClickFullscreen ? '1' : '0');
      localStorage.setItem('mobile_swipe', readerSettings.swipeTurn ? '1' : '0');
      localStorage.setItem('auto_scroll_fit_mode', readerSettings.autoScrollFitMode);
      localStorage.setItem('auto_scroll_offset', String(readerSettings.autoScrollOffset));
      localStorage.setItem('keybinds', JSON.stringify(keybindSettings));
    } catch (e) {
      console.debug('Error saving reader settings:', e);
    }
  }

  // ── PRESET BADGE & RESTORE FUNCTIONS ──
  function updatePresetBadgeUI() {
    const icon = document.getElementById('preset-badge-icon');
    const text = document.getElementById('preset-badge-text');
    const quickBtn = document.getElementById('btn-restore-recommended-quick');
    const modalBtn = document.getElementById('btn-restore-recommended-modal');
    const tab5Btn = document.getElementById('btn-restore-recommended-tab5');

    if (readerSettings.presetMode === 'recommended') {
      if (icon) {
        icon.textContent = '⭐';
        icon.style.color = '#fbbf24';
      }
      if (text) {
        text.textContent = 'Chế độ: Khuyến nghị';
      }
      if (quickBtn) quickBtn.style.display = 'none';
      if (modalBtn) {
        modalBtn.style.opacity = '0.5';
        modalBtn.title = 'Đang dùng cài đặt khuyến nghị';
      }
      if (tab5Btn) {
        tab5Btn.style.opacity = '0.6';
      }
    } else {
      if (icon) {
        icon.textContent = '⚙';
        icon.style.color = '#60a5fa';
      }
      if (text) {
        text.textContent = 'Chế độ: Tùy chỉnh';
      }
      if (quickBtn) quickBtn.style.display = 'inline-block';
      if (modalBtn) {
        modalBtn.style.opacity = '1';
        modalBtn.title = 'Quay lại cài đặt khuyến nghị';
      }
      if (tab5Btn) {
        tab5Btn.style.opacity = '1';
      }
    }
  }

  function markSettingsAsCustom() {
    if (readerSettings.presetMode !== 'custom') {
      readerSettings.presetMode = 'custom';
      try {
        localStorage.setItem('reader_settings_mode', 'custom');
      } catch (e) {}
      updatePresetBadgeUI();
    }
  }

  function applyRecommendedPreset() {
    readerSettings = JSON.parse(JSON.stringify(recommendedReaderSettings));
    readerSettings.presetMode = 'recommended';
    try {
      localStorage.setItem('reader_settings_mode', 'recommended');
      localStorage.setItem('reader_preset_version', String(RECOMMENDED_PRESET_VERSION));
    } catch (e) {}
    saveReaderSettings();
    applyAllReaderSettings();
    updatePresetBadgeUI();
  }

  // ── TAB SWITCHING ──
  function switchReaderTab(tabName) {
    const canonicalName = (tabName === 'fit' || tabName === 'image') ? 'image' : ((tabName === 'keys' || tabName === 'keybinds') ? 'keybinds' : tabName);
    readerSettings.activeTab = canonicalName;
    const tabBtns = {
      layout: document.getElementById('tab-btn-layout'),
      image: document.getElementById('tab-btn-image') || document.getElementById('tab-btn-fit'),
      keybinds: document.getElementById('tab-btn-keybinds') || document.getElementById('tab-btn-keys'),
      behaviors: document.getElementById('tab-btn-behaviors')
    };

    Object.entries(tabBtns).forEach(([k, btn]) => {
      if (k === canonicalName) {
        btn?.classList.add('active');
        btn?.setAttribute('aria-selected', 'true');
      } else {
        btn?.classList.remove('active');
        btn?.setAttribute('aria-selected', 'false');
      }
    });

    const targetPane = document.getElementById(`tab-pane-${canonicalName}`) || document.getElementById(`tab-pane-${tabName}`);
    const panel = document.getElementById('reader-settings-panel');
    if (targetPane && panel) {
      const targetTop = targetPane.offsetTop - panel.offsetTop - 55;
      panel.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
    }

    if (canonicalName === 'keybinds' || tabName === 'keys') {
      renderKeybindsList();
    }
  }

  // ── APPLY ALL READER SETTINGS ──
  function applyAllReaderSettings() {
    setReadingLayout(readerSettings.layout || 'vertical', false);
    setReadingDirection(readerSettings.direction || 'ltr', false);
    setHeaderVisibility(readerSettings.headerVisibility || 'shown', false);
    setProgressBarStyle(readerSettings.progressBarStyle || 'light', false);
    setProgressBarPosition(readerSettings.progressBarPos || 'bottom', false);
    setProgressBarSize(readerSettings.progressBarSize || 3, false);
    setCursorHints(readerSettings.cursorHints || 'none', false);

    // Reader extras
    if (readerSettings.readerExtras) {
      Object.entries(readerSettings.readerExtras).forEach(([k, v]) => {
        setReaderExtra(k, v, false);
      });
    }

    setReaderBackground(readerSettings.readerBg || 'theme', false);

    // Fit & sizing
    if (readerSettings.fit === 'fit-height') {
      setFitMode('fit-height', false);
    } else if (readerSettings.fit === 'fit-width') {
      setFitMode('fit-width', false);
    } else {
      setReaderWidth(readerSettings.width || 800, false);
    }

    setStretchSmall(readerSettings.stretchSmall, false);
    setMaxWidthLimit(readerSettings.maxWidthLimit || 'none', false);
    setMaxHeightLimit(readerSettings.maxHeightLimit || 'none', false);
    setPageSpacing(readerSettings.spacing || 0, false);
    setBrightness(readerSettings.brightness || 100, false);

    if (readerSettings.night) {
      document.body.classList.add('reader-night-mode');
      const btnNight = document.getElementById('btn-toggle-night');
      btnNight?.classList.add('active');
      btnNight?.setAttribute('aria-pressed', 'true');
      if (btnNight) btnNight.textContent = '🌙 Đang bật giảm chói';
    } else {
      document.body.classList.remove('reader-night-mode');
      const btnNight = document.getElementById('btn-toggle-night');
      btnNight?.classList.remove('active');
      btnNight?.setAttribute('aria-pressed', 'false');
      if (btnNight) btnNight.textContent = '🌙 Giảm chói mắt';
    }

    // Behaviors
    setAutoAdvanceChapter(readerSettings.autoAdvanceChapter, false);
    setHistoryMode(readerSettings.historyMode || 'push', false);
    setTapTurnMode(readerSettings.tapTurnMode || 'direction', false);
    setScrollTurnMode(readerSettings.scrollTurnMode || 'both', false);
    setSwipeTurnMode(readerSettings.swipeTurn !== false, false);
    setDblClickFullscreen(readerSettings.dblClickFullscreen !== false, false);
    setAutoScrollFitMode(readerSettings.autoScrollFitMode || 'none', false);
    setAutoScrollOffset(readerSettings.autoScrollOffset || 0, false);

    renderKeybindsList();
    updateHotkeyBox();
    updatePresetBadgeUI();

    if (readerSettings.panelOpen) {
      toggleSettingsPanel(true);
    }
  }

  // ── TOGGLE SETTINGS PANEL & FLOATING TRACK ──
  function toggleSettingsPanel(forceState) {
    const panel = document.getElementById('reader-settings-panel');
    const track = document.getElementById('reader-settings-track');
    const backdrop = document.getElementById('reader-settings-backdrop');
    if (!panel) return;

    const isCurrentlyOpen = panel.classList.contains('is-open') || panel.style.display === 'block';
    const shouldOpen = (forceState !== undefined) ? !!forceState : !isCurrentlyOpen;

    if (shouldOpen) {
      panel.classList.add('is-open');
      panel.style.display = 'block';
      document.body.classList.add('reader-panel-open');
      if (track) track.style.display = 'block';
      if (backdrop && window.innerWidth < 768) {
        backdrop.classList.add('is-open');
        backdrop.style.display = 'block';
      }
      readerSettings.panelOpen = true;
      saveReaderSettings();
    } else {
      panel.classList.remove('is-open');
      panel.style.display = 'none';
      document.body.classList.remove('reader-panel-open');
      if (track && window.innerWidth >= 1024) {
        track.style.display = 'none';
      }
      if (backdrop) {
        backdrop.classList.remove('is-open');
        backdrop.style.display = 'none';
      }
      readerSettings.panelOpen = false;
      saveReaderSettings();
    }
  }

  document.addEventListener('click', function(e) {
    const panel = document.getElementById('reader-settings-panel');
    const btn = document.getElementById('btn-open-settings');
    const dockBtn = document.querySelector('.reader-bottom-dock button[title*="Cài đặt"]');
    const floatBtn = document.getElementById('floating-menu-btn');
    if (panel && (panel.classList.contains('is-open') || panel.style.display === 'block')) {
      if (window.innerWidth < 1024) {
        if (!panel.contains(e.target) && !btn?.contains(e.target) && !dockBtn?.contains(e.target) && !floatBtn?.contains(e.target)) {
          toggleSettingsPanel(false);
        }
      }
    }
  });

  // ── FULL / ADVANCED SETTINGS MODAL HANDLERS ──
  function openAdvancedModal() {
    const modal = document.getElementById('reader-advanced-modal');
    const backdrop = document.getElementById('reader-advanced-backdrop');
    if (!modal) return;
    modal.classList.add('is-open');
    if (backdrop) backdrop.classList.add('is-open');
    document.body.classList.add('reader-modal-open');
    loadReaderSettings();
  }

  function closeAdvancedModal() {
    const modal = document.getElementById('reader-advanced-modal');
    const backdrop = document.getElementById('reader-advanced-backdrop');
    if (!modal) return;
    modal.classList.remove('is-open');
    if (backdrop) backdrop.classList.remove('is-open');
    document.body.classList.remove('reader-modal-open');
  }

  function switchReaderTab(tabName) {
    const tabs = ['layout', 'image', 'keybinds', 'behaviors', 'other'];
    tabs.forEach(t => {
      const btn = document.getElementById(`tab-btn-${t}`);
      const pane = document.getElementById(`tab-pane-${t}`);
      if (t === tabName) {
        btn?.classList.add('active');
        btn?.setAttribute('aria-selected', 'true');
        pane?.classList.add('active');
      } else {
        btn?.classList.remove('active');
        btn?.setAttribute('aria-selected', 'false');
        pane?.classList.remove('active');
      }
    });
  }

  function setSwipeTurnMode(val, persist = true) {
    readerSettings.swipeTurn = !!val;
    const btnOff = document.getElementById('btn-swipe-turn-off');
    const btnOn = document.getElementById('btn-swipe-turn-on');
    if (btnOff && btnOn) {
      btnOff.classList.toggle('active', !val);
      btnOff.setAttribute('aria-pressed', !val ? 'true' : 'false');
      btnOn.classList.toggle('active', !!val);
      btnOn.setAttribute('aria-pressed', !!val ? 'true' : 'false');
    }
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  // ── TAB 1: BỐ CỤC TRANG (Page Layout) ──
  function setReadingLayout(mode, persist = true) {
    readerSettings.layout = mode;
    const body = document.body;
    const singleNav = document.getElementById('single-page-nav');
    const dockPageNav = document.getElementById('dock-page-nav');
    const btnVert = document.getElementById('btn-mode-vertical');
    const advBtnVert = document.getElementById('adv-btn-mode-vertical');
    const btnSingle = document.getElementById('btn-mode-single');
    const advBtnSingle = document.getElementById('adv-btn-mode-single');
    const btnDouble = document.getElementById('btn-mode-double');
    const advBtnDouble = document.getElementById('adv-btn-mode-double');
    const dirSection = document.getElementById('section-reading-direction');
    const advDirSection = document.getElementById('adv-section-reading-direction');
    const spacingSection = document.getElementById('section-page-spacing');
    const spacingLabel = document.getElementById('label-page-spacing');

    body.classList.remove('reader-layout-vertical', 'reader-layout-single', 'reader-layout-double');
    [btnVert, advBtnVert, btnSingle, advBtnSingle, btnDouble, advBtnDouble].forEach(b => {
      b?.classList.remove('active');
      b?.setAttribute('aria-pressed', 'false');
    });

    if (mode === 'single') {
      body.classList.add('reader-layout-single');
      if (readerSettings.direction === 'rtl') body.classList.add('reader-dir-rtl');
      [btnSingle, advBtnSingle].forEach(b => {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      });
      if (singleNav) singleNav.style.display = 'inline-flex';
      if (dockPageNav) dockPageNav.style.display = 'inline-flex';
      if (dirSection) dirSection.style.display = 'block';
      if (advDirSection) advDirSection.style.display = 'block';
      if (spacingSection) spacingSection.style.display = 'none';
      showPage(currentSinglePageIndex);
    } else if (mode === 'double') {
      body.classList.add('reader-layout-double');
      if (readerSettings.direction === 'rtl') body.classList.add('reader-dir-rtl');
      [btnDouble, advBtnDouble].forEach(b => {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      });
      if (singleNav) singleNav.style.display = 'inline-flex';
      if (dockPageNav) dockPageNav.style.display = 'inline-flex';
      if (dirSection) dirSection.style.display = 'block';
      if (advDirSection) advDirSection.style.display = 'block';
      if (spacingSection) {
        spacingSection.style.display = 'block';
        if (spacingLabel) spacingLabel.textContent = 'KHOẢNG CÁCH GIỮA 2 TRANG';
      }
      showPage(currentSinglePageIndex);
    } else {
      // vertical
      body.classList.add('reader-layout-vertical');
      body.classList.remove('reader-dir-rtl');
      [btnVert, advBtnVert].forEach(b => {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      });
      if (singleNav) singleNav.style.display = 'none';
      if (dockPageNav) dockPageNav.style.display = 'none';
      if (dirSection) dirSection.style.display = 'none';
      if (advDirSection) advDirSection.style.display = 'none';
      if (spacingSection) {
        spacingSection.style.display = 'block';
        if (spacingLabel) spacingLabel.textContent = 'KHOẢNG CÁCH GIỮA ẢNH';
      }
      pageWrappers.forEach(w => w.classList.remove('active-page', 'single-spread'));
    }

    updateHotkeyBox();
    window.readerImages?.show(currentSinglePageIndex, mode);
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setReadingDirection(dir, persist = true) {
    readerSettings.direction = dir;
    const body = document.body;
    const btnLtr = document.getElementById('btn-dir-ltr');
    const advBtnLtr = document.getElementById('adv-btn-dir-ltr');
    const btnRtl = document.getElementById('btn-dir-rtl');
    const advBtnRtl = document.getElementById('adv-btn-dir-rtl');

    if (dir === 'rtl') {
      if (readerSettings.layout !== 'vertical') {
        body.classList.add('reader-dir-rtl');
      }
      [btnLtr, advBtnLtr].forEach(b => {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      });
      [btnRtl, advBtnRtl].forEach(b => {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      });
    } else {
      body.classList.remove('reader-dir-rtl');
      [btnLtr, advBtnLtr].forEach(b => {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      });
      [btnRtl, advBtnRtl].forEach(b => {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      });
    }

    updateHotkeyBox();
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setPageSpacing(spacing, persist = true) {
    const num = Math.max(0, parseInt(spacing, 10) || 0);
    readerSettings.spacing = num;
    const container = document.getElementById('reader-container');
    const btns = {
      '0': document.getElementById('btn-space-0'),
      '8': document.getElementById('btn-space-8'),
      '16': document.getElementById('btn-space-16'),
      '24': document.getElementById('btn-space-24'),
    };

    Object.entries(btns).forEach(([key, b]) => {
      if (String(num) === key) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    const slider = document.getElementById('page-spacing-slider');
    const label = document.getElementById('page-spacing-val');
    if (slider) slider.value = num;
    if (label) label.textContent = `Khoảng cách: ${num}px`;

    if (container) {
      container.style.gap = num + 'px';
      document.documentElement.style.setProperty('--page-spacing', num + 'px');
    }

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setHeaderVisibility(val, persist = true) {
    const isHidden = (val === 'hidden' || val === 'hide');
    readerSettings.headerVisibility = isHidden ? 'hidden' : 'shown';
    document.body.classList.toggle('reader-header-hidden', isHidden);

    const btnHidden = document.getElementById('btn-header-hidden') || document.getElementById('btn-header-hide');
    const btnShown = document.getElementById('btn-header-shown') || document.getElementById('btn-header-show');
    if (isHidden) {
      btnHidden?.classList.add('active');
      btnHidden?.setAttribute('aria-pressed', 'true');
      btnShown?.classList.remove('active');
      btnShown?.setAttribute('aria-pressed', 'false');
    } else {
      btnShown?.classList.add('active');
      btnShown?.setAttribute('aria-pressed', 'true');
      btnHidden?.classList.remove('active');
      btnHidden?.setAttribute('aria-pressed', 'false');
    }

    const floatBtn = document.getElementById('floating-menu-btn');
    if (floatBtn) {
      floatBtn.style.display = (isHidden && readerSettings.readerExtras?.show_menu_btn) ? 'flex' : 'none';
    }

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setProgressBarStyle(style, persist = true) {
    readerSettings.progressBarStyle = style;
    const bar = document.getElementById('reader-progress-bar');
    const btnHidden = document.getElementById('btn-progress-hidden');
    const btnLight = document.getElementById('btn-progress-light');
    const btnNormal = document.getElementById('btn-progress-normal');

    btnHidden?.classList.remove('active');
    btnHidden?.setAttribute('aria-pressed', 'false');
    btnLight?.classList.remove('active');
    btnLight?.setAttribute('aria-pressed', 'false');
    btnNormal?.classList.remove('active');
    btnNormal?.setAttribute('aria-pressed', 'false');

    if (style === 'hidden') {
      btnHidden?.classList.add('active');
      btnHidden?.setAttribute('aria-pressed', 'true');
      bar?.classList.add('is-hidden');
    } else if (style === 'light') {
      btnLight?.classList.add('active');
      btnLight?.setAttribute('aria-pressed', 'true');
      bar?.classList.remove('is-hidden');
      if (bar) bar.style.opacity = '0.7';
    } else {
      btnNormal?.classList.add('active');
      btnNormal?.setAttribute('aria-pressed', 'true');
      bar?.classList.remove('is-hidden');
      if (bar) bar.style.opacity = '1';
    }

    updateProgress();
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setProgressBarPosition(pos, persist = true) {
    readerSettings.progressBarPos = pos;
    const bar = document.getElementById('reader-progress-bar');
    const btns = {
      bottom: document.getElementById('btn-pos-bottom') || document.getElementById('btn-progress-pos-bottom'),
      left: document.getElementById('btn-pos-left') || document.getElementById('btn-progress-pos-left'),
      right: document.getElementById('btn-pos-right') || document.getElementById('btn-progress-pos-right'),
      top: document.getElementById('btn-pos-top') || document.getElementById('btn-progress-pos-top')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === pos) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    if (bar) {
      bar.classList.remove('pos-top', 'pos-bottom', 'pos-left', 'pos-right');
      bar.classList.add(`pos-${pos}`);
    }

    updateProgress();
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setProgressBarSize(size, persist = true) {
    const num = Math.min(Math.max(parseInt(size, 10) || 4, 1), 16);
    readerSettings.progressBarSize = num;
    const bar = document.getElementById('reader-progress-bar');
    const slider = document.getElementById('progress-size-slider');
    const label = document.getElementById('progress-size-val');

    if (slider) slider.value = num;
    if (label) label.textContent = `Kích thước thanh tiến trình: ${num}px`;
    if (bar) bar.style.setProperty('--progress-size', `${num}px`);

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setCursorHints(hints, persist = true) {
    const canonical = (hints === 'pointer') ? 'cursor' : hints;
    readerSettings.cursorHints = canonical;
    const body = document.body;
    const btns = {
      none: document.getElementById('btn-cursor-none'),
      overlay: document.getElementById('btn-cursor-overlay'),
      cursor: document.getElementById('btn-cursor-cursor') || document.getElementById('btn-cursor-pointer')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === canonical) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    body.classList.remove('reader-cursor-overlay', 'reader-cursor-custom', 'reader-cursor-pointer');
    if (canonical === 'overlay') body.classList.add('reader-cursor-overlay');
    if (canonical === 'cursor') body.classList.add('reader-cursor-custom', 'reader-cursor-pointer');

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setReaderExtra(extraKey, checked, persist = true) {
    if (!readerSettings.readerExtras) readerSettings.readerExtras = {};
    const key = (extraKey === 'menuBtn') ? 'show_menu_btn' : ((extraKey === 'pageNum') ? 'show_page_number' : ((extraKey === 'grayscale') ? 'greyscale' : extraKey));
    readerSettings.readerExtras[key] = !!checked;

    const cb1 = document.getElementById(`extra-${key.replace(/_/g, '-')}`);
    const cb2 = document.getElementById(`toggle-extra-${extraKey}`);
    const cb3 = document.getElementById(`toggle-extra-${key.replace(/_/g, '-')}`);
    [cb1, cb2, cb3].forEach(c => { if (c) c.checked = !!checked; });

    if (key === 'greyscale') {
      document.body.classList.toggle('reader-grayscale', !!checked);
    } else if (key === 'dim') {
      document.body.classList.toggle('reader-dim', !!checked);
    } else if (key === 'show_menu_btn') {
      const floatBtn = document.getElementById('floating-menu-btn');
      if (floatBtn) {
        floatBtn.style.display = (readerSettings.headerVisibility === 'hidden' && !!checked) ? 'flex' : 'none';
      }
    } else if (key === 'show_page_number') {
      updateProgress();
    }

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setReaderBackground(bg, persist = true) {
    readerSettings.readerBg = bg;
    const body = document.body;
    const btns = {
      theme: document.getElementById('btn-bg-theme'),
      white: document.getElementById('btn-bg-white'),
      black: document.getElementById('btn-bg-black')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === bg) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    body.classList.remove('reader-bg-white', 'reader-bg-black');
    if (bg === 'white') body.classList.add('reader-bg-white');
    if (bg === 'black') body.classList.add('reader-bg-black');

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  // ── TAB 2: HIỂN THỊ ẢNH (Image Fit & Display) ──
  function setFitMode(mode, persist = true) {
    readerSettings.fit = mode;
    const body = document.body;
    const btnWidth = document.getElementById('btn-fit-width');
    const advBtnWidth = document.getElementById('adv-btn-fit-width');
    const btnHeight = document.getElementById('btn-fit-height');
    const advBtnHeight = document.getElementById('adv-btn-fit-height');
    const btnFull = document.getElementById('btn-w-full') || document.getElementById('btn-maxw-full');
    const container = document.getElementById('reader-container');

    body.classList.remove('reader-fit-width', 'reader-fit-height');
    [btnWidth, advBtnWidth].forEach(b => {
      b?.classList.remove('active');
      b?.setAttribute('aria-pressed', 'false');
    });
    [btnHeight, advBtnHeight].forEach(b => {
      b?.classList.remove('active');
      b?.setAttribute('aria-pressed', 'false');
    });

    if (mode === 'fit-width') {
      body.classList.add('reader-fit-width');
      [btnWidth, advBtnWidth].forEach(b => {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      });
      readerSettings.width = '100%';
      if (container) {
        container.style.maxWidth = '100%';
        container.style.width = '100%';
      }
      btnFull?.classList.add('active');
      btnFull?.setAttribute('aria-pressed', 'true');
      if (readerSettings.autoScrollFitMode === 'width') {
        window.scrollTo({ top: readerSettings.autoScrollOffset || 0, behavior: 'smooth' });
      }
    } else if (mode === 'fit-height') {
      body.classList.add('reader-fit-height');
      [btnHeight, advBtnHeight].forEach(b => {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      });
      if (container) {
        container.style.maxWidth = '100%';
      }
      if (readerSettings.autoScrollFitMode === 'height') {
        window.scrollTo({ top: readerSettings.autoScrollOffset || 0, behavior: 'smooth' });
      }
    }

    window.readerImages?.resize();
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setReaderWidth(w, persist = true) {
    readerSettings.width = w;
    readerSettings.fit = (w === '100%') ? 'fit-width' : 'custom';
    const body = document.body;
    body.classList.remove('reader-fit-height');

    const btnWidth = document.getElementById('btn-fit-width');
    const btnHeight = document.getElementById('btn-fit-height');
    btnHeight?.classList.remove('active');
    btnHeight?.setAttribute('aria-pressed', 'false');

    if (w === '100%') {
      body.classList.add('reader-fit-width');
      btnWidth?.classList.add('active');
      btnWidth?.setAttribute('aria-pressed', 'true');
    } else {
      body.classList.remove('reader-fit-width');
      btnWidth?.classList.remove('active');
      btnWidth?.setAttribute('aria-pressed', 'false');
    }

    const container = document.getElementById('reader-container');
    const btns = {
      '680': document.getElementById('btn-w-680') || document.getElementById('btn-maxw-680'),
      '800': document.getElementById('btn-w-800') || document.getElementById('btn-maxw-800'),
      '1000': document.getElementById('btn-w-1000') || document.getElementById('btn-maxw-1000'),
      '100%': document.getElementById('btn-w-full') || document.getElementById('btn-maxw-full'),
    };

    Object.entries(btns).forEach(([key, b]) => {
      if (String(w) === key) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    if (container) {
      container.style.maxWidth = (w === '100%') ? '100%' : (w + 'px');
      container.style.width = '100%';
    }

    window.readerImages?.resize();
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setStretchSmall(val, persist = true) {
    readerSettings.stretchSmall = !!val;
    document.body.classList.toggle('reader-stretch-small', !!val);
    const cb = document.getElementById('checkbox-stretch-small');
    if (cb) cb.checked = !!val;
    const btn = document.getElementById('btn-stretch-small');
    if (btn) {
      btn.classList.toggle('active', !!val);
      btn.setAttribute('aria-pressed', val ? 'true' : 'false');
    }
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function toggleStretchSmall(persist = true) {
    setStretchSmall(!readerSettings.stretchSmall, persist);
  }

  function setMaxWidthLimit(val, persist = true) {
    readerSettings.maxWidthLimit = val;
    const btns = {
      '680': document.getElementById('btn-maxw-680') || document.getElementById('btn-w-680'),
      '800': document.getElementById('btn-maxw-800') || document.getElementById('btn-w-800'),
      '1000': document.getElementById('btn-maxw-1000') || document.getElementById('btn-w-1000'),
      'full': document.getElementById('btn-maxw-full') || document.getElementById('btn-w-full'),
      'none': document.getElementById('btn-maxw-none')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === val) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    const container = document.getElementById('reader-container');
    if (container) {
      if (val === 'none') {
        if (readerSettings.fit === 'fit-width') container.style.maxWidth = '100%';
        else if (readerSettings.width) container.style.maxWidth = (readerSettings.width === '100%') ? '100%' : `${readerSettings.width}px`;
      } else if (val === 'full') {
        container.style.maxWidth = '100%';
      } else {
        container.style.maxWidth = `${val}px`;
      }
    }

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setMaxHeightLimit(val, persist = true) {
    readerSettings.maxHeightLimit = val;
    const btns = {
      '70vh': document.getElementById('btn-maxh-70') || document.getElementById('btn-h-70vh'),
      '85vh': document.getElementById('btn-maxh-85') || document.getElementById('btn-h-85vh'),
      '100vh': document.getElementById('btn-maxh-100') || document.getElementById('btn-h-100vh'),
      'none': document.getElementById('btn-maxh-none') || document.getElementById('btn-h-none')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === val) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    const imgs = document.querySelectorAll('.comic-page-img');
    imgs.forEach(img => {
      img.classList.remove('max-h-70vh', 'max-h-85vh', 'max-h-100vh');
      if (val !== 'none') {
        img.classList.add(`max-h-${val}`);
      }
    });

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function toggleNightMode(persist = true) {
    readerSettings.night = !readerSettings.night;
    const btn = document.getElementById('btn-night-mode') || document.getElementById('btn-toggle-night');
    if (readerSettings.night) {
      document.body.classList.add('reader-night-mode');
      btn?.classList.add('active');
      btn?.setAttribute('aria-pressed', 'true');
      if (btn) btn.textContent = '🌙 Đang bật giảm chói';
    } else {
      document.body.classList.remove('reader-night-mode');
      btn?.classList.remove('active');
      btn?.setAttribute('aria-pressed', 'false');
      if (btn) btn.textContent = '🌙 Giảm chói mắt';
    }
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setBrightness(val, persist = true) {
    const num = Math.min(Math.max(parseInt(val, 10) || 100, 30), 100);
    readerSettings.brightness = num;

    const container = document.getElementById('reader-container');
    if (container) {
      container.style.filter = (num < 100) ? `brightness(${num}%)` : 'none';
    }

    const slider = document.getElementById('brightness-slider');
    const valLabel = document.getElementById('brightness-val');
    const advSlider = document.getElementById('adv-brightness-slider');
    const advValLabel = document.getElementById('adv-brightness-val');

    if (slider) slider.value = num;
    if (valLabel) valLabel.textContent = `Độ sáng: ${num}%`;
    if (advSlider) advSlider.value = num;
    if (advValLabel) advValLabel.textContent = `${num}%`;

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  // ── TAB 3: QUẢN LÝ PHÍM TẮT (Keybinds) ──
  function escapeHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function formatKeyDisplay(k) {
    if (k === ' ') return 'Space';
    if (k === 'ArrowRight') return '→';
    if (k === 'ArrowLeft') return '←';
    if (k === 'ArrowUp') return '↑';
    if (k === 'ArrowDown') return '↓';
    return k.toUpperCase();
  }

  function renderKeybindsList() {
    const table = document.getElementById('keybinds-list-container') || document.getElementById('keybinds-table');
    if (!table) return;

    const actions = Object.keys(defaultKeybinds);
    let html = '';

    actions.forEach(act => {
      const label = keybindActionLabels[act] || act;
      const keys = keybindSettings[act] || [];
      const isListening = listeningAction === act;

      html += `
        <div class="keybind-row">
          <span class="keybind-label">${escapeHtml(label)}</span>
          <div class="keybind-keys">
            ${keys.map((k, idx) => `
              <span class="keybind-badge">
                ${escapeHtml(formatKeyDisplay(k))}
                <span class="keybind-remove" onclick="deleteKeybind('${act}', ${idx})" title="Xóa phím">✕</span>
              </span>
            `).join('')}
            ${isListening ? '<span class="keybind-badge" style="background:#ff5e36; color:#fff;">Nhấn phím... (Esc hủy)</span>' : ''}
            <button type="button" class="keybind-add-btn" onclick="startRecordingKey('${act}')" title="Gán thêm phím">+</button>
          </div>
          <button type="button" class="keybind-reset-btn" onclick="resetActionKeybind('${act}')" title="Đặt lại action này">↺</button>
        </div>
      `;
    });

    table.innerHTML = html;
  }

  function startRecordingKey(action) {
    listeningAction = action;
    renderKeybindsList();
  }

  function deleteKeybind(action, index) {
    if (keybindSettings[action]) {
      keybindSettings[action].splice(index, 1);
      saveReaderSettings();
      renderKeybindsList();
      updateHotkeyBox();
    }
  }

  function resetActionKeybind(action) {
    if (defaultKeybinds[action]) {
      keybindSettings[action] = [...defaultKeybinds[action]];
      saveReaderSettings();
      renderKeybindsList();
      updateHotkeyBox();
    }
  }

  function resetAllKeybinds() {
    keybindSettings = JSON.parse(JSON.stringify(defaultKeybinds));
    saveReaderSettings();
    renderKeybindsList();
    updateHotkeyBox();
  }

  function updateHotkeyBox() {
    const box = document.getElementById('reader-hotkey-box');
    if (!box) return;

    const getKeyList = (act) => (keybindSettings[act] || defaultKeybinds[act] || []).map(formatKeyDisplay).join(' / ') || 'Chưa gán';

    if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
      const modeName = readerSettings.layout === 'single' ? 'Trang đơn' : 'Trang đôi';
      const isRtl = readerSettings.direction === 'rtl';
      box.innerHTML = `
        <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">⌨️ Phím tắt (${modeName}):</div>
        <div>• <code>${getKeyList('page_left')}</code> / <code>${getKeyList('page_right')}</code>: Lật trang (${isRtl ? 'Phải sang Trái' : 'Trái sang Phải'})</div>
        <div>• <code>${getKeyList('scroll_up')}</code> / <code>${getKeyList('scroll_down')}</code>: Trang trước / Trang sau</div>
        <div>• <code>${getKeyList('chapter_backward')}</code> / <code>${getKeyList('chapter_forward')}</code>: Chap trước / Chap sau</div>
        <div>• <code>${getKeyList('toggle_direction')}</code>: Đổi hướng đọc • <code>${getKeyList('cycle_fit_mode')}</code>: Đổi chế độ ảnh</div>
        <div>• <code>${getKeyList('toggle_menu')}</code>: Bật/tắt cài đặt • <code>${getKeyList('toggle_fullscreen')}</code>: Toàn màn hình</div>
      `;
    } else {
      box.innerHTML = `
        <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">⌨️ Phím tắt (Cuộn dọc):</div>
        <div>• <code>${getKeyList('scroll_up')}</code> / <code>${getKeyList('scroll_down')}</code>: Cuộn mượt trang</div>
        <div>• <code>${getKeyList('chapter_backward')}</code> / <code>${getKeyList('chapter_forward')}</code> hoặc <code>${getKeyList('page_left')}</code> / <code>${getKeyList('page_right')}</code>: Chuyển Chap</div>
        <div>• <code>${getKeyList('toggle_menu')}</code>: Cài đặt • <code>${getKeyList('toggle_fullscreen')}</code>: Toàn màn hình</div>
        <div>• <code>${getKeyList('cycle_fit_mode')}</code>: Chuyển chế độ ảnh • <code>Esc</code>: Đóng bảng cài đặt</div>
      `;
    }
  }

  // ── TAB 4: HÀNH VI ĐỌC (Behaviors) ──
  function setAutoAdvanceChapter(val, persist = true) {
    readerSettings.autoAdvanceChapter = !!val;
    const btnOn = document.getElementById('btn-auto-advance-on');
    const btnOff = document.getElementById('btn-auto-advance-off');
    if (val) {
      btnOn?.classList.add('active');
      btnOn?.setAttribute('aria-pressed', 'true');
      btnOff?.classList.remove('active');
      btnOff?.setAttribute('aria-pressed', 'false');
    } else {
      btnOff?.classList.add('active');
      btnOff?.setAttribute('aria-pressed', 'true');
      btnOn?.classList.remove('active');
      btnOn?.setAttribute('aria-pressed', 'false');
    }
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setHistoryMode(mode, persist = true) {
    readerSettings.historyMode = mode;
    const btns = {
      none: document.getElementById('btn-hist-none') || document.getElementById('btn-history-none'),
      replace: document.getElementById('btn-hist-replace') || document.getElementById('btn-history-replace'),
      push: document.getElementById('btn-hist-push') || document.getElementById('btn-history-push')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === mode) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setTapTurnMode(mode, persist = true) {
    const canonical = (mode === 'directional' || mode === 'direction') ? 'direction' : ((mode === 'always' || mode === 'always_forward') ? 'always_forward' : 'off');
    readerSettings.tapTurnMode = canonical;
    const btns = {
      direction: document.getElementById('btn-tap-dir') || document.getElementById('btn-tap-directional'),
      always_forward: document.getElementById('btn-tap-forward') || document.getElementById('btn-tap-always'),
      off: document.getElementById('btn-tap-off') || document.getElementById('btn-tap-none')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === canonical) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setScrollTurnMode(mode, persist = true) {
    const canonical = (mode === 'none' || mode === 'off') ? 'off' : ((mode === 'keys' || mode === 'keyboard') ? 'keyboard' : mode);
    readerSettings.scrollTurnMode = canonical;
    const btns = {
      off: document.getElementById('btn-scroll-off') || document.getElementById('btn-scroll-none'),
      wheel: document.getElementById('btn-scroll-wheel'),
      keyboard: document.getElementById('btn-scroll-keyboard') || document.getElementById('btn-scroll-keys'),
      both: document.getElementById('btn-scroll-both')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === canonical) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setDblClickFullscreen(val, persist = true) {
    readerSettings.dblClickFullscreen = !!val;
    const btnOn = document.getElementById('btn-dblclick-on') || document.getElementById('btn-dblclick-fs-on');
    const btnOff = document.getElementById('btn-dblclick-off') || document.getElementById('btn-dblclick-fs-off');
    if (val) {
      btnOn?.classList.add('active');
      btnOn?.setAttribute('aria-pressed', 'true');
      btnOff?.classList.remove('active');
      btnOff?.setAttribute('aria-pressed', 'false');
    } else {
      btnOff?.classList.add('active');
      btnOff?.setAttribute('aria-pressed', 'true');
      btnOn?.classList.remove('active');
      btnOn?.setAttribute('aria-pressed', 'false');
    }
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setAutoScrollFitMode(mode, persist = true) {
    readerSettings.autoScrollFitMode = mode;
    const btns = {
      width: document.getElementById('btn-autoscroll-width'),
      height: document.getElementById('btn-autoscroll-height'),
      none: document.getElementById('btn-autoscroll-none')
    };

    Object.entries(btns).forEach(([k, b]) => {
      if (k === mode) {
        b?.classList.add('active');
        b?.setAttribute('aria-pressed', 'true');
      } else {
        b?.classList.remove('active');
        b?.setAttribute('aria-pressed', 'false');
      }
    });

    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  function setAutoScrollOffset(val, persist = true) {
    const num = parseInt(val, 10) || 0;
    readerSettings.autoScrollOffset = num;
    const input1 = document.getElementById('input-autoscroll-offset');
    const input2 = document.getElementById('input-scroll-offset');
    if (input1) input1.value = num;
    if (input2) input2.value = num;
    const valLabel = document.getElementById('scroll-offset-val');
    if (valLabel) valLabel.textContent = `${num}px`;
    if (persist) {
      markSettingsAsCustom();
      saveReaderSettings();
    }
  }

  // ── RESET BUTTONS ──
  function resetAllSettingsToDefault() {
    applyRecommendedPreset();
    keybindSettings = JSON.parse(JSON.stringify(defaultKeybinds));
    try {
      localStorage.setItem('webcomics_reader_keybinds', JSON.stringify(keybindSettings));
      localStorage.setItem('keybinds', JSON.stringify(keybindSettings));
    } catch (e) {}
    renderKeybindsList();
    closeAdvancedModal();
  }

  function resetLocks() {
    listeningAction = null;
    wheelLock = false;
    renderKeybindsList();
  }

  function resetMargin() {
    setPageSpacing(0);
  }

  function resetOffset() {
    setAutoScrollOffset(0);
  }

  // ── SINGLE & DOUBLE PAGE NAVIGATION ──
  function showPage(index) {
    if (totalPagesCount === 0) return;

    if (readerSettings.layout === 'double') {
      currentSinglePageIndex = Math.floor(Math.max(0, Math.min(index, totalPagesCount - 1)) / 2) * 2;
      const secondPageIndex = currentSinglePageIndex + 1;
      const hasSecondPage = secondPageIndex < totalPagesCount;

      pageWrappers.forEach((wrapper, idx) => {
        if (idx === currentSinglePageIndex) {
          wrapper.classList.add('active-page');
          if (!hasSecondPage) {
            wrapper.classList.add('single-spread');
          } else {
            wrapper.classList.remove('single-spread');
          }
        } else if (idx === secondPageIndex && hasSecondPage) {
          wrapper.classList.add('active-page');
          wrapper.classList.remove('single-spread');
        } else {
          wrapper.classList.remove('active-page', 'single-spread');
        }
      });

      const endIdx = hasSecondPage ? (currentSinglePageIndex + 2) : totalPagesCount;
      const text = hasSecondPage
        ? `${currentSinglePageIndex + 1}-${endIdx} / ${totalPagesCount}`
        : `${totalPagesCount} / ${totalPagesCount}`;
      const singleCounter = document.getElementById('single-page-counter');
      const dockCounter = document.getElementById('dock-page-counter');
      if (singleCounter) singleCounter.textContent = text;
      if (dockCounter) dockCounter.textContent = text;
    } else {
      currentSinglePageIndex = Math.min(Math.max(index, 0), totalPagesCount - 1);
      pageWrappers.forEach((wrapper, idx) => {
        if (idx === currentSinglePageIndex) {
          wrapper.classList.add('active-page');
          wrapper.classList.remove('single-spread');
        } else {
          wrapper.classList.remove('active-page', 'single-spread');
        }
      });
      const text = `${currentSinglePageIndex + 1} / ${totalPagesCount}`;
      const singleCounter = document.getElementById('single-page-counter');
      const dockCounter = document.getElementById('dock-page-counter');
      if (singleCounter) singleCounter.textContent = text;
      if (dockCounter) dockCounter.textContent = text;
    }

    // Cập nhật URL và History theo historyMode
    const pageNum = currentSinglePageIndex + 1;
    if (readerSettings.historyMode === 'replace') {
      window.history.replaceState({ page: pageNum }, '', `#page-${pageNum}`);
    } else if (readerSettings.historyMode === 'push') {
      if (window.location.hash !== `#page-${pageNum}`) {
        window.history.pushState({ page: pageNum }, '', `#page-${pageNum}`);
      }
    }

    window.readerImages?.show(currentSinglePageIndex, readerSettings.layout);
    window.scrollTo({ top: 0, behavior: 'instant' });
    setTimeout(updateProgress, 50);
  }

  function advanceToNextChapter() {
    const nextBtn = document.getElementById('btn-next-chap') || document.getElementById('footer-btn-next-chap') || document.getElementById('dock-btn-next-chap');
    if (nextBtn && nextBtn.href && !nextBtn.classList.contains('disabled')) {
      window.location.href = nextBtn.href;
    }
  }

  function goToPrevChapter() {
    const prevBtn = document.getElementById('btn-prev-chap');
    if (prevBtn && prevBtn.href && !prevBtn.classList.contains('disabled')) {
      window.location.href = prevBtn.href;
    }
  }

  function nextPage() {
    if (readerSettings.layout === 'double') {
      if (currentSinglePageIndex + 2 < totalPagesCount) {
        showPage(currentSinglePageIndex + 2);
      } else {
        if (readerSettings.autoAdvanceChapter) {
          advanceToNextChapter();
        } else {
          advanceToNextChapter();
        }
      }
    } else if (readerSettings.layout === 'single') {
      if (currentSinglePageIndex < totalPagesCount - 1) {
        showPage(currentSinglePageIndex + 1);
      } else {
        if (readerSettings.autoAdvanceChapter) {
          advanceToNextChapter();
        } else {
          advanceToNextChapter();
        }
      }
    }
  }

  function prevPage() {
    if (readerSettings.layout === 'double') {
      if (currentSinglePageIndex >= 2) {
        showPage(currentSinglePageIndex - 2);
      } else {
        goToPrevChapter();
      }
    } else if (readerSettings.layout === 'single') {
      if (currentSinglePageIndex > 0) {
        showPage(currentSinglePageIndex - 1);
      } else {
        goToPrevChapter();
      }
    }
  }

  function handleNavNext() {
    if (readerSettings.direction === 'rtl') {
      prevPage();
    } else {
      nextPage();
    }
  }

  function handleNavPrev() {
    if (readerSettings.direction === 'rtl') {
      nextPage();
    } else {
      prevPage();
    }
  }

  // ── FULLSCREEN & UI TOGGLES ──
  function toggleFullscreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {});
    } else {
      document.exitFullscreen().catch(() => {});
    }
  }

  function toggleUI() {
    document.body.classList.toggle('ui-hidden');
  }

  // ── TOUCH & GESTURE NAVIGATION ──
  let touchStartX = 0;
  let touchStartY = 0;
  const containerSwipeEl = document.getElementById('reader-container');
  if (containerSwipeEl) {
    containerSwipeEl.addEventListener('touchstart', function(e) {
      if (readerSettings.layout === 'vertical') return;
      if (e.touches.length === 1) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
      }
    }, { passive: true });

    containerSwipeEl.addEventListener('touchend', function(e) {
      if (readerSettings.layout === 'vertical') return;
      if (e.changedTouches.length === 1) {
        const dx = e.changedTouches[0].clientX - touchStartX;
        const dy = e.changedTouches[0].clientY - touchStartY;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
          if (readerSettings.direction === 'rtl') {
            if (dx > 0) nextPage(); else prevPage();
          } else {
            if (dx < 0) nextPage(); else prevPage();
          }
        }
      }
    }, { passive: true });

    // Tap turn page & double click
    containerSwipeEl.addEventListener('click', function(e) {
      const isInteractive = e.target.closest('button, a, input, select, textarea, .reader-toolbar, .reader-settings-panel, .reader-settings-track, .reader-settings-backdrop, .reader-bottom-dock, #single-page-nav, #resume-scroll-toast, .broken-image-box');
      if (isInteractive) return;

      if (!e.target.closest('.comic-page-wrapper')) return;

      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.tapTurnMode === 'always_forward') {
          handleNavNext();
          return;
        } else if (readerSettings.tapTurnMode === 'direction') {
          const w = window.innerWidth;
          const clickX = e.clientX;
          if (clickX < w * 0.35) {
            handleNavPrev();
            return;
          } else if (clickX > w * 0.65) {
            handleNavNext();
            return;
          }
        }
      }

      toggleUI();
    });

    containerSwipeEl.addEventListener('dblclick', function(e) {
      if (!readerSettings.dblClickFullscreen) return;
      if (e.target.closest('button, a, input, select, textarea')) return;
      toggleFullscreen();
    });
  }

  // ── MOUSE WHEEL NAVIGATION ──
  window.addEventListener('wheel', function(e) {
    if (readerSettings.layout === 'vertical') return;
    if (readerSettings.scrollTurnMode !== 'wheel' && readerSettings.scrollTurnMode !== 'both') return;
    if (e.target.closest('#reader-settings-panel, #reader-chapter-picker, .comments-section, .reader-toolbar, .reader-footer')) return;

    if (Math.abs(e.deltaY) > 25) {
      e.preventDefault();
      if (wheelLock) return;
      wheelLock = true;
      setTimeout(() => { wheelLock = false; }, 200);

      if (e.deltaY > 0) {
        if (readerSettings.direction === 'rtl') prevPage(); else nextPage();
      } else {
        if (readerSettings.direction === 'rtl') nextPage(); else prevPage();
      }
    }
  }, { passive: false });

  // ── BROWSER HISTORY POPSTATE ──
  window.addEventListener('popstate', function(e) {
    if (readerSettings.historyMode === 'push' && e.state && typeof e.state.page === 'number') {
      showPage(e.state.page - 1);
    }
  });

  // ── DYNAMIC KEYBOARD SHORTCUTS ENGINE ──
  function isKeyMatched(actionName, eventKey) {
    const list = keybindSettings[actionName] || defaultKeybinds[actionName] || [];
    return list.some(k => k.toLowerCase() === eventKey.toLowerCase());
  }

  document.addEventListener('keydown', function(e) {
    // Nếu đang trong chế độ gán phím mới
    if (listeningAction) {
      e.preventDefault();
      e.stopPropagation();
      if (e.key === 'Escape' || e.key === 'Esc') {
        listeningAction = null;
        renderKeybindsList();
        return;
      }
      if (['Shift', 'Control', 'Alt', 'Meta'].includes(e.key)) return;

      const act = listeningAction;
      if (!Array.isArray(keybindSettings[act])) keybindSettings[act] = [];
      if (!keybindSettings[act].some(k => k.toLowerCase() === e.key.toLowerCase())) {
        keybindSettings[act].push(e.key);
      }
      listeningAction = null;
      saveReaderSettings();
      renderKeybindsList();
      updateHotkeyBox();
      return;
    }

    const activeEl = document.activeElement;
    if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'SELECT')) {
      return;
    }

    const key = e.key;

    // Phím Escape: Đóng modal nâng cao hoặc bảng cài đặt
    if (key === 'Escape' || key === 'Esc') {
      const modal = document.getElementById('reader-advanced-modal');
      if (modal && modal.classList.contains('is-open')) {
        e.preventDefault();
        closeAdvancedModal();
        return;
      }
      const panel = document.getElementById('reader-settings-panel');
      if (panel && (panel.classList.contains('is-open') || panel.style.display === 'block')) {
        e.preventDefault();
        toggleSettingsPanel(false);
        return;
      }
    }

    // Toggle menu
    if (isKeyMatched('toggle_menu', key)) {
      e.preventDefault();
      toggleSettingsPanel();
      return;
    }

    // Toggle fullscreen
    if (isKeyMatched('toggle_fullscreen', key)) {
      e.preventDefault();
      toggleFullscreen();
      return;
    }

    // Cycle fit mode
    if (isKeyMatched('cycle_fit_mode', key)) {
      e.preventDefault();
      const current = readerSettings.fit || 'custom';
      const next = current === 'custom' ? 'fit-width' : (current === 'fit-width' ? 'fit-height' : 'custom');
      if (next === 'custom') setReaderWidth(800);
      else setFitMode(next);
      return;
    }

    // Toggle direction
    if (isKeyMatched('toggle_direction', key)) {
      e.preventDefault();
      if (readerSettings.layout !== 'vertical') {
        setReadingDirection(readerSettings.direction === 'ltr' ? 'rtl' : 'ltr');
      }
      return;
    }

    // Chapter forward
    if (isKeyMatched('chapter_forward', key)) {
      e.preventDefault();
      advanceToNextChapter();
      return;
    }

    // Chapter backward
    if (isKeyMatched('chapter_backward', key)) {
      e.preventDefault();
      goToPrevChapter();
      return;
    }

    // Scroll up
    if (isKeyMatched('scroll_up', key)) {
      e.preventDefault();
      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.direction === 'rtl') nextPage(); else prevPage();
      } else {
        window.scrollBy({ top: -380, behavior: 'smooth' });
      }
      return;
    }

    // Scroll down
    if (isKeyMatched('scroll_down', key)) {
      e.preventDefault();
      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.direction === 'rtl') prevPage(); else nextPage();
      } else {
        window.scrollBy({ top: 380, behavior: 'smooth' });
      }
      return;
    }

    // Page right
    if (isKeyMatched('page_right', key)) {
      e.preventDefault();
      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.direction === 'rtl') prevPage(); else nextPage();
      } else {
        advanceToNextChapter();
      }
      return;
    }

    // Page left
    if (isKeyMatched('page_left', key)) {
      e.preventDefault();
      if (readerSettings.layout === 'single' || readerSettings.layout === 'double') {
        if (readerSettings.direction === 'rtl') nextPage(); else prevPage();
      } else {
        goToPrevChapter();
      }
      return;
    }
  });

  // Cập nhật thanh tiến độ đọc (Progress Bar) & Guest history
  window.addEventListener('scroll', updateProgress, { passive: true });

  function saveGuestReadingHistory(scrollPercent) {
    try {
      const currentItem = {
        comicId: {{ $comic->id }},
        title: @json($comic->title),
        cover: @json($comic->cover_url),
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

  // SMART IMAGE PREFETCH is handled by reader-images.js on the actual responsive images.

  // 6. XỬ LÝ ẢNH LỖI (Retry 2 lần + Báo lỗi tại chỗ cho Admin)
  function handleImageError(img) {
    if (img.hasAttribute('srcset') || img.closest('picture')?.querySelector('source[srcset]')) {
      img.removeAttribute('srcset');
      img.removeAttribute('data-srcset');
      img.closest('picture')?.querySelectorAll('source').forEach(source => source.remove());
      img.src = img.dataset.originalSrc;
      return;
    }
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
  // Parser-started images may fail before the reader script has been evaluated.
  document.querySelectorAll('.comic-page-img[data-early-error]').forEach(img => {
    delete img.dataset.earlyError;
    handleImageError(img);
  });
</script>
@endpush
