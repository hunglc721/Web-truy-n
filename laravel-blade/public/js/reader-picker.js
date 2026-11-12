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
  // Arrow keys move through the chapter list so the picker is usable without a mouse.
  dialog.addEventListener('keydown', event => {
    event.stopPropagation();
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
    const visible = links.filter(link => !link.hidden);
    if (!visible.length) return;
    event.preventDefault();
    const currentIndex = visible.indexOf(document.activeElement);
    let nextIndex;
    if (event.key === 'ArrowDown') {
      nextIndex = currentIndex < 0 || currentIndex >= visible.length - 1 ? 0 : currentIndex + 1;
    } else {
      nextIndex = currentIndex <= 0 ? visible.length - 1 : currentIndex - 1;
    }
    visible[nextIndex].focus();
  });
})();
