(() => {
  'use strict';

  const intro = document.querySelector('[data-site-intro]');
  if (!intro) return;

  const dismissButton = intro.querySelector('[data-intro-dismiss]');
  const storageKey = intro.dataset.storageKey || 'comicx:site-intro:v1';
  const fallbackCookie = 'comicx_site_intro_seen';
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const requestedDuration = Number.parseInt(intro.dataset.duration || '', 10);
  const duration = reducedMotion ? 700 : (Number.isFinite(requestedDuration) ? requestedDuration : 3000);
  const backgroundStates = new Map();
  let dismissTimer;
  let hideTimer;
  let dismissed = false;

  const announceDismissed = () => {
    document.dispatchEvent(new CustomEvent('comicx:site-intro-dismissed'));
  };

  const hasFallbackCookie = () => document.cookie
    .split(';')
    .some(cookie => cookie.trim() === `${fallbackCookie}=1`);

  const storage = {
    get() {
      try {
        return window.sessionStorage.getItem(storageKey);
      } catch (_) {
        return window.__comicxIntroSeen || hasFallbackCookie() ? '1' : null;
      }
    },
    set() {
      window.__comicxIntroSeen = true;
      try {
        window.sessionStorage.setItem(storageKey, '1');
      } catch (_) {
        try {
          document.cookie = `${fallbackCookie}=1; path=/; SameSite=Lax`;
        } catch (_) {
          // The in-memory fallback still prevents duplicate initialization on this page.
        }
      }
    },
  };

  const cleanup = () => {
    window.clearTimeout(dismissTimer);
    window.clearTimeout(hideTimer);
    document.removeEventListener('keydown', handleKeydown);
  };

  const lockBackground = () => {
    [...document.body.children].forEach((element) => {
      if (element === intro || !(element instanceof HTMLElement)) return;
      backgroundStates.set(element, element.getAttribute('aria-hidden'));
      element.setAttribute('aria-hidden', 'true');
    });
  };

  const unlockBackground = () => {
    backgroundStates.forEach((previousValue, element) => {
      if (previousValue === null) element.removeAttribute('aria-hidden');
      else element.setAttribute('aria-hidden', previousValue);
    });
    backgroundStates.clear();
  };

  const finish = () => {
    if (dismissed) return;
    dismissed = true;
    window.clearTimeout(dismissTimer);
    intro.dataset.state = 'leaving';
    intro.classList.add('is-leaving');
    document.body.classList.remove('site-intro-open');
    unlockBackground();
    document.querySelector('#site-header .logo-link')?.focus({ preventScroll: true });
    intro.setAttribute('aria-hidden', 'true');

    hideTimer = window.setTimeout(() => {
      intro.hidden = true;
      intro.classList.remove('is-active', 'is-leaving');
      intro.dataset.state = 'dismissed';
      cleanup();
      announceDismissed();
    }, reducedMotion ? 0 : 720);
  };

  function handleKeydown(event) {
    if (event.key === 'Escape') {
      event.preventDefault();
      finish();
      return;
    }

    if (event.key === 'Tab') {
      event.preventDefault();
      dismissButton?.focus({ preventScroll: true });
    }
  }

  if (storage.get() === '1') {
    intro.dataset.state = 'dismissed';
    announceDismissed();
    return;
  }

  storage.set();
  intro.hidden = false;
  intro.setAttribute('aria-hidden', 'false');
  intro.dataset.state = 'entering';
  document.body.classList.add('site-intro-open');
  lockBackground();
  document.addEventListener('keydown', handleKeydown);
  dismissButton?.addEventListener('click', finish, { once: true });

  // This script is deferred, so activate before DOMContentLoaded. That removes a
  // one-frame gap where controls behind the modal could receive focus.
  intro.classList.add('is-active');
  intro.dataset.state = 'visible';
  dismissButton?.focus({ preventScroll: true });

  dismissTimer = window.setTimeout(finish, duration);
  window.addEventListener('pagehide', () => {
    intro.hidden = true;
    document.body.classList.remove('site-intro-open');
    unlockBackground();
    cleanup();
  }, { once: true });
})();
