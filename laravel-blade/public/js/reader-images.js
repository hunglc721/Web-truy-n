(() => {
  'use strict';
  const images = [...document.querySelectorAll('.comic-page-img')];
  let pending = [];
  let active = 0;
  let mode = 'vertical';
  let current = 0;
  const started = new WeakSet();

  function sizes(img) {
    const wrapper = img.closest('.comic-page-wrapper');
    const width = wrapper?.getBoundingClientRect().width || Math.min(innerWidth, 800);
    img.sizes = `${Math.ceil(width)}px`;
  }

  function pump() {
    while (active < 2 && pending.length) {
      const img = pending.shift();
      if (started.has(img)) continue;
      started.add(img);
      active++;
      let finished = false;
      const done = () => {
        if (finished) return;
        finished = true;
        active--;
        img.removeEventListener('load', done);
        img.removeEventListener('error', done);
        pump();
      };
      img.addEventListener('load', done);
      img.addEventListener('error', done);
      sizes(img);
      // Activate the real DOM image only; no prefetch link or duplicate Image() download.
      img.loading = 'eager';
      img.closest('picture')?.querySelectorAll('source[data-srcset]').forEach(source => {
        source.srcset = source.dataset.srcset;
        delete source.dataset.srcset;
      });
      if (img.dataset.srcset) img.srcset = img.dataset.srcset;
      if (img.dataset.src) img.src = img.dataset.src;
      delete img.dataset.src;
      delete img.dataset.srcset;
      if (img.complete && img.naturalWidth) done();
    }
  }

  function show(index, layout = mode) {
    mode = layout;
    current = Math.max(0, index);
    const visible = mode === 'double' ? 2 : 1;
    const lookahead = navigator.connection?.saveData ? 1 : 3;
    // Replace unstarted work after jumps instead of building an unbounded queue.
    pending = images.slice(current, current + visible + lookahead).filter(img => !started.has(img));
    pump();
  }

  // First two images are fetched by the parser, with the first at high priority.
  images.slice(0, 2).forEach(img => started.add(img));
  window.readerImages = { show, resize: () => images.filter(img => !img.dataset.src && img.getClientRects().length).forEach(sizes) };
  const container = document.getElementById('reader-container');
  if (container && 'ResizeObserver' in window) {
    new ResizeObserver(() => window.readerImages.resize()).observe(container);
  }
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      if (mode !== 'vertical') return;
      const visible = entries.filter(e => e.isIntersecting);
      if (visible.length) {
        const nearest = visible.sort((a, b) => Math.abs(a.boundingClientRect.top) - Math.abs(b.boundingClientRect.top))[0];
        show(Number(nearest.target.dataset.readerIndex), 'vertical');
      }
    }, { rootMargin: '150px 0px', threshold: 0.01 });
    images.forEach((img, i) => {
      const wrapper = img.closest('.comic-page-wrapper');
      wrapper.dataset.readerIndex = i;
      observer.observe(wrapper);
    });
  } else {
    window.addEventListener('scroll', () => {
      if (mode !== 'vertical') return;
      const index = images.findIndex(img => img.closest('.comic-page-wrapper').getBoundingClientRect().bottom > 0);
      show(Math.max(index, 0));
    }, { passive: true });
  }
  window.addEventListener('resize', () => window.readerImages.resize());
})();
