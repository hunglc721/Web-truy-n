/* WebComics interactions on the Laravel origin.
 * Authentication, authorization and server state are rendered/owned by Laravel.
 */
(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

  // Prototype horizontal carousel, now fed by Blade/DB data.
  $$('.trending-scroll-wrap').forEach((wrap) => {
    const list = $('.trending-list', wrap);
    const left = $('.scroll-left', wrap);
    const right = $('.scroll-right', wrap);
    if (!list) return;

    const amount = () => Math.max(240, Math.floor(list.clientWidth * 0.75));
    left?.addEventListener('click', () => list.scrollBy({ left: -amount(), behavior: 'smooth' }));
    right?.addEventListener('click', () => list.scrollBy({ left: amount(), behavior: 'smooth' }));
  });

  // Live search uses the real Laravel endpoint on the same origin.
  const searchInput = $('#search-input');
  const searchDropdown = $('#search-dropdown');
  let searchTimer = null;
  const closeSearch = () => searchDropdown?.classList.remove('visible');
  const openSearch = () => searchDropdown?.classList.add('visible');

  searchInput?.addEventListener('focus', openSearch);
  searchInput?.addEventListener('input', () => {
    const q = searchInput.value.trim();
    openSearch();
    clearTimeout(searchTimer);

    if (!q) return;

    searchTimer = setTimeout(async () => {
      try {
        const response = await fetch(`/api/search/live?q=${encodeURIComponent(q)}`, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        });
        if (!response.ok) return;

        const data = await response.json();
        const items = Array.isArray(data) ? data : (data.data || data.results || []);
        if (!searchDropdown) return;

        searchDropdown.innerHTML = '';
        if (!items.length) {
          searchDropdown.innerHTML = '<div class="search-recent-title">Không tìm thấy truyện</div>';
          return;
        }

        items.slice(0, 8).forEach((item) => {
          const title = item.title || item.name || '';
          const slug = item.slug || '';
          const el = document.createElement('a');
          el.className = 'search-item';
          el.href = slug ? `/truyen/${encodeURIComponent(slug)}` : '#';
          el.innerHTML = `<span class="search-item-icon">🔎</span>${escapeHtml(title)}`;
          searchDropdown.appendChild(el);
        });
      } catch (_) {
        // Search failure must not break the rest of the page.
      }
    }, 180);
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.search-wrap')) closeSearch();
  });

  // Header auth links are real <a>/<form> elements rendered by Blade.
  // Do not override them with JS redirects or probe protected APIs.

  $('#download-app-btn')?.addEventListener('click', () => {
    const target = $('#download-app-banner');
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  const header = $('#site-header');
  const nav = $('.main-nav');
  if (header && nav) {
    $$('.nav-link', nav).forEach((link) => {
      link.addEventListener('click', () => header.classList.remove('menu-open'));
    });
  }

  // Laravel homepage banner CRUD supplies .banner-slide elements.
  const slides = $$('.banner-slide');
  if (slides.length > 1) {
    let index = 0;
    setInterval(() => {
      slides[index].style.display = 'none';
      index = (index + 1) % slides.length;
      slides[index].style.display = 'block';
    }, 5000);
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
  }
})();
