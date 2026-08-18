<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  {{-- ✅ Mỗi trang tự định nghĩa <title> riêng qua @section('title') --}}
  <title>@yield('title', 'WebComics - Read Best Manhua, Manhwa & Manga Online For Free')</title>

  {{-- ✅ Mỗi trang có thể thêm meta riêng qua @section('meta') --}}
  @yield('meta')

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

  {{-- ✅ asset() trỏ đến thư mục public/ của Laravel --}}
  <link rel="stylesheet" href="{{ asset('css/style.css') }}" />

  {{-- ✅ Cho phép các trang con thêm CSS riêng --}}
  @stack('styles')
</head>
<body class="dark-theme">

  {{-- ===================== HEADER ===================== --}}
  <header class="site-header" id="site-header">
    <div class="header-inner">
      <div class="header-left">
        <a href="{{ route('home') }}" class="logo-link" aria-label="WebComics Home">
          <div class="logo-icon">
            <svg width="42" height="42" viewBox="0 0 44 44" fill="none">
              <rect width="44" height="44" rx="12" fill="url(#logo-grad)"/>
              <defs>
                <linearGradient id="logo-grad" x1="0" y1="0" x2="44" y2="44">
                  <stop offset="0%" stop-color="#FF5E36"/>
                  <stop offset="100%" stop-color="#FF2A6D"/>
                </linearGradient>
              </defs>
              <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="Inter" font-weight="900" font-size="18" fill="white">WC</text>
            </svg>
          </div>
          <span class="logo-text">WebComics</span>
        </a>

        {{-- Nav: dùng request()->routeIs() để tự động active đúng link --}}
        <nav class="main-nav" aria-label="Main navigation">
          <a href="{{ route('home') }}"
             class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
             id="nav-home">Home</a>
          <a href="{{ route('genres') }}"
             class="nav-link {{ request()->routeIs('genres') ? 'active' : '' }}"
             id="nav-genres">Genres</a>
          <a href="{{ route('schedule') }}"
             class="nav-link {{ request()->routeIs('schedule') ? 'active' : '' }}"
             id="nav-schedule">Schedule</a>
          <a href="{{ route('originals') }}"
             class="nav-link {{ request()->routeIs('originals') ? 'active' : '' }}"
             id="nav-originals">Originals</a>
        </nav>
      </div>

      <div class="header-right">
        <div class="search-wrap">
          <input id="search-input" type="search" placeholder="Search comics..."
                 aria-label="Search" class="search-input" autocomplete="off" />
          <span class="search-icon" aria-hidden="true">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <div class="search-dropdown" id="search-dropdown">
            <div class="search-recent-title">Trending Searches</div>
            <div class="search-item"><span class="search-item-icon">🔥</span>Solo Leveling</div>
            <div class="search-item"><span class="search-item-icon">⚔️</span>Tower of God</div>
            <div class="search-item"><span class="search-item-icon">💜</span>Omniscient Reader</div>
            <div class="search-item"><span class="search-item-icon">🌺</span>Lore Olympus</div>
            <div class="search-item"><span class="search-item-icon">⚔️</span>Demon Slayer</div>
          </div>
        </div>

        <a href="https://comicscreator.webcomicsapp.com/#/login" target="_blank"
           class="header-action-link" id="publish-link">Publish</a>
        <div class="header-divider"></div>

        <div class="nav-icon-group">
          <button class="icon-btn" aria-label="Library" id="library-btn" title="Library">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
          </button>
          <button class="icon-btn store-btn" aria-label="Store" id="store-btn" title="Store">
            <span class="bonus-badge">+Bonus</span>
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
              <line x1="3" y1="6" x2="21" y2="6"/>
              <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
          </button>
        </div>

        @auth
          @if(auth()->user()->is_admin ?? false)
            <a href="{{ route('admin.comics.index') }}" class="btn btn-login" style="background: var(--primary); text-decoration: none;">
              🛡️ Admin Dashboard
            </a>
          @endif
        @endauth

        <button class="btn btn-login" id="login-btn">Log in</button>
        <button class="btn btn-download" id="download-app-btn">
          Download App
          <svg class="dropdown-arrow-icon" width="10" height="7" viewBox="0 0 10 7" fill="currentColor">
            <polygon points="0,0 10,0 5,7"/>
          </svg>
        </button>
      </div>
    </div>
  </header>
  {{-- ===================== END HEADER ===================== --}}

  {{-- ✅ Nội dung từng trang sẽ được chèn vào đây --}}
  @yield('content')

  {{-- ===================== FOOTER ===================== --}}
  <footer class="site-footer" id="site-footer">
    <div class="container">

      {{-- FOOTER NEWSLETTER CARD --}}
      <div class="footer-newsletter-card">
        <div class="newsletter-info">
          <span class="newsletter-tag">🚀 JOIN OUR COMMUNITY</span>
          <h3 class="newsletter-title">Subscribe for New Releases &amp; Exclusive Bonuses</h3>
          <p class="newsletter-sub">Get weekly chapter alerts, creator spotlights, and bonus coin rewards.</p>
        </div>
        <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Thank you for subscribing to WebComics updates!');">
          <input type="email" placeholder="Enter your email address..." required class="newsletter-input" />
          <button type="submit" class="btn-newsletter-sub">Subscribe Free</button>
        </form>
      </div>

      {{-- FOOTER MAIN GRID --}}
      <div class="footer-main-grid">

        <div class="fgrid-brand-col">
          <a href="{{ route('home') }}" class="logo-link" aria-label="WebComics Home">
            <div class="logo-icon">
              <svg width="40" height="40" viewBox="0 0 44 44" fill="none">
                <rect width="44" height="44" rx="12" fill="url(#footer-logo-grad2)"/>
                <defs>
                  <linearGradient id="footer-logo-grad2" x1="0" y1="0" x2="44" y2="44">
                    <stop offset="0%" stop-color="#FF5E36"/>
                    <stop offset="100%" stop-color="#FF2A6D"/>
                  </linearGradient>
                </defs>
                <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="Inter" font-weight="900" font-size="18" fill="white">WC</text>
              </svg>
            </div>
            <span class="logo-text">WebComics</span>
          </a>
          <p class="fbrand-desc">Official platform for the best free Webtoons, Manhua &amp; Manga online.</p>
          <div class="fbrand-badges">
            <span class="fbadge-pill">★ 4.8 Rating</span>
            <span class="fbadge-pill">10M+ Readers</span>
          </div>
        </div>

        <div class="fgrid-col">
          <h4 class="fcol-heading">Discover</h4>
          <ul class="fcol-list">
            <li><a href="{{ route('home') }}">Trending Series</a></li>
            <li><a href="{{ route('genres') }}">All Genres</a></li>
            <li><a href="{{ route('schedule') }}">Release Schedule</a></li>
            <li><a href="{{ route('originals') }}">WebComics Originals</a></li>
            <li><a href="{{ route('genres') }}">Popular Action</a></li>
            <li><a href="{{ route('genres') }}">Top Romance</a></li>
          </ul>
        </div>

        <div class="fgrid-col">
          <h4 class="fcol-heading">For Creators</h4>
          <ul class="fcol-list">
            <li><a href="https://comicscreator.webcomicsapp.com/#/login" target="_blank">Publish Your Comic</a></li>
            <li><a href="#">Creator Portal</a></li>
            <li><a href="#">Guild Guidelines</a></li>
            <li><a href="#">Monetization Program</a></li>
            <li><a href="#">WebComics Contest 2024</a></li>
          </ul>
        </div>

        <div class="fgrid-col">
          <h4 class="fcol-heading">Company</h4>
          <ul class="fcol-list">
            <li><a href="#">About Us</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Press &amp; Media</a></li>
            <li><a href="#">Community Hub</a></li>
            <li><a href="#">Download Mobile App</a></li>
          </ul>
        </div>

        <div class="fgrid-col">
          <h4 class="fcol-heading">Support &amp; Legal</h4>
          <ul class="fcol-list">
            <li><a href="#">Terms of Service</a></li>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Copyright &amp; DMCA</a></li>
            <li><a href="#">Content Guidelines</a></li>
            <li><a href="#">Help Center / FAQ</a></li>
          </ul>
        </div>

      </div>

      {{-- FOOTER BOTTOM BAR --}}
      <div class="footer-bottom-bar">
        <div class="fbottom-left">
          <p class="fcopy-text">&copy; {{ date('Y') }} WebComics, Inc. All rights reserved.</p>
        </div>
        <div class="fbottom-right">
          <div class="lang-selector">
            <span class="lang-icon">🌐</span>
            <select class="lang-select" aria-label="Select Language">
              <option value="en" selected>English (US)</option>
              <option value="vi">Tiếng Việt</option>
              <option value="fr">Français</option>
              <option value="es">Español</option>
              <option value="id">Bahasa Indonesia</option>
            </select>
          </div>
          <div class="fsocial-links">
            <a href="#" class="fsocial-icon" aria-label="Facebook">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="#" class="fsocial-icon" aria-label="Twitter">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.936 9.936 0 0024 4.59z"/></svg>
            </a>
            <a href="#" class="fsocial-icon" aria-label="Instagram">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </a>
            <a href="#" class="fsocial-icon" aria-label="Discord">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.061 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.893.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
            </a>
          </div>
        </div>
      </div>

    </div>
  </footer>
  {{-- ===================== END FOOTER ===================== --}}

  <script src="{{ asset('js/app.js') }}"></script>
  @stack('scripts')

</body>
</html>
