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
  const PREVIEW_PAGE_SIZE = 48;

  let chapters = [];
  let totalBytes = 0;
  let uploadedBytes = 0;
  let activeSession = null;
  let uploadRunning = false;
  let activeChapterIndex = -1;
  let ignoredFileCount = 0;
  let inspectorChapterIndex = -1;
  let inspectorRenderedPages = 0;
  let inspectorObjectUrls = [];

  const checksumCache = new WeakMap();

  class UploadHttpError extends Error {
    constructor(message, status, payload = null) {
      super(message);
      this.name = 'UploadHttpError';
      this.status = status;
      this.payload = payload;
    }
  }

  injectPreflightUi();

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

  function injectPreflightUi() {
    const headerRow = tableWrap?.querySelector('thead tr');
    if (headerRow && !headerRow.querySelector('[data-preflight-header]')) {
      const th = document.createElement('th');
      th.dataset.preflightHeader = 'true';
      th.style.width = '175px';
      th.textContent = 'Kiểm tra trước';
      headerRow.appendChild(th);
    }

    if (!document.getElementById('bulk-preflight-toolbar')) {
      const toolbar = document.createElement('div');
      toolbar.id = 'bulk-preflight-toolbar';
      toolbar.className = 'bulk-preflight-toolbar';
      toolbar.style.display = 'none';
      toolbar.innerHTML = `
        <div>
          <strong>🔎 Kiểm tra trước khi upload</strong>
          <span id="bulk-preflight-summary">Chưa có chapter để kiểm tra.</span>
        </div>
        <button type="button" id="bulk-check-all" class="btn-admin btn-admin-ghost btn-sm">
          ✓ Kiểm tra tất cả Chapter
        </button>
      `;
      tableWrap?.before(toolbar);

      toolbar.querySelector('#bulk-check-all')?.addEventListener('click', () => {
        if (!uploadRunning) inspectAllChapters();
      });
    }

    if (!document.getElementById('bulk-chapter-inspector')) {
      const inspector = document.createElement('section');
      inspector.id = 'bulk-chapter-inspector';
      inspector.className = 'bulk-chapter-inspector';
      inspector.style.display = 'none';
      inspector.innerHTML = `
        <div class="bulk-inspector-head">
          <div>
            <div class="bulk-inspector-kicker">PRE-UPLOAD CHECK</div>
            <h3 id="bulk-inspector-title">Chapter</h3>
            <p id="bulk-inspector-subtitle">Kiểm tra thứ tự trang, kích thước và ảnh hỏng trước khi upload.</p>
          </div>
          <button type="button" id="bulk-inspector-close" class="btn-admin btn-admin-ghost btn-sm">✕ Đóng</button>
        </div>

        <div class="bulk-inspector-summary">
          <div><span>Trạng thái</span><strong id="bulk-inspector-status">Chưa kiểm tra</strong></div>
          <div><span>Số trang</span><strong id="bulk-inspector-pages">0</strong></div>
          <div><span>Dung lượng</span><strong id="bulk-inspector-size">0 B</strong></div>
          <div><span>Ảnh lỗi</span><strong id="bulk-inspector-errors">0</strong></div>
        </div>

        <div id="bulk-inspector-message" class="bulk-inspector-message">
          Bấm kiểm tra để quét từng ảnh. Hệ thống chỉ mở một ảnh tại một thời điểm để tránh ngốn RAM với folder vài GB.
        </div>

        <div class="bulk-inspector-actions">
          <button type="button" id="bulk-inspector-run" class="btn-admin btn-admin-primary btn-sm">🔎 Kiểm tra Chapter này</button>
          <span>Ảnh preview chỉ được tạo cho chapter đang mở và sẽ giải phóng khi đóng.</span>
        </div>

        <div id="bulk-inspector-pages-grid" class="bulk-inspector-pages-grid"></div>

        <div class="bulk-inspector-more-wrap">
          <button type="button" id="bulk-inspector-more" class="btn-admin btn-admin-ghost btn-sm" style="display:none">
            Xem thêm trang
          </button>
        </div>
      `;
      tableWrap?.after(inspector);

      inspector.querySelector('#bulk-inspector-close')?.addEventListener('click', closeInspector);
      inspector.querySelector('#bulk-inspector-run')?.addEventListener('click', () => {
        if (inspectorChapterIndex >= 0 && !uploadRunning) inspectChapter(inspectorChapterIndex, true);
      });
      inspector.querySelector('#bulk-inspector-more')?.addEventListener('click', () => {
        if (inspectorChapterIndex >= 0) renderInspectorPages(inspectorChapterIndex, false);
      });
    }

    if (!document.getElementById('bulk-preflight-styles')) {
      const style = document.createElement('style');
      style.id = 'bulk-preflight-styles';
      style.textContent = `
        .bulk-preflight-toolbar {
          margin-top:14px;
          padding:12px 14px;
          display:flex;
          align-items:center;
          justify-content:space-between;
          gap:12px;
          flex-wrap:wrap;
          border:1px solid rgba(59,130,246,.28);
          border-radius:10px;
          background:rgba(59,130,246,.07);
        }
        .bulk-preflight-toolbar strong { display:block; font-size:13px; color:var(--admin-text); }
        .bulk-preflight-toolbar span { display:block; margin-top:3px; font-size:11.5px; color:var(--admin-text-muted); }
        .bulk-preflight-button { min-width:128px; justify-content:center; }
        .bulk-preflight-state {
          display:inline-flex;
          align-items:center;
          justify-content:center;
          min-width:92px;
          padding:4px 7px;
          margin-bottom:6px;
          border-radius:999px;
          font-size:10px;
          font-weight:800;
        }
        .bulk-preflight-unchecked { background:rgba(148,163,184,.12); color:#cbd5e1; }
        .bulk-preflight-checking { background:rgba(59,130,246,.14); color:#bfdbfe; }
        .bulk-preflight-ok { background:rgba(16,185,129,.14); color:#a7f3d0; }
        .bulk-preflight-error { background:rgba(239,68,68,.14); color:#fecaca; }
        .bulk-chapter-table tbody tr.bulk-row-clickable { cursor:pointer; transition:background .15s ease; }
        .bulk-chapter-table tbody tr.bulk-row-clickable:hover { background:rgba(108,99,255,.06); }
        .bulk-chapter-inspector {
          margin-top:16px;
          border:1px solid rgba(108,99,255,.3);
          border-radius:14px;
          background:rgba(10,10,20,.36);
          padding:16px;
          scroll-margin-top:90px;
        }
        .bulk-inspector-head {
          display:flex;
          justify-content:space-between;
          align-items:flex-start;
          gap:14px;
          flex-wrap:wrap;
        }
        .bulk-inspector-kicker {
          font-size:10px;
          letter-spacing:.12em;
          font-weight:900;
          color:#a5b4fc;
          margin-bottom:4px;
        }
        .bulk-inspector-head h3 { margin:0; font-size:18px; }
        .bulk-inspector-head p { margin:5px 0 0; font-size:12px; color:var(--admin-text-muted); }
        .bulk-inspector-summary {
          display:grid;
          grid-template-columns:repeat(4,minmax(0,1fr));
          gap:8px;
          margin-top:14px;
        }
        .bulk-inspector-summary > div {
          padding:10px;
          border:1px solid var(--admin-border);
          border-radius:9px;
          background:rgba(255,255,255,.03);
        }
        .bulk-inspector-summary span { display:block; font-size:10px; color:var(--admin-text-muted); }
        .bulk-inspector-summary strong { display:block; margin-top:3px; font-size:13px; }
        .bulk-inspector-message {
          margin-top:12px;
          padding:10px 12px;
          border:1px solid var(--admin-border);
          border-radius:9px;
          font-size:12px;
          line-height:1.55;
          color:var(--admin-text-muted);
        }
        .bulk-inspector-message.is-ok { border-color:rgba(16,185,129,.3); background:rgba(16,185,129,.07); color:#b7f7dc; }
        .bulk-inspector-message.is-error { border-color:rgba(239,68,68,.3); background:rgba(239,68,68,.07); color:#fecaca; }
        .bulk-inspector-message.is-checking { border-color:rgba(59,130,246,.3); background:rgba(59,130,246,.07); color:#bfdbfe; }
        .bulk-inspector-actions {
          display:flex;
          align-items:center;
          gap:10px;
          flex-wrap:wrap;
          margin-top:12px;
        }
        .bulk-inspector-actions span { font-size:11px; color:var(--admin-text-muted); }
        .bulk-inspector-pages-grid {
          display:grid;
          grid-template-columns:repeat(auto-fill,minmax(128px,1fr));
          gap:10px;
          margin-top:14px;
        }
        .bulk-preview-page {
          min-width:0;
          border:1px solid var(--admin-border);
          border-radius:9px;
          background:rgba(255,255,255,.035);
          overflow:hidden;
        }
        .bulk-preview-page img {
          display:block;
          width:100%;
          aspect-ratio:3/4;
          object-fit:contain;
          background:#09090b;
        }
        .bulk-preview-page.is-error { border-color:rgba(239,68,68,.55); }
        .bulk-preview-page-meta { padding:7px; }
        .bulk-preview-page-meta strong {
          display:block;
          font-size:10.5px;
          overflow:hidden;
          text-overflow:ellipsis;
          white-space:nowrap;
        }
        .bulk-preview-page-meta span { display:block; margin-top:2px; font-size:9.5px; color:var(--admin-text-muted); }
        .bulk-preview-page-error { color:#fecaca !important; }
        .bulk-inspector-more-wrap { margin-top:12px; text-align:center; }
        @media (max-width:700px) {
          .bulk-inspector-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
          .bulk-inspector-pages-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
      `;
      document.head.appendChild(style);
    }
  }

  function prepareFolder(files) {
    activeSession = null;
    uploadedBytes = 0;
    activeChapterIndex = -1;
    ignoredFileCount = 0;
    chapters = [];
    closeInspector();

    const groups = new Map();

    files.forEach((file) => {
      const relativePath = String(file.webkitRelativePath || file.name || '').replaceAll('\\', '/');
      const parts = relativePath.split('/').filter(Boolean);
      const fileName = parts.at(-1) || file.name;
      const extension = (fileName.split('.').pop() || '').toLowerCase();

      if (fileName === '.DS_Store' || fileName === 'Thumbs.db' || parts.includes('__MACOSX')) {
        ignoredFileCount++;
        return;
      }

      if (!allowedExtensions.has(extension)) {
        ignoredFileCount++;
        return;
      }

      const detected = detectChapterFolder(parts);
      if (!detected) {
        ignoredFileCount++;
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
        preflightStatus: 'unchecked',
        preflightErrors: [],
        pageMeta: [],
      };
    });

    chapters.sort((a, b) => {
      const aNumber = typeof a.chapterNumber === 'number' && Number.isFinite(a.chapterNumber) ? a.chapterNumber : Number.MAX_SAFE_INTEGER;
      const bNumber = typeof b.chapterNumber === 'number' && Number.isFinite(b.chapterNumber) ? b.chapterNumber : Number.MAX_SAFE_INTEGER;
      if (aNumber !== bNumber) return aNumber - bNumber;
      return naturalCollator.compare(a.folderName, b.folderName);
    });

    chapters.forEach((chapter, index) => {
      chapter.key = `chapter-${String(index).padStart(4, '0')}`;
    });

    totalBytes = chapters.reduce((sum, chapter) => sum + chapter.size, 0);
    renderChapterTable();
    refreshValidation();
    updatePreflightToolbar();
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
      chapterDirectoryIndex = directories.length >= 2 ? 1 : 0;
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
    let match = folderName.match(/\bCh(?:apter)?\.?\s*(\d+(?:\.\d+)?)(?=[^\d.]|$)/i);
    if (!match) {
      match = folderName.match(/^(?:Vol\.?\s*\d+[\s._\-–—]+)?(\d+(?:\.\d+)?)(?=[^\d.]|$)/i);
    }
    if (!match) {
      return { chapterNumber: null, title: cleanFolderTitle(folderName) };
    }

    const rawNum = Number.parseFloat(match[1]);
    const chapterNumber = Number.isFinite(rawNum) ? rawNum : null;
    let title = folderName.slice((match.index || 0) + match[0].length);
    title = title.replace(/^[\s._\-–—:]+/, '').trim();
    title = title.replace(/\s*\((?:en|vi|jp|ja|kr|ko)\)\s*(?:\[.*)?$/i, '').trim();
    title = title.replace(/\s*\[[^\]]*\]\s*$/g, '').trim();

    return {
      chapterNumber,
      title: title || (chapterNumber !== null ? `Chapter ${chapterNumber}` : cleanFolderTitle(folderName)),
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
      row.classList.add('bulk-row-clickable');

      const folderCell = document.createElement('td');
      folderCell.innerHTML = `
        <div class="bulk-folder-name" title="${escapeHtml(chapter.folderName)}">${escapeHtml(chapter.folderName)}</div>
        <div class="bulk-folder-meta">${chapter.files.length} ảnh · ${humanBytes(chapter.size)} · Bấm để xem trước</div>
      `;

      const numberCell = document.createElement('td');
      const numberInput = document.createElement('input');
      numberInput.type = 'number';
      numberInput.min = '0';
      numberInput.step = 'any';
      numberInput.className = 'form-control bulk-chapter-number';
      numberInput.dataset.testid = 'bulk-chapter-number';
      numberInput.value = chapter.chapterNumber ?? '';
      numberInput.placeholder = 'VD: 140 hoặc 187.5';
      numberInput.addEventListener('input', () => {
        const val = numberInput.value.trim();
        chapter.chapterNumber = val === '' ? null : Number.parseFloat(val);
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

      const preflightCell = document.createElement('td');
      preflightCell.style.whiteSpace = 'nowrap';

      const preflightState = document.createElement('span');
      preflightState.dataset.testid = 'bulk-preflight-state';
      preflightState.className = `bulk-preflight-state bulk-preflight-${chapter.preflightStatus}`;
      preflightState.textContent = preflightLabel(chapter.preflightStatus);

      const inspectButton = document.createElement('button');
      inspectButton.type = 'button';
      inspectButton.className = 'btn-admin btn-admin-ghost btn-sm bulk-preflight-button';
      inspectButton.dataset.testid = 'bulk-chapter-inspect';
      inspectButton.textContent = '👁 Xem & kiểm tra';
      inspectButton.disabled = uploadRunning || chapter.preflightStatus === 'checking';
      inspectButton.addEventListener('click', (event) => {
        event.stopPropagation();
        openChapterInspector(index, true);
      });

      preflightCell.append(preflightState, document.createElement('br'), inspectButton);

      row.addEventListener('click', (event) => {
        if (event.target.closest('input, button, a, select, textarea')) return;
        openChapterInspector(index, true);
      });

      row.append(folderCell, numberCell, titleCell, pagesCell, statusCell, preflightCell);
      tableBody.appendChild(row);
    });

    tableWrap.style.display = chapters.length ? 'block' : 'none';
  }

  function preflightLabel(status) {
    return ({
      unchecked: 'Chưa kiểm tra',
      checking: 'Đang kiểm tra',
      ok: '✓ Đạt',
      error: '⚠ Có lỗi',
    })[status] || 'Chưa kiểm tra';
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

  function updatePreflightState(index) {
    const chapter = chapters[index];
    if (!chapter) return;

    const row = tableBody.querySelector(`tr[data-chapter-index="${index}"]`);
    const badge = row?.querySelector('[data-testid="bulk-preflight-state"]');
    const button = row?.querySelector('[data-testid="bulk-chapter-inspect"]');

    if (badge) {
      badge.className = `bulk-preflight-state bulk-preflight-${chapter.preflightStatus}`;
      badge.textContent = preflightLabel(chapter.preflightStatus);
    }

    if (button) {
      button.disabled = uploadRunning || chapter.preflightStatus === 'checking';
      button.textContent = chapter.preflightStatus === 'ok'
        ? '👁 Xem lại'
        : chapter.preflightStatus === 'error'
          ? '🔎 Xem lỗi'
          : '👁 Xem & kiểm tra';
    }

    updatePreflightToolbar();
  }

  function updatePreflightToolbar() {
    const toolbar = document.getElementById('bulk-preflight-toolbar');
    const summary = document.getElementById('bulk-preflight-summary');
    const checkAllButton = document.getElementById('bulk-check-all');

    if (!toolbar || !summary || !checkAllButton) return;

    toolbar.style.display = chapters.length ? 'flex' : 'none';

    const checked = chapters.filter((chapter) => chapter.preflightStatus === 'ok').length;
    const checking = chapters.filter((chapter) => chapter.preflightStatus === 'checking').length;
    const failed = chapters.filter((chapter) => chapter.preflightStatus === 'error').length;

    if (!chapters.length) {
      summary.textContent = 'Chưa có chapter để kiểm tra.';
    } else if (checking) {
      summary.textContent = `Đang kiểm tra ${checking} chapter · ${checked}/${chapters.length} đã đạt.`;
    } else if (failed) {
      summary.textContent = `${checked}/${chapters.length} chapter đạt · ${failed} chapter có lỗi cần xem lại.`;
    } else if (checked === chapters.length) {
      summary.textContent = `Đã kiểm tra đủ ${checked}/${chapters.length} chapter. Có thể upload.`;
    } else {
      summary.textContent = `${checked}/${chapters.length} chapter đã kiểm tra. Upload chỉ mở khi tất cả đều đạt.`;
    }

    checkAllButton.disabled = uploadRunning || checking > 0 || !chapters.length;
    checkAllButton.textContent = checked === chapters.length && !failed
      ? '✓ Đã kiểm tra tất cả'
      : '✓ Kiểm tra tất cả Chapter';
  }

  function collectValidationIssues() {
    const issues = [];
    const numbers = new Map();
    let totalPages = 0;

    chapters.forEach((chapter, index) => {
      totalPages += chapter.files.length;

      const isNumValid = typeof chapter.chapterNumber === 'number' && Number.isFinite(chapter.chapterNumber) && chapter.chapterNumber >= 0;
      if (!isNumValid) {
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

      if (chapter.preflightStatus === 'error') {
        const firstError = chapter.preflightErrors[0];
        issues.push(
          firstError
            ? `Chapter ${chapter.chapterNumber ?? index + 1}: ${firstError}`
            : `Chapter ${chapter.chapterNumber ?? index + 1} có ảnh lỗi.`
        );
      }
    });

    numbers.forEach((count, number) => {
      if (count > 1) issues.push(`Chapter ${number} xuất hiện ${count} lần trong thư mục.`);
    });

    if (totalBytes > MAX_SESSION_BYTES) {
      issues.push('Tổng dung lượng vượt giới hạn an toàn 20GB / phiên upload.');
    }

    return { issues, totalPages };
  }

  function refreshValidation() {
    const { issues, totalPages } = collectValidationIssues();
    const checkedCount = chapters.filter((chapter) => chapter.preflightStatus === 'ok').length;
    const checkingCount = chapters.filter((chapter) => chapter.preflightStatus === 'checking').length;
    const uncheckedCount = chapters.length - checkedCount - chapters.filter((chapter) => chapter.preflightStatus === 'error').length - checkingCount;
    const allChecked = chapters.length > 0 && checkedCount === chapters.length;

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
    } else if (!allChecked) {
      validationBox.className = 'bulk-validation bulk-validation-neutral';
      validationBox.textContent = checkingCount
        ? `Đang kiểm tra ảnh. ${checkedCount}/${chapters.length} chapter đã đạt.`
        : `Đã nhận diện ${chapters.length} chapter. Còn ${uncheckedCount} chapter chưa kiểm tra ảnh. Bấm từng dòng để xem hoặc dùng "Kiểm tra tất cả Chapter".`;
    } else {
      validationBox.className = 'bulk-validation bulk-validation-ok';
      validationBox.textContent = `Sẵn sàng upload ${chapters.length} chapter. Tất cả chapter đã qua kiểm tra ảnh trước upload.${ignoredFileCount ? ` Đã bỏ qua ${ignoredFileCount} file không phải ảnh / file rác.` : ''}`;
    }

    uploadButton.disabled = uploadRunning || !chapters.length || issues.length > 0 || !allChecked;
    updatePreflightToolbar();
    return issues;
  }

  async function openChapterInspector(index, runCheck = false) {
    const chapter = chapters[index];
    if (!chapter) return;

    inspectorChapterIndex = index;
    inspectorRenderedPages = 0;
    revokeInspectorUrls();

    const inspector = document.getElementById('bulk-chapter-inspector');
    inspector.style.display = 'block';

    document.getElementById('bulk-inspector-title').textContent =
      `Chapter ${chapter.chapterNumber ?? '?'} · ${chapter.title || chapter.folderName}`;
    document.getElementById('bulk-inspector-subtitle').textContent =
      chapter.folderName;
    document.getElementById('bulk-inspector-pages').textContent = String(chapter.files.length);
    document.getElementById('bulk-inspector-size').textContent = humanBytes(chapter.size);

    renderInspectorStatus(chapter);
    renderInspectorPages(index, true);
    inspector.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    if (runCheck && chapter.preflightStatus === 'unchecked') {
      await inspectChapter(index, true);
    }
  }

  function closeInspector() {
    const inspector = document.getElementById('bulk-chapter-inspector');
    if (inspector) inspector.style.display = 'none';
    inspectorChapterIndex = -1;
    inspectorRenderedPages = 0;
    revokeInspectorUrls();

    const grid = document.getElementById('bulk-inspector-pages-grid');
    if (grid) grid.innerHTML = '';
  }

  function revokeInspectorUrls() {
    inspectorObjectUrls.forEach((url) => URL.revokeObjectURL(url));
    inspectorObjectUrls = [];
  }

  function renderInspectorStatus(chapter) {
    if (!chapter) return;

    const status = document.getElementById('bulk-inspector-status');
    const errors = document.getElementById('bulk-inspector-errors');
    const message = document.getElementById('bulk-inspector-message');
    const runButton = document.getElementById('bulk-inspector-run');

    status.textContent = preflightLabel(chapter.preflightStatus);
    errors.textContent = String(chapter.preflightErrors.length);
    message.className = 'bulk-inspector-message';

    if (chapter.preflightStatus === 'checking') {
      message.classList.add('is-checking');
      message.textContent = 'Đang mở và kiểm tra từng ảnh lần lượt. Không re-encode và không giữ toàn bộ ảnh trong RAM.';
      runButton.disabled = true;
      runButton.textContent = '⏳ Đang kiểm tra...';
    } else if (chapter.preflightStatus === 'ok') {
      message.classList.add('is-ok');
      message.textContent = `Đạt: ${chapter.files.length}/${chapter.files.length} ảnh đọc được, có kích thước hợp lệ và đúng thứ tự natural sort.`;
      runButton.disabled = uploadRunning;
      runButton.textContent = '↻ Kiểm tra lại Chapter';
    } else if (chapter.preflightStatus === 'error') {
      message.classList.add('is-error');
      message.textContent = chapter.preflightErrors[0] || 'Phát hiện ảnh lỗi trong chapter này.';
      runButton.disabled = uploadRunning;
      runButton.textContent = '↻ Kiểm tra lại sau khi sửa';
    } else {
      message.textContent = 'Bấm kiểm tra để quét từng ảnh. Hệ thống chỉ mở một ảnh tại một thời điểm để tránh ngốn RAM với folder vài GB.';
      runButton.disabled = uploadRunning;
      runButton.textContent = '🔎 Kiểm tra Chapter này';
    }
  }

  function renderInspectorPages(index, reset = false) {
    const chapter = chapters[index];
    const grid = document.getElementById('bulk-inspector-pages-grid');
    const moreButton = document.getElementById('bulk-inspector-more');
    if (!chapter || !grid || !moreButton) return;

    if (reset) {
      revokeInspectorUrls();
      grid.innerHTML = '';
      inspectorRenderedPages = 0;
    }

    const nextLimit = Math.min(chapter.files.length, inspectorRenderedPages + PREVIEW_PAGE_SIZE);

    for (let pageIndex = inspectorRenderedPages; pageIndex < nextLimit; pageIndex++) {
      const item = chapter.files[pageIndex];
      const meta = chapter.pageMeta[pageIndex] || {};
      const card = document.createElement('article');
      card.className = `bulk-preview-page${meta.error ? ' is-error' : ''}`;
      card.dataset.pageIndex = String(pageIndex);
      card.dataset.testid = 'bulk-preview-page';

      const img = document.createElement('img');
      img.loading = 'lazy';
      img.alt = `Trang ${pageIndex + 1} · ${item.file.name}`;
      const objectUrl = URL.createObjectURL(item.file);
      inspectorObjectUrls.push(objectUrl);
      img.src = objectUrl;

      img.addEventListener('error', () => {
        card.classList.add('is-error');
      });

      const info = document.createElement('div');
      info.className = 'bulk-preview-page-meta';

      const title = document.createElement('strong');
      title.title = item.innerPath;
      title.textContent = `#${pageIndex + 1} · ${item.file.name}`;

      const detail = document.createElement('span');
      detail.dataset.testid = 'bulk-preview-page-meta';
      detail.textContent = meta.width && meta.height
        ? `${meta.width}×${meta.height} · ${humanBytes(item.file.size)}`
        : humanBytes(item.file.size);

      info.append(title, detail);

      if (meta.error) {
        const errorLine = document.createElement('span');
        errorLine.className = 'bulk-preview-page-error';
        errorLine.textContent = meta.error;
        info.appendChild(errorLine);
      }

      card.append(img, info);
      grid.appendChild(card);
    }

    inspectorRenderedPages = nextLimit;
    moreButton.style.display = nextLimit < chapter.files.length ? 'inline-flex' : 'none';
    moreButton.textContent = nextLimit < chapter.files.length
      ? `Xem thêm ${Math.min(PREVIEW_PAGE_SIZE, chapter.files.length - nextLimit)} trang`
      : 'Đã hiển thị toàn bộ trang';
  }

  function refreshVisiblePreviewMeta(index) {
    if (inspectorChapterIndex !== index) return;

    const chapter = chapters[index];
    const grid = document.getElementById('bulk-inspector-pages-grid');
    if (!chapter || !grid) return;

    grid.querySelectorAll('.bulk-preview-page').forEach((card) => {
      const pageIndex = Number.parseInt(card.dataset.pageIndex || '-1', 10);
      if (pageIndex < 0) return;

      const meta = chapter.pageMeta[pageIndex] || {};
      const detail = card.querySelector('[data-testid="bulk-preview-page-meta"]');
      if (detail) {
        detail.textContent = meta.width && meta.height
          ? `${meta.width}×${meta.height} · ${humanBytes(chapter.files[pageIndex].file.size)}`
          : humanBytes(chapter.files[pageIndex].file.size);
      }

      let errorLine = card.querySelector('.bulk-preview-page-error');
      if (meta.error) {
        card.classList.add('is-error');
        if (!errorLine) {
          errorLine = document.createElement('span');
          errorLine.className = 'bulk-preview-page-error';
          card.querySelector('.bulk-preview-page-meta')?.appendChild(errorLine);
        }
        errorLine.textContent = meta.error;
      } else {
        card.classList.remove('is-error');
        errorLine?.remove();
      }
    });

    renderInspectorStatus(chapter);
  }

  async function inspectChapter(index, keepOpen = false) {
    const chapter = chapters[index];
    if (!chapter || uploadRunning || chapter.preflightStatus === 'checking') return false;

    chapter.preflightStatus = 'checking';
    chapter.preflightErrors = [];
    chapter.pageMeta = new Array(chapter.files.length);
    updatePreflightState(index);
    refreshValidation();

    if (keepOpen && inspectorChapterIndex !== index) {
      await openChapterInspector(index, false);
    } else if (inspectorChapterIndex === index) {
      renderInspectorStatus(chapter);
    }

    for (let pageIndex = 0; pageIndex < chapter.files.length; pageIndex++) {
      const item = chapter.files[pageIndex];
      const pageError = validatePageFile(item.file);

      if (pageError) {
        chapter.pageMeta[pageIndex] = { error: pageError };
        chapter.preflightErrors.push(`Trang ${pageIndex + 1} (${item.file.name}): ${pageError}`);
        refreshVisiblePreviewMeta(index);
        continue;
      }

      try {
        const dimensions = await readImageDimensions(item.file);
        chapter.pageMeta[pageIndex] = {
          width: dimensions.width,
          height: dimensions.height,
          error: null,
        };
      } catch {
        const message = 'Không thể giải mã ảnh, file có thể hỏng hoặc sai định dạng.';
        chapter.pageMeta[pageIndex] = { error: message };
        chapter.preflightErrors.push(`Trang ${pageIndex + 1} (${item.file.name}): ${message}`);
      }

      if (pageIndex % 8 === 7) {
        refreshVisiblePreviewMeta(index);
        await sleep(0);
      }
    }

    chapter.preflightStatus = chapter.preflightErrors.length ? 'error' : 'ok';
    updatePreflightState(index);
    refreshVisiblePreviewMeta(index);
    refreshValidation();

    return chapter.preflightStatus === 'ok';
  }

  async function inspectAllChapters() {
    if (uploadRunning || !chapters.length) return;

    const button = document.getElementById('bulk-check-all');
    if (button) {
      button.disabled = true;
      button.textContent = '⏳ Đang kiểm tra tất cả...';
    }

    for (let index = 0; index < chapters.length; index++) {
      const chapter = chapters[index];
      if (chapter.preflightStatus === 'ok') continue;
      await inspectChapter(index, inspectorChapterIndex === index);
    }

    updatePreflightToolbar();
    refreshValidation();
  }

  function validatePageFile(file) {
    if (!file || file.size <= 0) {
      return 'File rỗng.';
    }

    if (file.size > MAX_IMAGE_BYTES) {
      return 'Ảnh vượt quá 20MB.';
    }

    const extension = (file.name.split('.').pop() || '').toLowerCase();
    if (!allowedExtensions.has(extension)) {
      return `Định dạng .${extension || '?'} không được hỗ trợ.`;
    }

    return null;
  }

  async function readImageDimensions(file) {
    if ('createImageBitmap' in window) {
      const bitmap = await createImageBitmap(file);
      const dimensions = { width: bitmap.width, height: bitmap.height };
      bitmap.close();

      if (!dimensions.width || !dimensions.height) {
        throw new Error('Invalid dimensions');
      }

      return dimensions;
    }

    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(file);
      const img = new Image();

      img.onload = () => {
        const dimensions = { width: img.naturalWidth, height: img.naturalHeight };
        URL.revokeObjectURL(url);

        if (!dimensions.width || !dimensions.height) {
          reject(new Error('Invalid dimensions'));
          return;
        }

        resolve(dimensions);
      };

      img.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('Image decode failed'));
      };

      img.src = url;
    });
  }

  async function runUpload() {
    const issues = refreshValidation();
    const allChecked = chapters.length > 0 && chapters.every((chapter) => chapter.preflightStatus === 'ok');
    if (issues.length || !allChecked) return;

    uploadRunning = true;
    uploadButton.disabled = true;
    folderInput.disabled = true;
    updatePreflightToolbar();
    renderChapterTable();
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
      validationBox.innerHTML = `Đã tạo thành công <strong>${completed.chapters_created}</strong> chapter. Ảnh đã qua kiểm tra trước upload, giữ nguyên byte gốc và xác minh SHA-256 trước + sau khi lưu. <a href="${chaptersUrl}">Mở danh sách chapter →</a>`;
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
      renderChapterTable();
      updatePreflightToolbar();
      uploadButton.disabled = refreshValidation().length > 0 || !chapters.every((chapter) => chapter.preflightStatus === 'ok');
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
