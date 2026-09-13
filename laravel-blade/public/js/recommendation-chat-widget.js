(() => {
  const toggle = document.getElementById('recommendation-chat-toggle');
  const panel = document.getElementById('recommendation-chat-panel');
  const close = document.getElementById('recommendation-chat-close');
  if (!toggle || !panel || !close) return;

  const widget = document.getElementById('recommendation-chat-widget');
  const form = document.getElementById('recommendation-chat-form');
  const input = document.getElementById('recommendation-chat-input');
  const sendButton = document.getElementById('recommendation-chat-send');
  const messages = document.getElementById('recommendation-chat-messages');
  const chips = document.getElementById('recommendation-chat-chips');
  const loading = document.getElementById('recommendation-chat-loading');
  const error = document.getElementById('recommendation-chat-error');
  const storageKey = 'comicx.recommendation.conversation';
  const defaults = ['Action', 'Fantasy', 'Main OP', 'Weak → Strong', 'No Romance', 'Completed'];
  let token = null;
  let busy = false;
  let failedSubmission = null;
  try { token = sessionStorage.getItem(storageKey); } catch (_) { /* Storage may be disabled. */ }
  if (token && !/^[a-f0-9]{64}$/.test(token)) token = null;

  const saveToken = (value) => {
    token = typeof value === 'string' && /^[a-f0-9]{64}$/.test(value) ? value : null;
    try {
      if (token) sessionStorage.setItem(storageKey, token);
      else sessionStorage.removeItem(storageKey);
    } catch (_) { /* Keep the in-memory token when storage is unavailable. */ }
  };
  const bubble = (text, role) => {
    const element = document.createElement('p');
    element.className = `recommendation-chat-bubble recommendation-chat-${role}`;
    element.textContent = text;
    messages.append(element);
    messages.scrollTop = messages.scrollHeight;
  };
  const renderChips = (values) => {
    const labels = Array.isArray(values) ? values.filter(value => typeof value === 'string' && value.trim() && value.length <= 100) : [];
    chips.replaceChildren();
    [...new Set(labels.length ? labels : defaults)].slice(0, 8).forEach(label => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = label;
      button.disabled = busy;
      button.addEventListener('click', () => submit('quick_reply', label));
      chips.append(button);
    });
  };
  const setBusy = (value) => {
    busy = value;
    sendButton.disabled = value;
    chips.querySelectorAll('button').forEach(button => { button.disabled = value; });
    loading.hidden = !value;
  };

  async function submit(field, value) {
    value = value.trim();
    if (busy || !value || value.length > (field === 'message' ? 4000 : 100)) return;
    const signature = `${field}:${value}`;
    if (failedSubmission !== signature) bubble(value, 'user');
    error.hidden = true;
    setBusy(true);
    if (field === 'message') input.value = '';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 65000);
    try {
      const response = await fetch(widget.dataset.endpoint, {
        method: 'POST', credentials: 'same-origin', signal: controller.signal,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify({ [field]: value, conversation_token: token }),
      });
      if (!response.ok) {
        if (response.status === 404) saveToken(null);
        throw new Error('Chat request failed');
      }
      const data = await response.json();
      if (!data || !['question', 'recommendations'].includes(data.type)
        || (data.type === 'recommendations' && !Array.isArray(data.recommendations))) throw new Error('Invalid response');
      saveToken(data.conversation_token);
      if (data.type === 'recommendations' && data.recommendations.length === 0) {
        bubble('Chưa tìm thấy truyện phù hợp với các tiêu chí hiện tại.', 'bot');
      } else if (typeof data.message === 'string' && data.message.trim()) {
        bubble(data.message, 'bot');
      }
      if (data.type === 'recommendations') renderCards(data.recommendations);
      renderChips(data.quick_replies);
      failedSubmission = null;
    } catch (_) {
      failedSubmission = signature;
      error.textContent = 'Có lỗi khi tìm truyện. Thử lại nhé.';
      error.hidden = false;
      if (field === 'message' && !input.value) input.value = value;
    } finally {
      clearTimeout(timeout);
      setBusy(false);
      messages.scrollTop = messages.scrollHeight;
    }
  }
  function safeUrl(value, sameOrigin = false) {
    if (typeof value !== 'string' || !value.trim()) return null;
    try {
      const url = new URL(value, location.href);
      if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) return null;
      if (sameOrigin && url.origin !== location.origin) return null;
      return url.href;
    } catch (_) { return null; }
  }
  function renderCards(results) {
    const seen = new Set();
    results.forEach(comic => {
      if (!comic || typeof comic.title !== 'string' || seen.has(comic.id)) return;
      seen.add(comic.id);
      const card = document.createElement('article');
      card.className = 'recommendation-chat-card';
      const cover = document.createElement('img');
      cover.alt = `Bìa ${comic.title}`;
      cover.loading = 'lazy';
      const placeholder = safeUrl(widget.dataset.placeholder, true);
      cover.src = safeUrl(comic.cover) || placeholder || '';
      cover.addEventListener('error', () => {
        if (placeholder && cover.src !== placeholder) cover.src = placeholder;
        else cover.hidden = true;
      });
      const info = document.createElement('div');
      const title = document.createElement('h3');
      title.textContent = comic.title;
      info.append(title);
      if (Number.isFinite(comic.score)) {
        const score = document.createElement('p');
        score.textContent = `Điểm phù hợp: ${comic.score}`;
        info.append(score);
      }
      const reasons = Array.isArray(comic.matched_reasons)
        ? comic.matched_reasons.filter(reason => typeof reason === 'string').slice(0, 4) : [];
      if (reasons.length) {
        const text = document.createElement('p');
        text.textContent = `Phù hợp vì: ${reasons.join(' • ')}`;
        info.append(text);
      }
      const url = safeUrl(comic.url, true);
      if (url) {
        const link = document.createElement('a');
        link.href = url;
        link.textContent = 'Xem truyện';
        info.append(link);
      }
      card.append(cover, info);
      messages.append(card);
    });
  }
  form.addEventListener('submit', event => { event.preventDefault(); submit('message', input.value); });
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
      event.preventDefault();
      submit('message', input.value);
    }
  });
  renderChips(defaults);

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
