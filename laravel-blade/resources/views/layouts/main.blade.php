<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', ($siteSettings['site_name'] ?? 'WebComics') . ' - ' . ($siteSettings['tagline'] ?? 'Đọc Manhua, Manhwa & Manga Online'))</title>
  @hasSection('meta')
    @yield('meta')
  @else
    <meta name="description" content="{{ $siteSettings['meta_description'] ?? 'Nền tảng đọc truyện tranh trực tuyến WebComics.' }}" />
    <meta name="keywords" content="{{ $siteSettings['seo_keywords'] ?? 'đọc truyện,manga,manhwa,manhua,webtoon' }}" />
  @endif
  <link rel="manifest" href="{{ asset('manifest.json') }}" />
  <meta name="theme-color" content="#ff5e36" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
  <style>:root{--card-bg:var(--bg-surface-1);--border:var(--border-color)}.footer-static-item{color:var(--text-muted);font-size:13px;display:block;padding:3px 0}</style>
  @stack('styles')
</head>
<body class="dark-theme" data-auth-state="{{ auth()->check() ? (auth()->user()->canAccessAdmin() ? 'admin' : 'member') : 'guest' }}">
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
          <a href="{{ route('schedule') }}" class="nav-link {{ request()->routeIs('schedule') ? 'active' : '' }}">Lịch Ra Truyện</a>
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
        <a href="https://comicscreator.webcomicsapp.com/#/login" target="_blank" rel="noopener" class="header-action-link" id="publish-link" title="Đăng truyện cho tác giả & nhóm dịch">
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
      </div>
    </div>
  </header>

  @if(session('success'))<div class="container" style="padding-top:16px"><div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);color:#4ade80;font-weight:600">{{ session('success') }}</div></div>@endif
  @if(session('error'))<div class="container" style="padding-top:16px"><div style="padding:12px 16px;border-radius:10px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#f87171;font-weight:600">{{ session('error') }}</div></div>@endif

  @yield('content')

  <footer class="site-footer" id="site-footer"><div class="container">
    <div class="footer-newsletter-card"><div class="newsletter-info"><span class="newsletter-tag">🚀 CỘNG ĐỒNG {{ strtoupper($siteSettings['site_name'] ?? 'WEBCOMICS') }}</span><h3 class="newsletter-title">Theo dõi chương mới và truyện nổi bật</h3><p class="newsletter-sub">{{ $siteSettings['tagline'] ?? 'Khám phá truyện mới, lịch phát hành và các tác phẩm đang thịnh hành.' }}</p></div><div class="newsletter-form"><span style="font-size:13px;color:var(--text-sub)">Kênh email chưa được cấu hình, nên không hiện form đăng ký giả.</span></div></div>
    <div class="footer-main-grid">
      <div class="fgrid-brand-col"><a href="{{ route('home') }}" class="logo-link"><div class="logo-icon"><svg width="40" height="40" viewBox="0 0 44 44"><rect width="44" height="44" rx="12" fill="#FF5E36"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="Inter" font-weight="900" font-size="18" fill="white">WC</text></svg></div><span class="logo-text">{{ $siteSettings['site_name'] ?? 'WebComics' }}</span></a><p class="fbrand-desc">{{ $siteSettings['tagline'] ?? 'Nền tảng đọc Manga, Manhwa và Manhua trực tuyến.' }}</p></div>
      <div class="fgrid-col"><h4 class="fcol-heading">Khám Phá</h4><ul class="fcol-list"><li><a href="{{ route('home') }}">Truyện Thịnh Hành</a></li><li><a href="{{ route('genres') }}">Tất Cả Thể Loại</a></li><li><a href="{{ route('schedule') }}">Lịch Ra Truyện</a></li><li><a href="{{ route('originals') }}">Truyện Độc Quyền</a></li></ul></div>
      <div class="fgrid-col"><h4 class="fcol-heading">Tài Khoản</h4>
        <ul class="fcol-list">
          @guest
            <li><a href="{{ route('login') }}">Đăng Nhập</a></li>
            <li><a href="{{ route('register') }}">Đăng Ký</a></li>
          @else
            <li><a href="{{ route('user.dashboard') }}">Tổng Quan</a></li>
            <li><a href="{{ route('user.library') }}">Tủ Truyện</a></li>
            <li><a href="{{ route('user.history') }}">Lịch Sử</a></li>
            <li><a href="{{ route('user.likes') }}">Yêu Thích</a></li>
            @if(auth()->user()->canAccessAdmin())
              <li><a href="{{ route('admin.dashboard') }}">Trang Quản Trị</a></li>
            @endif
          @endguest
        </ul>
      </div>
      <div class="fgrid-col"><h4 class="fcol-heading">Hỗ Trợ</h4><span class="footer-static-item">Điều Khoản Sử Dụng</span><span class="footer-static-item">Chính Sách Riêng Tư</span><a href="{{ route('dmca.show') }}" style="color: var(--text-muted); text-decoration: none; display: block; margin-bottom: 8px;">⚖️ Bản Quyền & DMCA</a><a href="{{ route('teams.index') }}" style="color: var(--text-muted); text-decoration: none; display: block; margin-bottom: 8px;">👥 Danh Sách Nhóm Dịch</a></div>
    </div>
    <div class="footer-bottom-bar"><p class="fcopy-text">&copy; {{ date('Y') }} {{ $siteSettings['site_name'] ?? 'WebComics' }}. All rights reserved.</p><div class="lang-selector"><span class="lang-icon">🌐</span><select class="lang-select" aria-label="Ngôn ngữ"><option value="vi" selected>Tiếng Việt</option></select></div></div>
  </div></footer>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    // PWA Service Worker & Install Prompt Registration
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(err => console.log('SW registration failed:', err));
      });
    }

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
        if (outcome === 'accepted') {
          pwaInstallBtn.style.display = 'none';
        }
        deferredPrompt = null;
      }
    });
  </script>
  @stack('scripts')
</body>
</html>
