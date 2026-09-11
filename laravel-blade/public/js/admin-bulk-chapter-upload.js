(() => {
  const root = document.getElementById('tab-bulk-folder');
  if (!root) return;

  const endpoint = root.dataset.endpoint;
  const csrf = root.dataset.csrf;
  const chaptersUrl = root.dataset.chaptersUrl;
  const folderInput = document.getElementById('bulk-folder-input');
  const tableWrap = document.getElementById('bulk-chapter-table-wrap');
  const tableBody = document.getElementById('bulk-chapter-table-body');
  const uploadButton = document.getElementById('bulk-start-upload');
  const validationBox = document.getElementById('bulk-validation-message');
  const progressWrap = document.getElementById('bulk-progress-wrap');
  const progressBar = document.getElementById('bulk-progress-bar');
  const progressText = document.getElementById('bulk-progress-text');
  const summaryChapters = document.getElementById('bulk-summary-chapters');
  const summaryPages = document.getElementById('bulk-summary-pages');
  const summarySize = document.getElementById('bulk-summary-size');
  const summaryIssues = document.getElementById('bulk-summary-issues');
  const singleSubmitActions = document.getElementById('single-submit-actions');

  const naturalCollator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
  const allowedExtensions = new Set(['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif']);
  const MAX_IMAGE_BYTES = 20 * 1024 * 1024;
  const MAX_BATCH_BYTES = 24 * 1024 * 1024;
  const MAX_BATCH_FILES = 12;
  const MAX_PAGES_PER_CHAPTER = 2000;
  const MAX_SESSION_BYTES = 20 * 1024 * 1024 * 1024;

  let chapters = [];
  let totalBytes = 0;
  let uploadedBytes = 0;
  let activeSession = null;
  let uploadRunning = false;
  let activeChapterIndex = -1;
  const checksumCache = new WeakMap();

  class UploadHttpError extends Error {
    constructor(message, status, payload = null) {
      super(message);
      this.name = 'UploadHttpError';
      this.status = status;
      this.payload = payload;
    }
  }

  document.querySelectorAll('.tab-btn').forEach((button) => {
    button.addEventListener('click', () => {
      if (!singleSubmitActions) return;
      singleSubmitActions.style.display = button.dataset.target === 'tab-bulk-folder' ? 'none' : 'block';
    });
  });

  folderInput?.addEventListener('change', () => {
    if (uploadRunning) return;
    prepareFolder(Array.from(folderInput.files || []));
  });

  uploadButton?.addEventListener('click', () => {
    if (!uploadRunning) runUpload();
  });

  window.addEventListener('beforeunload', (event) => {
    if (!uploadRunning) return;
    event.preventDefault();
    event.returnValue = '';
  });

  function prepareFolder(files) {
    activeSession = null;
    uploadedBytes = 0;
    activeChapterIndex = -1;
    chapters = [];

    const groups = new Map();
    let ignoredFiles = 0;

    files.forEach((file) => {
      const relativePath = String(file.webkitRelativePath || file.name || '').replaceAll('\\', '/');
      const parts = relativePath.split('/').filter(Boolean);
      const fileName = parts.at(-1) || file.name;
      const extension = (fileName.split('.').pop() || '').toLowerCase();

      if (fileName === '.DS_Store' || fileName === 'Thumbs.db' || parts.includes('__MACOSX')) {
        ignoredFiles++;
        return;
      }

      if (!allowedExtensions.has(extension)) {
        ignoredFiles++;
        return;
      }

      const detected = detectChapterFolder(parts);
      if (!detected) {
        ignoredFiles++;
        return;
      }

      if (!groups.has(detected.groupKey)) {
        groups.set(detected.groupKey, {
          folderName: detected.folderName,
          groupKey: detected.groupKey,
          files: [],
        });
      }

      groups.get(detected.groupKey).files.push({
        file,
        innerPath: detected.innerPath || fileName,
      });
    });

    chapters = Array.from(groups.values()).map((group) => {
      group.files.sort((a, b) => naturalCollator.compare(a.innerPath, b.innerPath));
      const parsed = parseChapterFolderName(group.folderName);
      const size = group.files.reduce((sum, item) => sum + item.file.size, 0);

      return {
        ...group,
        chapterNumber: parsed.chapterNumber,
        title: parsed.title,
        size,
        status: 'pending',
        statusText: 'Chờ upload',
      };
    });

    chapters.sort((a, b) => {
      const aNumber = Number.isInteger(a.chapterNumber) ? a.chapterNumber : Number.MAX_SAFE_INTEGER;
      const bNumber = Number.isInteger(b.chapterNumber) ? b.chapterNumber : Number.MAX_SAFE_INTEGER;
      if (aNumber !== bNumber) return aNumber - bNumber;
      return naturalCollator.compare(a.folderName, b.folderName);
    });

    chapters.forEach((chapter, index) => {
      chapter.key = `chapter-${String(index).padStart(4, '0')}`;
    });

    totalBytes = chapters.reduce((sum, chapter) => sum + chapter.size, 0);
    renderChapterTable();
    refreshValidation(ignoredFiles);
  }

  function detectChapterFolder(parts) {
    if (parts.length < 2) return null;

    const directories = parts.slice(0, -1);
    let chapterDirectoryIndex = -1;

    for (let index = 0; index < directories.length; index++) {
      if (parseChapterFolderName(directories[index]).chapterNumber !== null) {
        chapterDirectoryIndex = index;
        break;
      }
    }

    if (chapterDirectoryIndex === -1) {
      if (directories.length >= 2) {
        chapterDirectoryIndex = 1;
      } else {
        chapterDirectoryIndex = 0;
      }
    }

    const folderName = directories[chapterDirectoryIndex];
    if (!folderName) return null;

    return {
      folderName,
      groupKey: directories.slice(0, chapterDirectoryIndex + 1).join('/'),
      innerPath: parts.slice(chapterDirectoryIndex + 1).join('/'),
    };
  }

  function parseChapterFolderName(folderName) {
    const match = folderName.match(/\bCh(?:apter)?\.?\s*0*(\d+)\b/i);
    if (!match) {
      return { chapterNumber: null, title: cleanFolderTitle(folderName) };
    }

    const chapterNumber = Number.parseInt(match[1], 10);
    let title = folderName.slice((match.index || 0) + match[0].length);
    title = title.replace(/^[\s._\-–—:]+/, '').trim();
    title = title.replace(/\s*\((?:en|vi|jp|ja|kr|ko)\)\s*(?:\[.*)?$/i, '').trim();
    title = title.replace(/\s*\[[^\]]*\]\s*$/g, '').trim();

    return {
      chapterNumber: Number.isInteger(chapterNumber) ? chapterNumber : null,
      title: title || `Chapter ${chapterNumber}`,
    };
  }

  function cleanFolderTitle(value) {
    return String(value || '')
      .replace(/\s*\((?:en|vi|jp|ja|kr|ko)\)\s*(?:\[.*)?$/i, '')
      .replace(/\s*\[[^\]]*\]\s*$/g, '')
      .trim();
  }

  function renderChapterTable() {
    tableBody.innerHTML = '';

    chapters.forEach((chapter, index) => {
      const row = document.createElement('tr');
      row.dataset.testid = 'bulk-chapter-row';
      row.dataset.chapterIndex = String(index);

      const folderCell = document.createElement('td');
      folderCell.innerHTML = `
        <div class="bulk-folder-name" title="${escapeHtml(chapter.folderName)}">${escapeHtml(chapter.folderName)}</div>
        <div class="bulk-folder-meta">${chapter.files.length} ảnh · ${humanBytes(chapter.size)}</div>
      `;

      const numberCell = document.createElement('td');
      const numberInput = document.createElement('input');
      numberInput.type = 'number';
      numberInput.min = '0';
      numberInput.step = '1';
      numberInput.className = 'form-control bulk-chapter-number';
      numberInput.dataset.testid = 'bulk-chapter-number';
      numberInput.value = chapter.chapterNumber ?? '';
      numberInput.placeholder = 'VD: 140';
      numberInput.addEventListener('input', () => {
        chapter.chapterNumber = numberInput.value === '' ? null : Number.parseInt(numberInput.value, 10);
        refreshValidation();
      });
      numberCell.appendChild(numberInput);

      const titleCell = document.createElement('td');
      const titleInput = document.createElement('input');
      titleInput.type = 'text';
      titleInput.maxLength = 255;
      titleInput.className = 'form-control bulk-chapter-title';
      titleInput.dataset.testid = 'bulk-chapter-title';
      titleInput.value = chapter.title || '';
      titleInput.placeholder = 'Tên chapter';
      titleInput.addEventListener('input', () => {
        chapter.title = titleInput.value.trim();
      });
      titleCell.appendChild(titleInput);

      const pagesCell = document.createElement('td');
      pagesCell.style.whiteSpace = 'nowrap';
      pagesCell.textContent = `${chapter.files.length} trang`;

      const statusCell = document.createElement('td');
      const status = document.createElement('span');
      status.className = `bulk-status bulk-status-${chapter.status}`;
      status.dataset.testid = 'bulk-chapter-status';
      status.textContent = chapter.statusText;
      statusCell.appendChild(status);

      row.append(folderCell, numberCell, titleCell, pagesCell, statusCell);
      tableBody.appendChild(row);
    });

    tableWrap.style.display = chapters.length ? 'block' : 'none';
  }

  function updateChapterStatus(index, status, text) {
    const chapter = chapters[index];
    if (!chapter) return;

    chapter.status = status;
    chapter.statusText = text;

    const row = tableBody.querySelector(`tr[data-chapter-index="${index}"]`);
    const badge = row?.querySelector('.bulk-status');
    if (badge) {
      badge.className = `bulk-status bulk-status-${status}`;
      badge.textContent = text;
    }
  }

  function refreshValidation(ignoredFiles = 0) {
    const issues = [];
    const numbers = new Map();
    let totalPages = 0;

    chapters.forEach((chapter, index) => {
      totalPages += chapter.files.length;

      if (!Number.isInteger(chapter.chapterNumber) || chapter.chapterNumber < 0) {
        issues.push(`Folder "${chapter.folderName}" chưa nhận diện được số chapter.`);
      } else {
        const count = numbers.get(chapter.chapterNumber) || 0;
        numbers.set(chapter.chapterNumber, count + 1);
      }

      if (chapter.files.length === 0 || chapter.files.length > MAX_PAGES_PER_CHAPTER) {
        issues.push(`Chapter ${chapter.chapterNumber ?? index + 1} có số trang không hợp lệ.`);
      }

      const oversized = chapter.files.find((item) => item.file.size > MAX_IMAGE_BYTES);
      if (oversized) {
        issues.push(`${oversized.file.name} vượt quá 20MB.`);
      }
    });

    numbers.forEach((count, number) => {
      if (count > 1) issues.push(`Chapter ${number} xuất hiện ${count} lần trong thư mục.`);
    });

    if (totalBytes > MAX_SESSION_BYTES) {
      issues.push('Tổng dung lượng vượt giới hạn an toàn 20GB / phiên upload.');
    }

    summaryChapters.textContent = String(chapters.length);
    summaryPages.textContent = String(totalPages);
    summarySize.textContent = humanBytes(totalBytes);
    summaryIssues.textContent = String(issues.length);

    if (!chapters.length) {
      validationBox.className = 'bulk-validation bulk-validation-neutral';
      validationBox.textContent = 'Chọn thư mục gốc chứa nhiều folder chapter để hệ thống phân tích trước khi upload.';
    } else if (issues.length) {
      validationBox.className = 'bulk-validation bulk-validation-error';
      validationBox.textContent = issues[0] + (issues.length > 1 ? ` (+${issues.length - 1} lỗi khác)` : '');
    } else {
      validationBox.className = 'bulk-validation bulk-validation-ok';
      validationBox.textContent = `Sẵn sàng upload ${chapters.length} chapter. ${ignoredFiles ? `Đã bỏ qua ${ignoredFiles} file không phải ảnh / file rác.` : 'Không phát hiện lỗi.'}`;
    }

    uploadButton.disabled = uploadRunning || !chapters.length || issues.length > 0;
    return issues;
  }

  async function runUpload() {
    if (refreshValidation().length) return;

    uploadRunning = true;
    uploadButton.disabled = true;
    folderInput.disabled = true;
    uploadButton.textContent = activeSession ? '⏳ Đang tiếp tục...' : '⏳ Đang chuẩn bị phiên upload...';
    progressWrap.style.display = 'block';

    try {
      if (!activeSession) {
        const started = await postAction({ bulk_action: 'start' });
        activeSession = started.session;
      }

      for (let chapterIndex = 0; chapterIndex < chapters.length; chapterIndex++) {
        const chapter = chapters[chapterIndex];
        if (chapter.status === 'done') continue;

        activeChapterIndex = chapterIndex;
        updateChapterStatus(chapterIndex, 'uploading', 'Đang upload');
        uploadButton.textContent = `⏳ Chapter ${chapter.chapterNumber} (${chapterIndex + 1}/${chapters.length})`;

        const batches = createBatches(chapter.files);
        for (let batchIndex = 0; batchIndex < batches.length; batchIndex++) {
          await uploadBatchAdaptive(activeSession, chapter, batches[batchIndex]);
          const batchBytes = batches[batchIndex].reduce((sum, item) => sum + item.file.size, 0);
          uploadedBytes = Math.min(totalBytes, uploadedBytes + batchBytes);
          renderProgress(chapter, batchIndex + 1, batches.length);
        }

        const finalized = await postAction({
          bulk_action: 'finalize',
          session: activeSession,
          chapter_key: chapter.key,
          chapter_number: String(chapter.chapterNumber),
          title: chapter.title || '',
          page_count: String(chapter.files.length),
        });

        updateChapterStatus(chapterIndex, 'done', `✓ Xong · ${finalized.pages} trang`);
      }

      const completed = await postAction({
        bulk_action: 'complete',
        session: activeSession,
      });

      activeSession = null;
      uploadedBytes = totalBytes;
      renderProgress(null, 1, 1);
      validationBox.className = 'bulk-validation bulk-validation-ok';
      validationBox.innerHTML = `Đã tạo thành công <strong>${completed.chapters_created}</strong> chapter. Ảnh được giữ nguyên byte gốc và đã kiểm tra SHA-256 trước + sau khi lưu. <a href="${chaptersUrl}">Mở danh sách chapter →</a>`;
      uploadButton.textContent = '✅ Upload hoàn tất';
    } catch (error) {
      if (activeChapterIndex >= 0) {
        updateChapterStatus(activeChapterIndex, 'failed', 'Lỗi · có thể thử lại');
      }

      validationBox.className = 'bulk-validation bulk-validation-error';
      validationBox.textContent = error instanceof Error ? error.message : 'Upload thất bại.';
      uploadButton.textContent = '↻ Thử lại / tiếp tục';
    } finally {
      uploadRunning = false;
      folderInput.disabled = false;
      uploadButton.disabled = refreshValidation().length > 0;
    }
  }

  function createBatches(files) {
    const batches = [];
    let current = [];
    let currentBytes = 0;

    files.forEach((item, index) => {
      const wouldOverflow = current.length > 0 && (
        current.length >= MAX_BATCH_FILES ||
        currentBytes + item.file.size > MAX_BATCH_BYTES
      );

      if (wouldOverflow) {
        batches.push(current);
        current = [];
        currentBytes = 0;
      }

      current.push({ ...item, pageIndex: index });
      currentBytes += item.file.size;
    });

    if (current.length) batches.push(current);
    return batches;
  }

  async function uploadBatchAdaptive(session, chapter, batch) {
    try {
      return await uploadBatch(session, chapter, batch);
    } catch (error) {
      if (error instanceof UploadHttpError && error.status === 413 && batch.length > 1) {
        const middle = Math.ceil(batch.length / 2);
        await uploadBatchAdaptive(session, chapter, batch.slice(0, middle));
        await uploadBatchAdaptive(session, chapter, batch.slice(middle));
        return;
      }
      throw error;
    }
  }

  async function uploadBatch(session, chapter, batch) {
    const formData = new FormData();
    formData.append('bulk_action', 'chunk');
    formData.append('session', session);
    formData.append('chapter_key', chapter.key);

    for (const item of batch) {
      formData.append('files[]', item.file, item.file.name);
      formData.append('page_indexes[]', String(item.pageIndex));
      formData.append('checksums[]', await sha256(item.file));
    }

    return sendForm(formData, 3);
  }

  async function sha256(file) {
    if (checksumCache.has(file)) return checksumCache.get(file);

    if (!window.crypto?.subtle) {
      throw new Error('Trình duyệt không hỗ trợ SHA-256 Web Crypto. Hãy dùng Chrome / Edge bản mới.');
    }

    const digest = await window.crypto.subtle.digest('SHA-256', await file.arrayBuffer());
    const hash = Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
    checksumCache.set(file, hash);
    return hash;
  }

  async function postAction(values) {
    const formData = new FormData();
    Object.entries(values).forEach(([key, value]) => formData.append(key, value));
    return sendForm(formData, 3);
  }

  async function sendForm(formData, attempts = 3) {
    let lastError = null;

    for (let attempt = 1; attempt <= attempts; attempt++) {
      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
          },
          body: formData,
        });

        const payload = await readResponsePayload(response);
        if (!response.ok) {
          const message = extractErrorMessage(payload) || `Upload lỗi HTTP ${response.status}.`;
          throw new UploadHttpError(message, response.status, payload);
        }

        return payload;
      } catch (error) {
        lastError = error;

        if (error instanceof UploadHttpError) {
          if (error.status === 413 || (error.status >= 400 && error.status < 500)) {
            throw error;
          }
        }

        if (attempt < attempts) {
          await sleep(700 * attempt);
          continue;
        }
      }
    }

    throw lastError || new Error('Không thể kết nối máy chủ để upload.');
  }

  async function readResponsePayload(response) {
    const text = await response.text();
    if (!text) return {};

    try {
      return JSON.parse(text);
    } catch {
      return { message: text.slice(0, 500) };
    }
  }

  function extractErrorMessage(payload) {
    if (!payload || typeof payload !== 'object') return null;
    if (payload.errors && typeof payload.errors === 'object') {
      const first = Object.values(payload.errors).flat().find(Boolean);
      if (first) return String(first);
    }
    return payload.message ? String(payload.message) : null;
  }

  function renderProgress(chapter, batchNumber, totalBatches) {
    const percent = totalBytes > 0 ? Math.min(100, Math.round((uploadedBytes / totalBytes) * 100)) : 0;
    progressBar.style.width = `${percent}%`;
    progressBar.setAttribute('aria-valuenow', String(percent));

    if (chapter) {
      progressText.textContent = `${percent}% · ${humanBytes(uploadedBytes)} / ${humanBytes(totalBytes)} · Chapter ${chapter.chapterNumber}, batch ${batchNumber}/${totalBatches}`;
    } else {
      progressText.textContent = `100% · ${humanBytes(totalBytes)} đã upload và xác minh`;
    }
  }

  function humanBytes(bytes) {
    const value = Number(bytes) || 0;
    if (value < 1024) return `${value} B`;
    const units = ['KB', 'MB', 'GB', 'TB'];
    let size = value / 1024;
    let unit = units[0];

    for (let index = 1; index < units.length && size >= 1024; index++) {
      size /= 1024;
      unit = units[index];
    }

    return `${size >= 100 ? size.toFixed(0) : size.toFixed(2)} ${unit}`;
  }

  function escapeHtml(value) {
    return String(value || '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function sleep(milliseconds) {
    return new Promise((resolve) => setTimeout(resolve, milliseconds));
  }
})();
