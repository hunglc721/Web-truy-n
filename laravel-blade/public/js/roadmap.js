(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

  setupMobileMenu();
  setupReaderMobile();
  setupChapterCatalogue();
  setupComicReleaseMeta();
  setupCompletedScheduleLink();

  function setupMobileMenu() {
    const button = $('#mobile-menu-btn');
    const menu = $('#mobile-menu');
    if (!button || !menu) return;

    const setOpen = (open) => {
      menu.classList.toggle('open', open);
      menu.setAttribute('aria-hidden', open ? 'false' : 'true');
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      button.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
      button.textContent = open ? '✕' : '☰';
      document.body.classList.toggle('mobile-menu-open', open);
    };

    button.addEventListener('click', (event) => {
      event.stopPropagation();
      setOpen(!menu.classList.contains('open'));
    });

    menu.addEventListener('click', (event) => event.stopPropagation());
    menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
    document.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') setOpen(false);
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) setOpen(false);
    }, { passive: true });
  }

  function setupReaderMobile() {
    if (!$('.reader-page-wrapper')) return;

    document.body.classList.add('is-reader-page');

    const fitStorageKey = 'webcomics_reader_fit_mode';
    const restoreFitMode = () => {
      if (typeof window.setFitMode !== 'function') return;
      try {
        const savedFit = localStorage.getItem(fitStorageKey);
        if (savedFit === 'fit-width' || savedFit === 'fit-height') {
          window.setFitMode(savedFit, false);
        }
      } catch (_) {}
    };

    $('#btn-fit-width')?.addEventListener('click', () => {
      try { localStorage.setItem(fitStorageKey, 'fit-width'); } catch (_) {}
    });
    $('#btn-fit-height')?.addEventListener('click', () => {
      try { localStorage.setItem(fitStorageKey, 'fit-height'); } catch (_) {}
    });

    const enforceMobileReaderMode = () => {
      if (window.innerWidth > 768) return;
      const isDouble = document.body.classList.contains('reader-layout-double');
      if (isDouble && typeof window.setReadingLayout === 'function') {
        window.setReadingLayout('vertical');
      }
    };

    setTimeout(restoreFitMode, 150);
    setTimeout(enforceMobileReaderMode, 0);
    setTimeout(enforceMobileReaderMode, 180);
    window.addEventListener('resize', enforceMobileReaderMode, { passive: true });
  }

  function getComicSlugFromDetailPath() {
    const match = window.location.pathname.match(/^\/truyen\/([^/]+)\/?$/);
    return match ? decodeURIComponent(match[1]) : null;
  }

  function setupChapterCatalogue() {
    const list = $('#chapter-list');
    const slug = getComicSlugFromDetailPath();
    if (!list || !slug) return;

    const section = $('#detail-tab-chapters') || list.parentElement;
    const existingToolbar = section?.querySelector('.section-title')?.parentElement;
    const searchWrap = document.createElement('div');
    searchWrap.className = 'chapter-catalog-controls';

    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'chapter-catalog-search';
    search.placeholder = 'Tìm chapter, ví dụ: 100 hoặc tên chương...';
    search.setAttribute('aria-label', 'Tìm chapter');

    const status = document.createElement('div');
    status.className = 'chapter-catalog-status';
    status.textContent = 'Đang tải danh sách chapter...';

    const actions = document.createElement('div');
    actions.className = 'chapter-catalog-actions';

    const loadMore = document.createElement('button');
    loadMore.type = 'button';
    loadMore.className = 'chapter-catalog-load-more';
    loadMore.textContent = 'Xem thêm chapter';
    loadMore.hidden = true;
    actions.appendChild(loadMore);

    const anchor = existingToolbar || list;
    anchor.parentElement?.insertBefore(searchWrap, anchor.nextSibling);
    searchWrap.appendChild(search);
    searchWrap.parentElement?.insertBefore(status, list);
    list.parentElement?.insertBefore(actions, list.nextSibling);

    let page = 1;
    let lastPage = 1;
    let sort = 'desc';
    let query = '';
    let debounceTimer = null;
    let controller = null;

    const sortDesc = $('#chap-sort-desc');
    const sortAsc = $('#chap-sort-asc');

    const updateSortButtons = () => {
      sortDesc?.classList.toggle('active', sort === 'desc');
      sortAsc?.classList.toggle('active', sort === 'asc');
    };

    const render = (items, append) => {
      if (!append) list.innerHTML = '';

      if (!items.length && !append) {
        const empty = document.createElement('div');
        empty.className = 'roadmap-empty-state';
        empty.textContent = query ? 'Không tìm thấy chapter phù hợp.' : 'Chưa có chapter nào được phát hành.';
        list.appendChild(empty);
        return;
      }

      items.forEach((chapter) => {
        const link = document.createElement('a');
        link.href = chapter.url;
        link.className = 'browse-card chapter-row';
        link.dataset.chapter = chapter.chapter_number;
        link.style.cssText = 'padding:16px 20px;text-decoration:none;align-items:center;';

        const info = document.createElement('div');
        info.className = 'browse-info';
        info.style.padding = '0';

        const title = document.createElement('h3');
        title.className = 'browse-title';
        title.style.fontSize = '15px';
        title.textContent = `Chương ${chapter.chapter_number}${chapter.title ? ` — ${chapter.title}` : ''}`;

        const meta = document.createElement('p');
        meta.className = 'browse-meta';
        meta.style.margin = '4px 0 0';
        meta.textContent = chapter.time_ago || 'Đã phát hành';

        info.append(title, meta);
        link.appendChild(info);
        list.appendChild(link);
      });
    };

    const fetchPage = async ({ append = false } = {}) => {
      controller?.abort();
      controller = new AbortController();

      if (!append) page = 1;
      status.textContent = 'Đang tải danh sách chapter...';
      loadMore.disabled = true;

      const params = new URLSearchParams({
        page: String(page),
        per_page: '20',
        sort,
      });
      if (query) params.set('q', query);

      try {
        const response = await fetch(`/api/comics/${encodeURIComponent(slug)}/chapters?${params.toString()}`, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          signal: controller.signal,
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const payload = await response.json();
        const items = Array.isArray(payload.data) ? payload.data : [];
        const meta = payload.meta || {};
        lastPage = Number(meta.last_page || 1);

        render(items, append);
        status.textContent = meta.total
          ? `Hiển thị ${meta.to || items.length}/${meta.total} chapter${query ? ` cho “${query}”` : ''}.`
          : (query ? `Không có chapter cho “${query}”.` : 'Chưa có chapter.');
        loadMore.hidden = page >= lastPage;
      } catch (error) {
        if (error.name === 'AbortError') return;
        status.textContent = 'Không tải được danh sách chapter. Danh sách ban đầu vẫn có thể sử dụng.';
        loadMore.hidden = true;
      } finally {
        loadMore.disabled = false;
      }
    };

    search.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      query = search.value.trim();
      debounceTimer = setTimeout(() => fetchPage(), 280);
    });

    sortDesc?.addEventListener('click', (event) => {
      event.preventDefault();
      sort = 'desc';
      updateSortButtons();
      fetchPage();
    });

    sortAsc?.addEventListener('click', (event) => {
      event.preventDefault();
      sort = 'asc';
      updateSortButtons();
      fetchPage();
    });

    loadMore.addEventListener('click', () => {
      if (page >= lastPage) return;
      page += 1;
      fetchPage({ append: true });
    });

    updateSortButtons();
    fetchPage();
  }

  function setupComicReleaseMeta() {
    const slug = getComicSlugFromDetailPath();
    const authorLine = $('.spotlight-author');
    if (!slug || !authorLine) return;

    fetch(`/api/comics/${encodeURIComponent(slug)}/release-meta`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((response) => response.ok ? response.json() : null)
      .then((payload) => {
        const data = payload?.data;
        if (!data) return;

        const meta = document.createElement('div');
        meta.className = 'comic-release-meta';

        if (Number(data.chapter_count) > 0) {
          const chapterCount = document.createElement('span');
          chapterCount.textContent = `📚 ${data.chapter_count} chương đã phát hành`;
          meta.appendChild(chapterCount);
        }

        if (Array.isArray(data.schedules) && data.schedules.length) {
          const schedule = document.createElement('span');
          const names = data.schedules.map((item) => item.day_name).filter(Boolean).join(', ');
          schedule.textContent = `📅 Cập nhật: ${names}`;
          meta.appendChild(schedule);
        }

        if (data.latest_chapter?.time_ago) {
          const latest = document.createElement('a');
          latest.href = data.latest_chapter.url;
          latest.textContent = `🕘 Chapter mới: ${data.latest_chapter.time_ago}`;
          meta.appendChild(latest);
        }

        if (meta.children.length) authorLine.insertAdjacentElement('afterend', meta);

        if (Array.isArray(data.tags)) {
          const tagLinksByName = new Map(data.tags.map((tag) => [String(tag.name).trim().toLowerCase(), tag]));
          $$('.spotlight-tags .orig-tag').forEach((node) => {
            const tag = tagLinksByName.get(node.textContent.trim().toLowerCase());
            if (!tag) return;
            const link = document.createElement('a');
            link.href = tag.url;
            link.className = node.className;
            link.textContent = node.textContent;
            link.style.textDecoration = 'none';
            node.replaceWith(link);
          });
        }
      })
      .catch(() => {});
  }

  function setupCompletedScheduleLink() {
    const bar = $('.schedule-day-bar');
    if (!bar || window.location.pathname !== '/schedule') return;
    if (bar.querySelector('[data-completed-tab]')) return;

    bar.classList.add('has-completed-tab');

    const link = document.createElement('a');
    link.href = '/schedule/completed';
    link.className = 'sched-day-item';
    link.dataset.completedTab = '1';
    link.style.textDecoration = 'none';
    link.innerHTML = '<span class="day-name">✓</span><span class="day-count">COMPLETED</span>';
    bar.appendChild(link);
  }
})();
