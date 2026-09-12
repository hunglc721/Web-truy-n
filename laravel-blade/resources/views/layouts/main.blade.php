<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', ($siteSettings['site_name'] ?? 'WebComics') . ' - ' . ($siteSettings['tagline'] ?? 'Đọc Manhua, Manhwa & Manga Online'))</title>
  @hasSection('meta')
    @yield('meta')
  @else
    <meta name="description" content="{{ $siteSettings['meta_description'] ?? 'Nền tảng đọc truyện tranh trực tuyến WebComics.' }}" />
    <meta name="keywords" content="{{ $siteSettings['seo_keywords'] ?? 'đọc truyện,manga,manhwa,manhua,webtoon' }}" />
  @endif
  <link rel="canonical" href="{{ url()->current() }}" />
  <meta property="og:site_name" content="{{ $siteSettings['site_name'] ?? 'WebComics' }}" />
  @if(request()->routeIs('comics.show') && isset($comic))
    @include('partials.comic-seo')
  @else
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
  @endif
  <link rel="manifest" href="{{ asset('manifest.json') }}" />
  <meta name="theme-color" content="#ff5e36" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=5" />
  <link rel="stylesheet" href="{{ asset('css/responsive.css') }}?v=5" />
  <style>
    :root{--card-bg:var(--bg-surface-1);--border:var(--border-color)}.footer-static-item{color:var(--text-muted);font-size:13px;display:block;padding:3px 0}
    /* Mobile Footer Accordion & Layout Critical Styles */
    @media (max-width: 767.98px) {
      .site-footer {
        padding: 28px 0 20px !important;
        background: #07090e !important;
      }
      .site-footer .container {
        padding-left: 16px !important;
        padding-right: 16px !important;
      }
      .site-footer .footer-newsletter-card {
        display: none !important;
      }
      .site-footer .footer-main-grid {
        display: flex !important;
        flex-direction: column !important;
        gap: 0 !important;
        padding-bottom: 0 !important;
        border-bottom: none !important;
      }
      .site-footer .fgrid-brand-col {
        margin-bottom: 20px !important;
        padding-bottom: 0 !important;
      }
      .site-footer .fgrid-brand-col .logo-link {
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
      }
      .site-footer .fgrid-brand-col .logo-icon svg {
        width: 36px !important;
        height: 36px !important;
      }
      .site-footer .fgrid-brand-col .logo-text {
        font-size: 19px !important;
        font-weight: 800 !important;
        color: var(--text-main) !important;
      }
      .site-footer .fbrand-desc {
        font-size: 13px !important;
        color: var(--text-sub) !important;
        margin-top: 6px !important;
        line-height: 1.45 !important;
        max-width: none !important;
      }

      /* Accordion Item */
      .site-footer .footer-accordion-item {
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
      }
      .site-footer .fcol-accordion-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 100% !important;
        padding: 14px 0 !important;
        margin: 0 !important;
        background: transparent !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        cursor: pointer !important;
        pointer-events: auto !important;
        text-align: left !important;
        -webkit-tap-highlight-color: transparent !important;
      }
      .site-footer .fcol-heading-text {
        font-size: 15px !important;
        font-weight: 700 !important;
        color: #FFFFFF !important;
        letter-spacing: -0.1px !important;
      }
      .site-footer .fcol-accordion-icon {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 24px !important;
        height: 24px !important;
        color: var(--text-muted) !important;
        transition: transform 0.25s ease, color 0.2s ease !important;
        flex-shrink: 0 !important;
      }
      .site-footer .fcol-accordion-icon .icon-plus {
        display: block !important;
      }
      .site-footer .fcol-accordion-icon .icon-minus {
        display: none !important;
      }

      /* Open State */
      .site-footer .footer-accordion-item.open .fcol-accordion-icon {
        color: var(--primary) !important;
        transform: none !important;
      }
      .site-footer .footer-accordion-item.open .fcol-accordion-icon .icon-plus {
        display: none !important;
      }
      .site-footer .footer-accordion-item.open .fcol-accordion-icon .icon-minus {
        display: block !important;
      }

      /* Expand / Collapse */
      .site-footer .footer-accordion-item:not(.open) .fcol-collapse {
        display: none !important;
        max-height: 0 !important;
        opacity: 0 !important;
        visibility: hidden !important;
        overflow: hidden !important;
      }
      .site-footer .footer-accordion-item.open .fcol-collapse {
        display: block !important;
        max-height: 500px !important;
        opacity: 1 !important;
        visibility: visible !important;
        overflow: visible !important;
        animation: fcolSlideDown 0.25s ease forwards !important;
      }
      @keyframes fcolSlideDown {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .site-footer .fcol-list {
        list-style: none !important;
        padding: 2px 0 16px 2px !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 11px !important;
      }
      .site-footer .fcol-list li {
        margin: 0 !important;
      }
      .site-footer .fcol-list a {
        font-size: 14px !important;
        color: var(--text-sub) !important;
        text-decoration: none !important;
        display: inline-block !important;
        padding: 2px 0 !important;
      }
      .site-footer .fcol-list a:hover,
      .site-footer .fcol-list a:active {
        color: var(--primary) !important;
      }

      /* Bottom Bar */
      .site-footer .footer-bottom-bar {
        display: none !important;
      }
      .site-footer .footer-bottom-mobile {
        display: block !important;
        margin-top: 24px !important;
        padding-top: 16px !important;
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        text-align: center !important;
      }
      .site-footer .fcopy-text-mobile {
        font-size: 12.5px !important;
        color: var(--text-muted) !important;
        margin: 0 0 6px !important;
      }
      .site-footer .footer-legal-links-mobile {
        font-size: 12.5px !important;
        color: var(--text-muted) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
      }
      .site-footer .footer-legal-links-mobile a {
        color: var(--text-muted) !important;
        text-decoration: none !important;
      }
      .site-footer .footer-legal-links-mobile .legal-sep {
        color: rgba(255, 255, 255, 0.2) !important;
      }
    }

    @media (min-width: 768px) {
      .site-footer .footer-accordion-item:not(.open) .fcol-collapse,
      .site-footer .footer-accordion-item.open .fcol-collapse {
        display: block !important;
      }
      .site-footer .fcol-accordion-btn {
        all: unset !important;
        display: block !important;
        cursor: default !important;
        pointer-events: none !important;
        margin-bottom: 20px !important;
      }
      .site-footer .fcol-heading-text {
        font-size: 14.5px !important;
        font-weight: 800 !important;
        color: var(--text-main) !important;
        letter-spacing: -0.2px !important;
      }
      .site-footer .fcol-accordion-icon {
        display: none !important;
      }
      .site-footer .footer-bottom-mobile {
        display: none !important;
      }
      .site-footer .footer-bottom-bar {
        display: flex !important;
      }
    }
  </style>
  @stack('styles')
