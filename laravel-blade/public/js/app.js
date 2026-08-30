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

  // Setup Genre Tabs Scroll & Navigation Buttons
  $$('.genre-tabs-wrapper').forEach((wrap) => {
    const list = $('.genre-tabs', wrap);
    const leftBtn = $('.genre-scroll-left', wrap);
    const rightBtn = $('.genre-scroll-right', wrap);
    if (!list) return;

    const updateButtons = () => {
      const maxScroll = list.scrollWidth - list.clientWidth;
      if (maxScroll <= 5) {
        if (leftBtn) leftBtn.style.display = 'none';
        if (rightBtn) rightBtn.style.display = 'none';
        return;
      }
      if (leftBtn) {
        leftBtn.style.display = 'flex';
        leftBtn.disabled = list.scrollLeft <= 5;
        leftBtn.classList.toggle('disabled', list.scrollLeft <= 5);
      }
      if (rightBtn) {
        rightBtn.style.display = 'flex';
        rightBtn.disabled = list.scrollLeft >= maxScroll - 5;
        rightBtn.classList.toggle('disabled', list.scrollLeft >= maxScroll - 5);
      }
    };

    const scrollAmount = () => Math.max(220, Math.floor(list.clientWidth * 0.65));
    leftBtn?.addEventListener('click', () => {
      list.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
    });
    rightBtn?.addEventListener('click', () => {
      list.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
    });

    list.addEventListener('scroll', updateButtons, { passive: true });
    window.addEventListener('resize', updateButtons, { passive: true });

    // Scroll active item into view
    const activeTab = $('.genre-tab.active', list);
    if (activeTab) {
      activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }

    setTimeout(updateButtons, 80);
  });

  const searchInput = $('#search-input');
  const searchDropdown = $('#search-dropdown');
  let searchTimer = null;
  const closeSearch = () => searchDropdown?.classList.remove('visible');
  const openSearch = () => searchDropdown?.classList.add('visible');

  // Search History Management
  const SEARCH_HISTORY_KEY = 'webcomics_search_history';
  const MAX_SEARCH_HISTORY = 8;

  const getSearchHistory = () => {
    try {
      const raw = localStorage.getItem(SEARCH_HISTORY_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
      return [];
    }
  };

  const saveSearchHistory = (history) => {
    try {
      localStorage.setItem(SEARCH_HISTORY_KEY, JSON.stringify(history.slice(0, MAX_SEARCH_HISTORY)));
    } catch (_) {}
  };

  const addSearchHistory = (term) => {
    const clean = (term || '').trim();
    if (!clean || clean.length < 2) return;
    const history = getSearchHistory().filter(item => item.toLowerCase() !== clean.toLowerCase());
    history.unshift(clean);
    saveSearchHistory(history);
  };

  const removeSearchHistoryItem = (term) => {
    const history = getSearchHistory().filter(item => item !== term);
    saveSearchHistory(history);
  };

  const clearSearchHistory = () => {
    try {
      localStorage.removeItem(SEARCH_HISTORY_KEY);
    } catch (_) {}
  };

  // Render initial dropdown with search history & hot keywords
  const renderInitialSearchDropdown = async () => {
    if (!searchDropdown) return;
    searchDropdown.innerHTML = '';

    const history = getSearchHistory();
    const hasHistory = history.length > 0;

    if (hasHistory) {
      const histSection = document.createElement('div');
      histSection.className = 'search-section';
      histSection.innerHTML = `
        <div class="search-section-header">
          <span class="search-section-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Lịch sử tìm kiếm
          </span>
          <button type="button" class="btn-clear-history" id="clear-all-history-btn">Xoá tất cả</button>
        </div>
        <div class="search-history-chips"></div>
      `;

      const chipsWrap = histSection.querySelector('.search-history-chips');
      history.forEach((keyword) => {
        const chip = document.createElement('div');
        chip.className = 'search-history-chip';
        chip.innerHTML = `
          <span class="history-chip-text" title="${escapeHtml(keyword)}">
            <svg class="history-clock-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            ${escapeHtml(keyword)}
          </span>
          <button type="button" class="history-chip-del" title="Xoá mục này">&times;</button>
        `;

        chip.querySelector('.history-chip-text').addEventListener('click', (e) => {
          e.stopPropagation();
          if (searchInput) searchInput.value = keyword;
          addSearchHistory(keyword);
          window.location.href = `/genres?q=${encodeURIComponent(keyword)}`;
        });

        chip.querySelector('.history-chip-del').addEventListener('click', (e) => {
          e.stopPropagation();
          removeSearchHistoryItem(keyword);
          renderInitialSearchDropdown();
        });

        chipsWrap.appendChild(chip);
      });

      histSection.querySelector('#clear-all-history-btn')?.addEventListener('click', (e) => {
        e.stopPropagation();
        clearSearchHistory();
        renderInitialSearchDropdown();
      });

      searchDropdown.appendChild(histSection);
    }

    // Hot keywords section
    try {
      const res = await fetch('/api/search/hot', { headers: { Accept: 'application/json' } });
      if (res.ok) {
        const data = await res.json();
        const keywords = data.data || [];
        if (keywords.length) {
          const hotSection = document.createElement('div');
          hotSection.className = 'search-section';
          if (hasHistory) {
            hotSection.style.borderTop = '1px solid rgba(255,255,255,0.06)';
            hotSection.style.marginTop = '4px';
            hotSection.style.paddingTop = '6px';
          }
          hotSection.innerHTML = `
            <div class="search-section-header">
              <span class="search-section-title">🔥 TỪ KHOÁ HOT</span>
            </div>
            <div class="search-hot-chips"></div>
          `;
          const hotWrap = hotSection.querySelector('.search-hot-chips');
          keywords.forEach((kw) => {
            const chip = document.createElement('a');
            chip.href = `/genres?q=${encodeURIComponent(kw.keyword)}`;
            chip.className = 'search-hot-chip';
            chip.innerHTML = `<span>🔥</span> ${escapeHtml(kw.keyword)}`;
            chip.addEventListener('click', () => {
              if (searchInput) searchInput.value = kw.keyword;
              addSearchHistory(kw.keyword);
            });
            hotWrap.appendChild(chip);
          });
          searchDropdown.appendChild(hotSection);
        }
      }
    } catch (_) {}
  };

  searchInput?.addEventListener('focus', () => {
    openSearch();
    if (!searchInput.value.trim()) {
      renderInitialSearchDropdown();
    }
  });

  searchInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      const q = searchInput.value.trim();
      if (q) {
        addSearchHistory(q);
        window.location.href = `/genres?q=${encodeURIComponent(q)}`;
      }
    }
  });

  let searchAbortController = null;

  searchInput?.addEventListener('input', () => {
    const q = searchInput.value.trim();
    openSearch();
    clearTimeout(searchTimer);

    if (searchAbortController) {
      searchAbortController.abort();
    }

    if (q.length < 2) {
      if (!q) {
        renderInitialSearchDropdown();
      } else if (searchDropdown) {
        searchDropdown.innerHTML = `<div style="padding:12px;text-align:center;color:var(--text-sub);font-size:13.5px;">Gõ thêm ký tự để tìm kiếm...</div>`;
      }
      return;
    }

    searchTimer = setTimeout(async () => {
      try {
        searchAbortController = new AbortController();
        const response = await fetch(`/api/search/live?q=${encodeURIComponent(q)}`, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
          signal: searchAbortController.signal
        });
        if (!response.ok) return;

        const data = await response.json();
        const items = Array.isArray(data) ? data : (data.data || data.results || []);
        if (!searchDropdown) return;

        searchDropdown.innerHTML = '';
        if (!items.length) {
          searchDropdown.innerHTML = `<div class="search-recent-title" style="padding:12px;text-align:center;color:var(--text-sub);">Không tìm thấy truyện phù hợp cho "<strong>${escapeHtml(q)}</strong>"</div>`;
          return;
        }

        items.slice(0, 7).forEach((item) => {
          const title = item.title || item.name || '';
          const slug = item.slug || '';
          const cover = item.cover_image || '';
          const rating = item.avg_rating ? `★ ${Number(item.avg_rating).toFixed(1)}` : '';
          const el = document.createElement('a');
          el.className = 'search-item';
          el.href = slug ? `/truyen/${encodeURIComponent(slug)}` : '#';
          el.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 12px;text-decoration:none;border-bottom:1px solid rgba(255,255,255,.05);';
          el.innerHTML = `
            ${cover ? `<img src="${escapeHtml(cover)}" style="width:34px;height:46px;object-fit:cover;border-radius:4px;flex-shrink:0;" alt="${escapeHtml(title)}"/>` : '<span class="search-item-icon">📖</span>'}
            <div style="flex:1;min-width:0;">
              <div style="font-weight:700;font-size:13.5px;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(title)}</div>
              <div style="font-size:11.5px;color:var(--text-sub);display:flex;gap:8px;margin-top:2px;">
                ${rating ? `<span style="color:#fbbf24;">${rating}</span>` : ''}
                ${item.country ? `<span style="text-transform:uppercase;">${escapeHtml(item.country)}</span>` : ''}
                ${item.status ? `<span>${escapeHtml(item.status)}</span>` : ''}
              </div>
            </div>
          `;
          el.addEventListener('click', () => {
            if (title) addSearchHistory(title);
          });
          searchDropdown.appendChild(el);
        });

        // Nút xem tất cả kết quả
        const viewAll = document.createElement('a');
        viewAll.href = `/genres?q=${encodeURIComponent(q)}`;
        viewAll.className = 'search-item';
        viewAll.style.cssText = 'display:block;text-align:center;padding:10px;font-size:13px;font-weight:700;color:var(--primary);background:rgba(255,94,54,.08);border-radius:8px;margin-top:4px;';
        viewAll.innerHTML = `Xem tất cả kết quả cho "${escapeHtml(q)}" →`;
        viewAll.addEventListener('click', () => {
          addSearchHistory(q);
        });
        searchDropdown.appendChild(viewAll);
      } catch (err) {
        if (err.name !== 'AbortError') console.error('Live search error:', err);
      }
    }, 350);
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

  // ── HERO BANNER CAROUSEL ─────────────────────────────────────────
  const bannerCarousel = $('#banner-carousel');
  if (bannerCarousel) {
    const slides = $$('.banner-slide', bannerCarousel);
    const dots = $$('.banner-dot', bannerCarousel);
    const prevBtn = $('#banner-prev', bannerCarousel);
    const nextBtn = $('#banner-next', bannerCarousel);
    let currentIndex = 0;
    let autoPlayTimer = null;
    const intervalMs = 2000;

    const goToSlide = (newIndex) => {
      if (!slides.length) return;
      slides[currentIndex]?.classList.remove('active');
      dots[currentIndex]?.classList.remove('active');

      currentIndex = (newIndex + slides.length) % slides.length;

      slides[currentIndex]?.classList.add('active');
      dots[currentIndex]?.classList.add('active');
    };

    const nextSlide = () => goToSlide(currentIndex + 1);
    const prevSlide = () => goToSlide(currentIndex - 1);

    const startAutoPlay = () => {
      stopAutoPlay();
      if (slides.length > 1) {
        autoPlayTimer = setInterval(nextSlide, intervalMs);
      }
    };

    const stopAutoPlay = () => {
      if (autoPlayTimer) {
        clearInterval(autoPlayTimer);
        autoPlayTimer = null;
      }
    };

    nextBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      nextSlide();
      startAutoPlay();
    });

    prevBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      prevSlide();
      startAutoPlay();
    });

    dots.forEach((dot, idx) => {
      dot.addEventListener('click', (e) => {
        e.preventDefault();
        goToSlide(idx);
        startAutoPlay();
      });
    });

    // Pause on hover
    bannerCarousel.addEventListener('mouseenter', stopAutoPlay);
    bannerCarousel.addEventListener('mouseleave', startAutoPlay);

    // Touch / Swipe support
    let touchStartX = 0;
    let touchEndX = 0;
    bannerCarousel.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
      stopAutoPlay();
    }, { passive: true });

    bannerCarousel.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].screenX;
      const diff = touchStartX - touchEndX;
      if (Math.abs(diff) > 40) {
        if (diff > 0) nextSlide();
        else prevSlide();
      }
      startAutoPlay();
    }, { passive: true });

    startAutoPlay();
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
      } catch (_) {}
    }
  }

  function setupGuestContinueReading() {
    const wrap = document.getElementById('guest-continue-reading');
    const container = document.getElementById('guest-history-cards');
    if (!wrap || !container) return;

    try {
      const historyRaw = localStorage.getItem('webcomics_guest_history');
      if (!historyRaw) return;
      const history = JSON.parse(historyRaw);
      if (!Array.isArray(history) || !history.length) return;

      container.innerHTML = '';
      history.slice(0, 4).forEach(item => {
        const percent = Math.max(5, Math.min(100, Math.round(item.percent || 0)));
        const card = document.createElement('div');
        card.style.cssText = 'background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:12px;padding:12px;display:flex;gap:12px;align-items:center;position:relative;overflow:hidden;';
        card.innerHTML = `
          <a href="${escapeAttribute(item.url)}" style="flex-shrink:0;">
            <img src="${escapeAttribute(item.cover)}" alt="${escapeAttribute(item.title)}" style="width:58px;height:78px;object-fit:cover;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.4);" loading="lazy" />
          </a>
          <div style="flex:1;min-width:0;">
            <h3 style="font-size:14px;font-weight:700;color:#fff;margin:0 0 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <a href="${escapeAttribute(item.comicUrl || '#')}" style="color:inherit;text-decoration:none;">${escapeHtml(item.title)}</a>
            </h3>
            <div style="font-size:12px;color:var(--text-sub);margin-bottom:6px;">
              Đang đọc: <strong style="color:var(--primary);">${escapeHtml(item.chapterTitle || 'Ch.' + item.chapterNum)}</strong>
            </div>
            <div style="height:6px;background:rgba(255,255,255,.08);border-radius:999px;overflow:hidden;margin-bottom:6px;">
              <div style="height:100%;width:${percent}%;background:linear-gradient(90deg,#ff5e36,#ff2a6d);border-radius:999px;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:11px;color:var(--text-sub);">Tiến độ: ${percent}%</span>
              <a href="${escapeAttribute(item.url)}" class="btn-sm" style="font-size:11px;padding:3px 8px;background:var(--primary);color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">Đọc tiếp →</a>
            </div>
          </div>
        `;
        container.appendChild(card);
      });
      wrap.style.display = 'block';
    } catch (_) {}
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

  // Khởi chạy
  setupGuestContinueReading();
})();