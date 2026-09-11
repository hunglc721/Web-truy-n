(() => {
  const dialog = document.getElementById('reader-chapter-picker');
  const search = document.getElementById('reader-chapter-search');
  if (!dialog || !search) return;
  const triggers = [...document.querySelectorAll('[data-open-chapter-picker]')];
  const links = [...dialog.querySelectorAll('nav a')];
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/gi, 'd').toLowerCase();
  triggers.forEach(button => button.addEventListener('click', () => {
    search.value = '';
    links.forEach(link => link.hidden = false);
    document.getElementById('reader-picker-empty').hidden = true;
    dialog.showModal();
    triggers.forEach(trigger => trigger.setAttribute('aria-expanded', 'true'));
    dialog.querySelector('[aria-current="page"]')?.scrollIntoView({ block: 'nearest' });
    search.focus({ preventScroll: true });
  }));
  search.addEventListener('input', () => {
    const query = normalize(search.value.trim());
    links.forEach(link => link.hidden = !normalize(link.textContent).includes(query));
    document.getElementById('reader-picker-empty').hidden = links.some(link => !link.hidden);
  });
  document.getElementById('reader-picker-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  dialog.addEventListener('close', () => triggers.forEach(trigger => trigger.setAttribute('aria-expanded', 'false')));
  // Reader shortcuts must not navigate chapters while a modal has focus.
  dialog.addEventListener('keydown', event => event.stopPropagation());
})();
