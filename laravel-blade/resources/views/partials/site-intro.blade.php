<section
  class="site-intro"
  id="site-intro"
  data-site-intro
  data-testid="site-intro"
  data-storage-key="comicx:site-intro:v1"
  data-duration="3000"
  data-state="idle"
  role="dialog"
  aria-modal="true"
  aria-hidden="true"
  aria-labelledby="site-intro-title"
  hidden
>
  <div class="site-intro__backdrop" aria-hidden="true">
    <span class="site-intro__orb site-intro__orb--one"></span>
    <span class="site-intro__orb site-intro__orb--two"></span>
    <span class="site-intro__halftone"></span>
    <span class="site-intro__speed-lines"></span>
  </div>

  <div class="site-intro__panel site-intro__panel--left" aria-hidden="true">
    <span class="site-intro__panel-index">01</span>
    <strong>WOW!</strong>
    <small>Plot twist<br>đang tới.</small>
  </div>
  <div class="site-intro__panel site-intro__panel--right" aria-hidden="true">
    <span class="site-intro__panel-index">∞</span>
    <strong>Next<br>chapter</strong>
    <small>Chưa thể dừng.</small>
  </div>

  <button class="site-intro__skip" type="button" data-intro-dismiss aria-label="Bỏ qua intro">
    <span>Bỏ qua</span>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </button>

  <div class="site-intro__content">
    <div class="site-intro__eyebrow">
      <span class="site-intro__eyebrow-line"></span>
      <span>Welcome to your next obsession</span>
      <span class="site-intro__eyebrow-line"></span>
    </div>

    <div class="site-intro__brand-mark" aria-hidden="true">
      @if($siteSettings['site_logo_url'] ?? null)
        <img src="{{ $siteSettings['site_logo_url'] }}" alt="" width="84" height="84" />
      @else
        <svg width="84" height="84" viewBox="0 0 84 84" fill="none">
          <rect x="2" y="2" width="80" height="80" rx="24" fill="url(#intro-logo-gradient)" stroke="rgba(255,255,255,.28)" stroke-width="4"/>
          <path d="M20 27L27 58L42 38L57 58L64 27" stroke="white" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
          <defs>
            <linearGradient id="intro-logo-gradient" x1="8" y1="5" x2="76" y2="79" gradientUnits="userSpaceOnUse">
              <stop stop-color="#FF8A3D"/>
              <stop offset=".48" stop-color="#FF3D67"/>
              <stop offset="1" stop-color="#8B5CF6"/>
            </linearGradient>
          </defs>
        </svg>
      @endif
    </div>

    <h1 class="site-intro__title" id="site-intro-title">
      <span class="site-intro__title-small">Mở trang.</span>
      <span class="site-intro__title-brand">{{ $siteSettings['site_name'] ?? 'WebComics' }}</span>
      <span class="site-intro__title-accent">Bật mood.</span>
    </h1>

    <p class="site-intro__manifesto">Một cú lướt. Cả thế giới bật mở.</p>
    <div class="site-intro__genres" aria-label="Thể loại truyện">
      <span>Manga</span><i></i><span>Manhwa</span><i></i><span>Manhua</span>
    </div>
  </div>

  <div class="site-intro__footer" aria-hidden="true">
    <div class="site-intro__progress"><span></span></div>
    <div class="site-intro__footer-meta">
      <span>Scroll into another universe</span>
      <span>ESC để bỏ qua</span>
    </div>
  </div>
</section>