</head>
<body class="dark-theme" data-auth-state="{{ auth()->check() ? (auth()->user()->canAccessAdmin() ? 'admin' : 'member') : 'guest' }}" data-notification-transport="{{ app()->environment('local') ? 'polling' : 'sse' }}">
  <header class="site-header" id="site-header">
    <div class="header-inner">
      <div class="header-left">
        <a href="{{ route('home') }}" class="logo-link" aria-label="{{ $siteSettings['site_name'] ?? 'WebComics' }} Trang chủ">
          <div class="logo-icon">
            <svg width="40" height="40" viewBox="0 0 44 44" fill="none">
              <rect width="44" height="44" rx="13" fill="url(#logo-grad)"/>
              <defs>
                <linearGradient id="logo-grad" x1="0" y1="0" x2="44" y2="44">
                  <stop offset="0%" stop-color="#FF5E36"/>
                  <stop offset="100%" stop-color="#FF2A6D"/>
                </linearGradient>
              </defs>
              <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-family="Inter, -apple-system, sans-serif" font-weight="900" font-size="19" fill="white" letter-spacing="-0.5">WC</text>
            </svg>
          </div>
          <span class="logo-text">{{ $siteSettings['site_name'] ?? 'WebComics' }}</span>
        </a>
        <nav class="main-nav" aria-label="Menu chính">
          <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Trang Chủ</a>
          <a href="{{ route('genres') }}" class="nav-link {{ request()->routeIs('genres') ? 'active' : '' }}">Thể Loại</a>
          <a href="{{ route('schedule') }}" class="nav-link {{ request()->routeIs('schedule*') ? 'active' : '' }}">Lịch Ra Truyện</a>
          <a href="{{ route('originals') }}" class="nav-link {{ request()->routeIs('originals') ? 'active' : '' }}">Độc Quyền</a>
        </nav>
      </div>
      <div class="header-right">
        <div class="search-wrap">
          <input id="search-input" type="search" placeholder="Tìm kiếm truyện..." aria-label="Tìm kiếm truyện tranh" class="search-input" autocomplete="off" />
          <span class="search-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <div class="search-dropdown" id="search-dropdown">
            <div class="search-recent-title">Tìm kiếm thịnh hành</div>
          </div>
        </div>
        <button type="button" class="header-action-link" id="pwa-install-btn" style="display:none;background:rgba(255,94,54,.15);color:var(--primary);border:1px solid rgba(255,94,54,.3);border-radius:999px;padding:6px 14px;font-weight:700;cursor:pointer;align-items:center;gap:6px;">📲 Cài App</button>
        <a href="{{ route('publish.create') }}" class="header-action-link" id="publish-link" title="Đăng truyện cho tác giả & nhóm dịch">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          <span>Đăng Truyện</span>
        </a>
        <div class="header-divider"></div>
        <div class="nav-icon-group">
          <a class="icon-btn" id="library-btn" aria-label="Tủ truyện" title="Tủ truyện của bạn" href="{{ auth()->check() ? route('user.library') : route('login') }}">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
          </a>
        </div>
        @guest
          <a href="{{ route('login') }}" class="btn btn-login">Đăng Nhập</a>
          <a href="{{ route('register') }}" class="btn btn-download">Đăng Ký</a>
        @else
          @if(auth()->user()->canAccessAdmin())<a href="{{ route('admin.dashboard') }}" class="btn btn-login" style="background:linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);color:#fff;border-color:transparent">🛡️ Quản Trị</a>@endif
          <a href="{{ route('user.dashboard') }}" class="btn btn-login" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="Khu vực thành viên của {{ auth()->user()->name }}">👤 {{ auth()->user()->name }}</a>
          <form action="{{ route('logout') }}" method="POST" style="margin:0">@csrf<button type="submit" class="btn btn-download">Đăng Xuất</button></form>
        @endguest
        <button type="button" class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Mở menu" aria-expanded="false" aria-controls="mobile-menu">☰</button>
      </div>
    </div>
  </header>

  <aside class="mobile-menu" id="mobile-menu" aria-hidden="true">
    <form action="{{ route('genres') }}" method="GET" class="mobile-search">
      <input type="search" name="q" placeholder="Tìm kiếm truyện..." aria-label="Tìm kiếm truyện trên mobile">
      <button type="submit" aria-label="Tìm kiếm">🔍</button>
    </form>
    <nav aria-label="Menu mobile">
      <a href="{{ route('home') }}">🏠 Trang Chủ</a>
      <a href="{{ route('genres') }}">📚 Thể Loại</a>
      <a href="{{ route('schedule') }}">📅 Lịch Ra Truyện</a>
      <a href="{{ route('schedule.completed') }}">✅ Truyện Hoàn Thành</a>
      <a href="{{ route('originals') }}">⭐ Độc Quyền</a>
      <a href="{{ route('publish.create') }}">✍️ Đăng Truyện</a>
      @auth
        <a href="{{ route('user.library') }}">📖 Tủ Truyện</a>
        <a href="{{ route('user.history') }}">🕘 Lịch Sử Đọc</a>
        <a href="{{ route('user.dashboard') }}">👤 Tài Khoản</a>
        @if(auth()->user()->canAccessAdmin())<a href="{{ route('admin.dashboard') }}">🛡️ Quản Trị</a>@endif
        <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit">🚪 Đăng Xuất</button></form>
      @else
        <a href="{{ route('login') }}">🔑 Đăng Nhập</a>
        <a href="{{ route('register') }}">✍️ Đăng Ký</a>
      @endauth
    </nav>
  </aside>

  @if(session('success'))<div class="container" style="padding-top:16px"><div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);color:#4ade80;font-weight:600">{{ session('success') }}</div></div>@endif
  @if(session('error'))<div class="container" style="padding-top:16px"><div style="padding:12px 16px;border-radius:10px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#f87171;font-weight:600">{{ session('error') }}</div></div>@endif

  @yield('content')

  @if(request()->routeIs('home'))
    @include('partials.home-discovery')
  @endif
  @if(request()->routeIs('originals'))
    @include('partials.originals-discovery')
  @endif

  <footer class="site-footer" id="site-footer">
    <div class="container">
      {{-- Newsletter Card (Desktop only) --}}
      <div class="footer-newsletter-card">
        <div class="newsletter-info">
          <span class="newsletter-tag">🚀 CỘNG ĐỒNG {{ strtoupper($siteSettings['site_name'] ?? 'WEBCOMICS') }}</span>
          <h3 class="newsletter-title">Theo dõi chương mới và truyện nổi bật</h3>
          <p class="newsletter-sub">{{ $siteSettings['tagline'] ?? 'Khám phá truyện mới, lịch phát hành và các tác phẩm đang thịnh hành.' }}</p>
        </div>
        <div class="newsletter-form">
          <span style="font-size:13px;color:var(--text-sub)">Kênh email chưa được cấu hình, nên không hiện form đăng ký giả.</span>
        </div>
      </div>

      {{-- Main Footer Columns / Accordion --}}
      <div class="footer-main-grid">
        {{-- Brand Column --}}
        <div class="fgrid-brand-col">
          <a href="{{ route('home') }}" class="logo-link" aria-label="{{ $siteSettings['site_name'] ?? 'WebComics' }}">
            <div class="logo-icon">
              <svg width="40" height="40" viewBox="0 0 44 44">
                <rect width="44" height="44" rx="12" fill="#FF5E36"/>
                <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="Inter" font-weight="900" font-size="18" fill="white">WC</text>
              </svg>
            </div>
            <span class="logo-text">{{ $siteSettings['site_name'] ?? 'WebComics' }}</span>
          </a>
          <p class="fbrand-desc">{{ $siteSettings['tagline'] ?? 'Đọc Manga, Manhwa & Manhua Online' }}</p>
        </div>

        {{-- Column 1: Khám Phá --}}
        <div class="fgrid-col footer-accordion-item">
          <button type="button" class="fcol-accordion-btn" aria-expanded="false" aria-controls="fcol-collapse-explore" id="fcol-btn-explore">
            <span class="fcol-heading-text">Khám Phá</span>
            <span class="fcol-accordion-icon" aria-hidden="true">
              <svg class="icon-plus" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <svg class="icon-minus" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </span>
          </button>
          <div class="fcol-collapse" id="fcol-collapse-explore" role="region" aria-labelledby="fcol-btn-explore">
            <ul class="fcol-list">
              <li><a href="{{ route('home') }}">Truyện Thịnh Hành</a></li>
              <li><a href="{{ route('genres') }}">Tất Cả Thể Loại</a></li>
              <li><a href="{{ route('schedule') }}">Lịch Ra Truyện</a></li>
              <li><a href="{{ route('schedule.completed') }}">Truyện Hoàn Thành</a></li>
              <li><a href="{{ route('originals') }}">Truyện Độc Quyền</a></li>
            </ul>
          </div>
        </div>

        {{-- Column 2: Tài Khoản --}}
        <div class="fgrid-col footer-accordion-item">
          <button type="button" class="fcol-accordion-btn" aria-expanded="false" aria-controls="fcol-collapse-account" id="fcol-btn-account">
            <span class="fcol-heading-text">Tài Khoản</span>
            <span class="fcol-accordion-icon" aria-hidden="true">
              <svg class="icon-plus" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <svg class="icon-minus" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </span>
          </button>
          <div class="fcol-collapse" id="fcol-collapse-account" role="region" aria-labelledby="fcol-btn-account">
            <ul class="fcol-list">
              @guest
                <li><a href="{{ route('login') }}">Đăng Nhập</a></li>
                <li><a href="{{ route('register') }}">Đăng Ký</a></li>
              @else
                <li><a href="{{ route('user.dashboard') }}">Tổng Quan</a></li>
                <li><a href="{{ route('user.library') }}">Tủ Truyện</a></li>
                <li><a href="{{ route('user.history') }}">Lịch Sử</a></li>
                <li><a href="{{ route('user.likes') }}">Yêu Thích</a></li>
                @if(auth()->user()->canAccessAdmin())<li><a href="{{ route('admin.dashboard') }}">Trang Quản Trị</a></li>@endif
              @endguest
            </ul>
          </div>
        </div>

        {{-- Column 3: Hỗ Trợ --}}
        <div class="fgrid-col footer-accordion-item">
          <button type="button" class="fcol-accordion-btn" aria-expanded="false" aria-controls="fcol-collapse-support" id="fcol-btn-support">
            <span class="fcol-heading-text">Hỗ Trợ</span>
            <span class="fcol-accordion-icon" aria-hidden="true">
              <svg class="icon-plus" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <svg class="icon-minus" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </span>
          </button>
          <div class="fcol-collapse" id="fcol-collapse-support" role="region" aria-labelledby="fcol-btn-support">
            <ul class="fcol-list">
              <li><a href="{{ route('pages.about') }}">Giới Thiệu</a></li>
              <li><a href="{{ route('pages.terms') }}">Điều Khoản Sử Dụng</a></li>
              <li><a href="{{ route('pages.privacy') }}">Chính Sách Riêng Tư</a></li>
              <li><a href="{{ route('pages.contact') }}">Liên Hệ</a></li>
              <li><a href="{{ route('dmca.show') }}">Bản Quyền & DMCA</a></li>
              <li><a href="{{ route('teams.index') }}">Danh Sách Nhóm Dịch</a></li>
              <li><a href="{{ route('sitemap') }}">Sitemap</a></li>
            </ul>
          </div>
        </div>
      </div>

      {{-- Desktop Bottom Bar (>= 768px) --}}
      <div class="footer-bottom-bar">
        <p class="fcopy-text">&copy; {{ date('Y') }} {{ $siteSettings['site_name'] ?? 'WebComics' }}. All rights reserved.</p>
        <div class="lang-selector">
          <span class="lang-icon">🌐</span>
          <select class="lang-select" aria-label="Ngôn ngữ">
            <option value="vi" selected>Tiếng Việt</option>
          </select>
        </div>
      </div>

      {{-- Mobile Bottom Bar (< 768px) --}}
      <div class="footer-bottom-mobile">
        <p class="fcopy-text-mobile">&copy; {{ date('Y') }} {{ $siteSettings['site_name'] ?? 'WebComics' }}</p>
        <div class="footer-legal-links-mobile">
          <a href="{{ route('pages.terms') }}">Điều Khoản</a>
          <span class="legal-sep">·</span>
          <a href="{{ route('pages.privacy') }}">Chính Sách Bảo Mật</a>
        </div>
      </div>
    </div>
  </footer>
  <script src="{{ asset('js/app.js') }}?v=6"></script>
  <script src="{{ asset('js/roadmap.js') }}?v=6"></script>
  <script>
    // Inline Mobile Footer Accordion Handler (Guarded against double execution)
    (function() {
      document.addEventListener('click', function(e) {
        var btn = e.target.closest('.fcol-accordion-btn');
        if (!btn) return;
        if (e.__footerAccordionHandled) return;
        e.__footerAccordionHandled = true;

        if (window.matchMedia && window.matchMedia('(min-width: 768px)').matches) return;
        e.preventDefault();

        var item = btn.closest('.footer-accordion-item');
        if (!item) return;

        var willOpen = !item.classList.contains('open');

        document.querySelectorAll('.footer-accordion-item').forEach(function(other) {
          other.classList.remove('open');
          var otherBtn = other.querySelector('.fcol-accordion-btn');
          if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
        });

        if (willOpen) {
          item.classList.add('open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    })();

    @if (app()->environment('local'))
    // Local PWA cleanup: only this application's worker and caches.
    (async () => {
      try {
        if ('serviceWorker' in navigator) {
          const registrations = await navigator.serviceWorker.getRegistrations();
          await Promise.allSettled(registrations.filter(registration =>
            [registration.active, registration.waiting, registration.installing].some(worker => {
              if (!worker) return false;
              const url = new URL(worker.scriptURL);
              return url.origin === window.location.origin && url.pathname === '/sw.js';
            })
          ).map(registration => registration.unregister()));
        }
      } catch (error) {
        console.debug('Local SW cleanup unavailable:', error);
      }
      try {
        if ('caches' in window) {
          const names = await window.caches.keys();
          await Promise.allSettled(names.filter(name => name.startsWith('webcomics-'))
            .map(name => window.caches.delete(name)));
        }
      } catch (error) {
        console.debug('Local PWA cache cleanup unavailable:', error);
      }
    })();
    // End local PWA cleanup.
    @else
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(err => console.log('SW registration failed:', err));
      });
    }
    @endif

    let deferredPrompt;
    const pwaInstallBtn = document.getElementById('pwa-install-btn');
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      if (pwaInstallBtn) pwaInstallBtn.style.display = 'inline-flex';
    });

    pwaInstallBtn?.addEventListener('click', async () => {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') pwaInstallBtn.style.display = 'none';
        deferredPrompt = null;
      }
    });
  </script>
  @stack('scripts')
@include('partials.upload-task-widget')
</body>
</html>
