(() => {
  const workerId = crypto.randomUUID();
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const status = document.getElementById('worker-status');
  const channel = typeof BroadcastChannel === 'function' ? new BroadcastChannel('webcomics-upload') : null;
  let task = null, chapters = null, running = false, heartbeat = null, stopped = false;
  const hashes = new WeakMap();
  const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
  const publish = snapshot => {
    if (task?.id === snapshot.id && snapshot.version < task.version) return;
    task = snapshot;
    status.textContent = `${task.comic.title}: ${task.completed_chapters}/${task.uploadable_chapters} chapter · ${task.uploaded_bytes}/${task.total_bytes} bytes · ${task.status}${task.error_message ? ' · ' + task.error_message : ''}`;
    channel?.postMessage({ type: task.status === 'completed' ? 'UPLOAD_COMPLETED' : task.status === 'failed' ? 'UPLOAD_ERROR' : 'UPLOAD_PROGRESS', task });
  };
  async function request(url, body, attempts = 1) {
    for (let attempt = 1; ; attempt++) {
      try {
        const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', ...(body instanceof FormData ? {} : { 'Content-Type': 'application/json' }) }, body: body instanceof FormData ? body : JSON.stringify(body) });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw Object.assign(new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || `HTTP ${response.status}`), { httpStatus: response.status });
        return payload.task;
      } catch (error) {
        if (attempt >= attempts || (error.httpStatus >= 400 && error.httpStatus < 500)) throw error;
        await delay(700 * attempt);
      }
    }
  }
  const control = (action, extra = {}) => request(`/admin/upload-tasks/${task.id}/control`, { action, worker_id: workerId, ...extra });
  async function hash(file) {
    if (!hashes.has(file)) hashes.set(file, Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256', await file.arrayBuffer())), b => b.toString(16).padStart(2, '0')).join(''));
    return hashes.get(file);
  }
  function batches(files) {
    const result = []; let batch = [], bytes = 0;
    files.forEach(item => {
      if (batch.length && (batch.length >= 12 || bytes + item.file.size > 24 * 1024 * 1024)) { result.push(batch); batch = []; bytes = 0; }
      batch.push(item); bytes += item.file.size;
    });
    if (batch.length) result.push(batch);
    return result;
  }
  async function upload(action, chapter, batch, batchIndex, totalBatches) {
    if (stopped) throw new Error('Upload đã dừng.');
    const form = new FormData();
    const values = { bulk_action: action, worker_id: workerId, session: task.upload_session_id, chapter_key: chapter?.key || '', chapter_number: chapter?.number || '', title: chapter?.title || '', page_count: chapter?.files.length || 0, current_batch: batchIndex + 1, total_batches: Math.max(1, totalBatches) };
    Object.entries(values).forEach(([key, value]) => form.append(key, String(value)));
    for (const item of batch) {
      form.append('files[]', item.file, item.file.name);
      form.append('page_indexes[]', String(item.index));
      form.append('checksums[]', await hash(item.file));
    }
    try {
      publish(await request(`/admin/upload-tasks/${task.id}/upload`, form, 3));
    } catch (error) {
      if (error.httpStatus === 413 && batch.length > 1) {
        const middle = Math.ceil(batch.length / 2);
        await upload(action, chapter, batch.slice(0, middle), batchIndex, totalBatches);
        await upload(action, chapter, batch.slice(middle), batchIndex, totalBatches);
        return;
      }
      Object.assign(error, { chapterNumber: chapter?.number, chapterIndex: chapter?.index ?? 0, batchIndex, totalBatches, pageIndexes: batch.map(item => item.index), fileNames: batch.map(item => item.file.name) });
      throw error;
    }
  }
  async function run() {
    if (running || !chapters) return;
    running = true; stopped = false;
    try {
      const claimed = await control('claim');
      const manifest = claimed.manifest, receipts = claimed.session_state;
      delete claimed.manifest; delete claimed.session_state;
      publish(claimed);
      clearInterval(heartbeat);
      let beating = false;
      heartbeat = setInterval(async () => {
        if (beating) return;
        beating = true;
        try { publish(await control('heartbeat')); }
        catch (error) { if ([401, 403, 409].includes(error.httpStatus)) { stopped = true; clearInterval(heartbeat); } }
        finally { beating = false; }
      }, 8000);
      for (const chapter of manifest) {
        chapter.index = manifest.indexOf(chapter);
        if (chapter.skipped || receipts.finalized[chapter.key]) continue;
        const selected = chapters.find(ch => ch.number === chapter.number);
        if (!selected || selected.files.length !== chapter.files.length || selected.files.some((item, i) => item.file.name !== chapter.files[i].name || item.file.size !== chapter.files[i].size)) throw new Error(`Folder không khớp Chapter ${chapter.number}; chọn đúng folder và các ảnh đã chỉnh sửa.`);
        const pending = [];
        for (let index = 0; index < selected.files.length; index++) {
          const item = selected.files[index], receipt = receipts.pages[chapter.key]?.[index];
          if (receipt) {
            if (await hash(item.file) !== receipt.sha256) throw new Error(`Checksum ảnh đã lưu không khớp Chapter ${chapter.number}, trang ${index + 1}.`);
          } else pending.push({ file: item.file, index });
        }
        const groups = batches(pending);
        for (let i = 0; i < groups.length; i++) await upload('chunk', chapter, groups[i], i, groups.length);
        await upload('finalize', chapter, [], Math.max(0, groups.length - 1), groups.length);
      }
      await upload('complete', null, [], 0, 1);
      chapters = null;
      clearInterval(heartbeat);
    } catch (error) {
      if (!error.httpStatus && /fetch|network/i.test(error.message)) error.message = 'Mất kết nối hoặc server không phản hồi';
      status.textContent = error.message;
      if (!stopped) {
        try { publish(await control('error', { error: { message: error.message.slice(0, 2000), httpStatus: error.httpStatus || 0, chapterNumber: error.chapterNumber, chapterIndex: error.chapterIndex, batchIndex: error.batchIndex, totalBatches: error.totalBatches, pageIndexes: error.pageIndexes || [], fileNames: error.fileNames || [] } })); }
        catch { clearInterval(heartbeat); }
      }
    } finally { running = false; }
  }
  window.addEventListener('message', event => {
    if (event.origin !== location.origin || event.data?.type !== 'START_UPLOAD') return;
    if (task && running) { event.source?.postMessage({ type: 'UPLOAD_ACCEPTED', taskId: task.id }, location.origin); return; }
    task = event.data.task;
    chapters = event.data.chapters || chapters;
    if (!chapters) return;
    event.source?.postMessage({ type: 'UPLOAD_ACCEPTED', taskId: task.id }, location.origin);
    run();
  });
  channel?.addEventListener('message', event => {
    if (event.data?.task?.id !== task?.id) return;
    if (event.data.task.status === 'cancelled') { stopped = true; clearInterval(heartbeat); publish(event.data.task); }
  });
  window.addEventListener('pagehide', () => {
    if (!task || ['completed', 'cancelled'].includes(task.status)) return;
    const data = new FormData();
    data.append('_token', csrf); data.append('action', 'release'); data.append('worker_id', workerId);
    navigator.sendBeacon?.(`/admin/upload-tasks/${task.id}/control`, data);
  });
  window.comicxUploadWorkerReady = true;
})();
