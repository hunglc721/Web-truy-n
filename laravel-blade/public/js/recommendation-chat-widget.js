(() => {
  const toggle = document.getElementById('recommendation-chat-toggle');
  const panel = document.getElementById('recommendation-chat-panel');
  const close = document.getElementById('recommendation-chat-close');
  if (!toggle || !panel || !close) return;

  const setOpen = (open) => {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    (open ? close : toggle).focus();
  };
  toggle.addEventListener('click', () => setOpen(panel.hidden));
  close.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !panel.hidden) setOpen(false);
  });
})();
