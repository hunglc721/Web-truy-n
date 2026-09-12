(() => {
  const widget = document.getElementById('upload-task-widget');
  if (!widget || window.ComicxUploads) return;
  if (document.querySelector('.reader-page-wrapper')) widget.querySelector('[data-upload-details]').hidden = true;
  const channel = typeof BroadcastChannel === 'function' ? new BroadcastChannel('webcomics-upload') : null;
  let task = null, lastEvent = 0, fetching = false;
  const terminal = value => ['completed', 'cancelled'].includes(value?.status);
  async function api(url, body) {
    const response = await fetch(url, { cache: 'no-store', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': widget.dataset.csrf }, ...(body ? { method: 'POST', body: JSON.stringify(body) } : {}) });
    const payload = await response.json();
    if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || `HTTP ${response.status}`);
    return payload.task;
  }
  const bytes = value => `${(value / 1048576).toFixed(2)} MB`;
  function render(value) {
    if (!value || String(value.user_id) !== widget.dataset.userId) return;
    if (task?.id === value.id && value.version < task.version) return;
    task = value;
    widget.hidden = false;
    widget.dataset.taskId = task.id;
    widget.dataset.uploadedBytes = task.uploaded_bytes;
    widget.dataset.status = task.status;
    const percent = task.total_bytes ? Math.min(100, Math.floor(task.uploaded_bytes * 100 / task.total_bytes)) : (task.status === 'completed' ? 100 : 0);
    widget.querySelector('[data-upload-title]').textContent = `${task.status === 'completed' ? '✅ Upload hoàn tất' : '⬆ Upload'} ${task.comic.title} · ${percent}%`;
    widget.querySelector('[data-upload-progress]').textContent = `${task.completed_chapters}/${task.uploadable_chapters} chapter (${task.skipped_chapters} bỏ qua) · ${task.uploaded_files}/${task.total_files} file · ${bytes(task.uploaded_bytes)}/${bytes(task.total_bytes)} · Chapter ${task.current_chapter_number ?? '—'} · Batch ${task.current_batch}/${task.total_batches}`;
    widget.querySelector('[data-upload-status]').textContent = task.status === 'waiting_for_client' ? 'Upload đang tạm dừng · Chọn lại folder để tiếp tục' : task.worker_alive ? 'Upload worker đang hoạt động' : task.status;
    widget.querySelector('[data-upload-error]').textContent = task.error_message || '';
    widget.querySelector('[data-upload-return]').href = task.uploader_url;
    widget.querySelector('[data-upload-cancel]').hidden = terminal(task);
    document.dispatchEvent(new CustomEvent('upload-task-snapshot', { detail: task }));
  }
  async function refresh() {
    if (fetching) return;
    fetching = true;
    try { render(await api(task && !terminal(task) ? `/admin/upload-tasks/${task.id}` : '/admin/upload-tasks/active')); }
    catch (error) { widget.querySelector('[data-upload-error]').textContent = error.message; }
    finally { fetching = false; }
  }
  function openWorker() {
    const popup = window.open('', 'webcomics-upload-worker');
    if (!popup) throw new Error('Popup bị chặn. Cho phép popup rồi bấm “Mở trình upload nền”.');
    if (popup.location.href === 'about:blank') popup.location.replace('/admin/upload-worker');
    popup.focus();
    return popup;
  }
  async function transfer(popup, value, chapters) {
    const started = Date.now();
    while (!popup.comicxUploadWorkerReady) {
      if (popup.closed || Date.now() - started > 15000) throw new Error('Không kết nối được trình upload nền.');
      await new Promise(resolve => setTimeout(resolve, 100));
    }
    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => { window.removeEventListener('message', received); reject(new Error('Worker chưa nhận folder.')); }, 10000);
      function received(event) {
        if (event.origin !== location.origin || event.source !== popup || event.data?.type !== 'UPLOAD_ACCEPTED' || event.data.taskId !== value.id) return;
        clearTimeout(timer); window.removeEventListener('message', received); resolve();
      }
      window.addEventListener('message', received);
      popup.postMessage({ type: 'START_UPLOAD', task: value, chapters }, location.origin);
    });
    render(value);
  }
  window.ComicxUploads = { api, render, openWorker, transfer, refresh, get task() { return task; } };
  widget.querySelector('[data-upload-minimize]').onclick = () => { const details = widget.querySelector('[data-upload-details]'); details.hidden = !details.hidden; };
  widget.querySelector('[data-upload-title]').onclick = widget.querySelector('[data-upload-minimize]').onclick;
  widget.querySelector('[data-upload-worker]').onclick = () => { try { openWorker(); } catch (error) { widget.querySelector('[data-upload-error]').textContent = error.message; } };
  widget.querySelector('[data-upload-cancel]').onclick = async () => {
    try { const value = await api(`/admin/upload-tasks/${task.id}/control`, { action: 'cancel' }); render(value); channel?.postMessage({ type: 'UPLOAD_PROGRESS', task: value }); }
    catch (error) { widget.querySelector('[data-upload-error]').textContent = error.message; }
  };
  channel?.addEventListener('message', event => {
    if (!['UPLOAD_PROGRESS', 'UPLOAD_ERROR', 'UPLOAD_COMPLETED'].includes(event.data?.type)) return;
    lastEvent = Date.now(); render(event.data.task);
  });
  // Snapshot immediately on each navigation; poll only when broadcasts are unavailable/stale.
  refresh();
  const timer = setInterval(() => { if (!channel || Date.now() - lastEvent > 12000) refresh(); }, 5000);
  window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
  window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
})();
