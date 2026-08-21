/* WebComics interactions on the Laravel origin.
 * Authentication, authorization and server state are rendered/owned by Laravel.
 */
(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const csrf = $('meta[name="csrf-token"]')?.content || '';

  installNotificationStyles();
  loadPublicAnnouncements();
  setupNotificationBell();

  $$('.trending-scroll-wrap').forEach((wrap) => {
    const list = $('.trending-list', wrap);
    const left = $('.scroll-left', wrap);
    const right = $('.scroll-right', wrap);
    if (!list) return;

    const amount = () => Math.max(240, Math.floor(list.clientWidth * 0.75));
    left?.addEventListener('click', () => list.scrollBy({ left: -amount(), behavior: 'smooth' }));
    right?.addEventListener('click', () => list.scrollBy({ left: amount(), behavior: 'smooth' }));
  });

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
      } catch (_) {}
    }, 180);
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.search-wrap')) closeSearch();
    if (!event.target.closest('.wc-notification-wrap')) {
      $('.wc-notification-dropdown')?.classList.remove('open');
    }
  });

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

  const slides = $$('.banner-slide');
  if (slides.length > 1) {
    let index = 0;
    setInterval(() => {
      slides[index].style.display = 'none';
      index = (index + 1) % slides.length;
      slides[index].style.display = 'block';
    }, 5000);
  }

  async function loadPublicAnnouncements() {
    try {
      const response = await fetch('/api/announcements/active', {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      if (!response.ok) return;

      const data = await response.json();
      const announcements = data.announcements || [];
      if (!announcements.length) return;

      let stack = $('#wc-announcement-stack');
      if (!stack) {
        stack = document.createElement('div');
        stack.id = 'wc-announcement-stack';
        document.body.appendChild(stack);
      }

      announcements.forEach((notice) => {
        const item = document.createElement('section');
        item.className = `wc-announcement wc-announcement-${notice.severity || 'info'}`;
        item.dataset.id = notice.id;

        const icon = notice.severity === 'emergency' ? '🚨' : notice.severity === 'warning' ? '⚠️' : notice.severity === 'success' ? '✅' : '🔔';
        const link = notice.link_url
          ? `<a class="wc-announcement-link" href="${escapeAttribute(notice.link_url)}">Xem chi tiết</a>`
          : '';
        const close = notice.is_dismissible
          ? '<button type="button" class="wc-announcement-close" aria-label="Đóng">×</button>'
          : '';

        item.innerHTML = `<div class="wc-announcement-icon">${icon}</div><div class="wc-announcement-copy"><strong>${escapeHtml(notice.title || 'Thông báo')}</strong><p>${escapeHtml(notice.message || '')}</p>${link}</div>${close}`;
        stack.appendChild(item);

        $('.wc-announcement-close', item)?.addEventListener('click', async () => {
          try {
            await fetch(notice.dismiss_url, {
              method: 'POST',
              headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
              },
              credentials: 'same-origin',
            });
          } finally {
            item.remove();
          }
        });
      });
    } catch (_) {}
  }

  async function setupNotificationBell() {
    if (document.body?.dataset.authState === 'guest') return;
    const group = $('.nav-icon-group');
    if (!group) return;

    const wrap = document.createElement('div');
    wrap.className = 'wc-notification-wrap';
    wrap.innerHTML = `
      <button type="button" class="icon-btn wc-notification-bell" aria-label="Thông báo" title="Thông báo">
        <span aria-hidden="true">🔔</span><span class="wc-notification-badge" hidden>0</span>
      </button>
      <div class="wc-notification-dropdown"><div class="wc-notification-loading">Đang tải thông báo...</div></div>`;
    group.prepend(wrap);

    const bell = $('.wc-notification-bell', wrap);
    const dropdown = $('.wc-notification-dropdown', wrap);
    const badge = $('.wc-notification-badge', wrap);
    let loaded = false;

    bell?.addEventListener('click', async (event) => {
      event.stopPropagation();
      dropdown.classList.toggle('open');
      if (!loaded && dropdown.classList.contains('open')) {
        await refreshNotifications();
        loaded = true;
      }
    });

    await refreshNotifications(true);

    async function refreshNotifications(badgeOnly = false) {
      try {
        const response = await fetch('/user/notifications/header', {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        });
        if (!response.ok) return;
        const data = await response.json();
        const count = Number(data.unread_count || 0);
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.hidden = count < 1;
        if (badgeOnly) return;

        const items = data.notifications || [];
        dropdown.innerHTML = '<div class="wc-notification-head"><strong>Thông báo</strong></div>';
        if (!items.length) {
          dropdown.innerHTML += '<div class="wc-notification-empty">Chưa có thông báo.</div>';
        } else {
          items.forEach((item) => {
            const note = document.createElement('a');
            note.href = item.open_url;
            note.className = `wc-notification-item${item.read_at ? '' : ' unread'}`;
            note.innerHTML = `<span class="wc-notification-item-icon">${escapeHtml(item.data?.icon || '🔔')}</span><span><strong>${escapeHtml(item.data?.title || 'Thông báo')}</strong><small>${escapeHtml(item.data?.message || '')}</small><em>${escapeHtml(item.created_at || '')}</em></span>`;
            dropdown.appendChild(note);
          });
        }
        const all = document.createElement('a');
        all.href = data.all_url || '/user/notifications';
        all.className = 'wc-notification-all';
        all.textContent = 'Xem tất cả thông báo';
        dropdown.appendChild(all);
      } catch (_) {
        if (!badgeOnly) dropdown.innerHTML = '<div class="wc-notification-empty">Không tải được thông báo.</div>';
      }
    }
  }

  function installNotificationStyles() {
    if ($('#wc-notification-styles')) return;
    const style = document.createElement('style');
    style.id = 'wc-notification-styles';
    style.textContent = `
      #wc-announcement-stack{position:fixed;top:78px;left:50%;transform:translateX(-50%);width:min(760px,calc(100vw - 24px));z-index:9998;display:flex;flex-direction:column;gap:10px;pointer-events:none}
      .wc-announcement{pointer-events:auto;display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-radius:13px;background:#171b25;border:1px solid rgba(255,255,255,.12);box-shadow:0 14px 45px rgba(0,0,0,.45);color:#fff}.wc-announcement-warning{border-color:rgba(245,158,11,.65);background:#241d10}.wc-announcement-emergency{border-color:rgba(239,68,68,.85);background:#2a1115;box-shadow:0 14px 55px rgba(239,68,68,.22)}.wc-announcement-success{border-color:rgba(34,197,94,.55);background:#102219}.wc-announcement-icon{font-size:22px}.wc-announcement-copy{flex:1;min-width:0}.wc-announcement-copy strong{font-size:14px}.wc-announcement-copy p{font-size:12.5px;line-height:1.5;color:#c6cad6;margin:4px 0 0;white-space:pre-line}.wc-announcement-link{display:inline-block;margin-top:7px;color:#fff;font-size:12px;font-weight:800}.wc-announcement-close{border:0;background:transparent;color:#aab0c0;font-size:22px;cursor:pointer;line-height:1}
      .wc-notification-wrap{position:relative}.wc-notification-bell{position:relative}.wc-notification-badge{position:absolute;top:-5px;right:-6px;min-width:17px;height:17px;padding:0 4px;border-radius:999px;background:#ff2a6d;color:#fff;font:800 9px/17px Inter,sans-serif;text-align:center}.wc-notification-dropdown{display:none;position:absolute;top:calc(100% + 12px);right:0;width:min(380px,calc(100vw - 24px));max-height:480px;overflow:auto;background:#131722;border:1px solid rgba(255,255,255,.1);border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.55);z-index:9999}.wc-notification-dropdown.open{display:block}.wc-notification-head{padding:13px 15px;border-bottom:1px solid rgba(255,255,255,.08);color:#fff}.wc-notification-item{display:flex;gap:10px;padding:12px 14px;text-decoration:none;border-bottom:1px solid rgba(255,255,255,.055);color:#fff}.wc-notification-item.unread{background:rgba(255,94,54,.08)}.wc-notification-item:hover{background:rgba(255,255,255,.05)}.wc-notification-item-icon{font-size:19px}.wc-notification-item>span:last-child{display:flex;min-width:0;flex-direction:column;gap:3px}.wc-notification-item strong{font-size:12.5px}.wc-notification-item small{font-size:11.5px;color:#aeb4c4;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.wc-notification-item em{font-size:10px;color:#737b91;font-style:normal}.wc-notification-all{display:block;padding:11px 14px;text-align:center;color:#ff7a59;font-size:12px;font-weight:800;text-decoration:none}.wc-notification-empty,.wc-notification-loading{padding:24px 14px;text-align:center;color:#8d95a8;font-size:12px}
      @media(max-width:700px){#wc-announcement-stack{top:66px}.wc-announcement{padding:12px}.wc-notification-dropdown{position:fixed;top:68px;right:12px}}
    `;
    document.head.appendChild(style);
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#96;');
  }
})();