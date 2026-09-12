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

  // State for Lightbox
  const lightboxState = {
    isOpen: false,
    chapterIndex: -1,
    pageIndex: -1,
    zoomLevel: 1,
  };
  let lightboxTempUrl = null;

  // State for Upload Errors
  let lastUploadError = null;
  const failedBatchPages = new Set();
  let pendingReplacePageIndex = -1;

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

    if (!document.getElementById('bulk-error-panel')) {
      const errorPanel = document.createElement('section');
      errorPanel.id = 'bulk-error-panel';
      errorPanel.className = 'bulk-error-panel';
      errorPanel.dataset.testid = 'bulk-error-panel';
      errorPanel.style.display = 'none';
      errorPanel.innerHTML = `
        <div class="bulk-error-header">
          <div>
            <span class="bulk-error-badge">⚠ UPLOAD GẶP LỖI</span>
            <h4 id="bulk-error-heading">Chi tiết lỗi upload dữ liệu</h4>
          </div>
          <button type="button" id="bulk-error-dismiss" class="btn-admin btn-admin-ghost btn-sm" title="Đóng panel lỗi">✕</button>
        </div>

        <div class="bulk-error-grid">
          <div><span>Chapter:</span><strong id="bulk-error-chapter">-</strong></div>
          <div><span>Thứ tự chapter:</span><strong id="bulk-error-chapter-index">-</strong></div>
          <div><span>Batch:</span><strong id="bulk-error-batch">-</strong></div>
          <div><span>Mã HTTP:</span><strong id="bulk-error-http">-</strong></div>
          <div><span>Các trang:</span><strong id="bulk-error-pages">-</strong></div>
          <div><span>Đã upload:</span><strong id="bulk-error-progress">-</strong></div>
        </div>

        <div class="bulk-error-files-wrap">
          <span>File trong batch:</span>
          <div id="bulk-error-files" class="bulk-error-files-list"></div>
        </div>

        <div class="bulk-error-message-box">
          <strong>Thông báo lỗi:</strong>
          <p id="bulk-error-message-text">-</p>
        </div>

        <div class="bulk-error-actions">
          <button type="button" id="bulk-error-retry-btn" class="btn-admin btn-admin-primary btn-sm">
            ↻ Thử lại Chapter này
          </button>
          <button type="button" id="bulk-error-copy-btn" class="btn-admin btn-admin-ghost btn-sm">
            📋 Sao chép chi tiết lỗi
          </button>
          <button type="button" id="bulk-error-view-chapter-btn" class="btn-admin btn-admin-ghost btn-sm">
            👁 Xem Chapter
          </button>
        </div>

        <details id="bulk-error-details" class="bulk-error-details">
          <summary>Chi tiết kỹ thuật (dành cho Admin)</summary>
          <pre id="bulk-error-raw"></pre>
        </details>
      `;

      if (progressWrap) {
        progressWrap.after(errorPanel);
      } else if (tableWrap) {
        tableWrap.before(errorPanel);
      }

      errorPanel.querySelector('#bulk-error-dismiss')?.addEventListener('click', () => {
        errorPanel.style.display = 'none';
      });

      errorPanel.querySelector('#bulk-error-retry-btn')?.addEventListener('click', () => {
        if (!uploadRunning) runUpload();
      });

      errorPanel.querySelector('#bulk-error-copy-btn')?.addEventListener('click', copyErrorDetails);

      errorPanel.querySelector('#bulk-error-view-chapter-btn')?.addEventListener('click', () => {
        if (lastUploadError && lastUploadError.chapterIndex >= 0) {
          openChapterInspector(lastUploadError.chapterIndex, false);
        }
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
            <div class="bulk-inspector-kicker">PRE-UPLOAD CHECK &amp; EDIT</div>
            <h3 id="bulk-inspector-title">Chapter</h3>
            <p id="bulk-inspector-subtitle">Kiểm tra thứ tự trang, kích thước, ảnh hỏng và chỉnh sửa trước khi upload.</p>
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
          <button type="button" id="bulk-inspector-add" class="btn-admin btn-admin-ghost btn-sm" title="Chọn thêm ảnh từ máy để bổ sung vào cuối chapter">➕ Thêm ảnh</button>
          <button type="button" id="bulk-inspector-reset" class="btn-admin btn-admin-ghost btn-sm" title="Khôi phục lại danh sách ảnh gốc của chapter">↺ Khôi phục ban đầu</button>
          <input type="file" id="bulk-inspector-add-input" multiple accept="image/*" style="display:none" />
          <input type="file" id="bulk-inspector-replace-input" accept="image/*" style="display:none" />
          <span>Kéo thả thumbnail để đổi thứ tự trang. Mọi thay đổi sẽ yêu cầu kiểm tra lại trước khi upload.</span>
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

      const addBtn = inspector.querySelector('#bulk-inspector-add');
      const addInput = inspector.querySelector('#bulk-inspector-add-input');
      const resetBtn = inspector.querySelector('#bulk-inspector-reset');
      const replaceInput = inspector.querySelector('#bulk-inspector-replace-input');

      addBtn?.addEventListener('click', () => {
        if (uploadRunning) return;
        addInput.value = '';
        addInput.click();
      });

      addInput?.addEventListener('change', () => {
        if (inspectorChapterIndex >= 0 && addInput.files && addInput.files.length) {
          addInspectorPages(inspectorChapterIndex, Array.from(addInput.files));
        }
      });

      replaceInput?.addEventListener('change', () => {
        if (inspectorChapterIndex >= 0 && pendingReplacePageIndex >= 0 && replaceInput.files && replaceInput.files.length) {
          replaceInspectorPage(inspectorChapterIndex, pendingReplacePageIndex, replaceInput.files[0]);
        }
      });

      resetBtn?.addEventListener('click', () => {
        if (inspectorChapterIndex >= 0 && !uploadRunning) {
          resetInspectorChapter(inspectorChapterIndex);
        }
      });
    }

    if (!document.getElementById('bulk-preflight-lightbox')) {
      const lightbox = document.createElement('div');
      lightbox.id = 'bulk-preflight-lightbox';
      lightbox.className = 'bulk-lightbox-overlay';
      lightbox.dataset.testid = 'bulk-preflight-lightbox';
      lightbox.style.display = 'none';
      lightbox.innerHTML = `
        <div class="bulk-lightbox-bar">
          <div class="bulk-lightbox-title-area">
            <strong id="bulk-lightbox-pos">1 / 1</strong>
            <span id="bulk-lightbox-title">page.jpg</span>
            <span id="bulk-lightbox-meta" class="bulk-lightbox-dim">-</span>
          </div>
          <div class="bulk-lightbox-ctrls">
            <button type="button" id="bulk-lightbox-zoom-out" class="bulk-lightbox-btn" title="Thu nhỏ (-)">−</button>
            <button type="button" id="bulk-lightbox-zoom-reset" class="bulk-lightbox-btn" title="Kích thước gốc (0)">100%</button>
            <button type="button" id="bulk-lightbox-zoom-in" class="bulk-lightbox-btn" title="Phóng to (+)">+</button>
            <button type="button" id="bulk-lightbox-close" class="bulk-lightbox-btn close" title="Đóng (Escape)">✕</button>
          </div>
        </div>

        <button type="button" id="bulk-lightbox-prev" class="bulk-lightbox-nav prev" title="Trang trước (ArrowLeft)">‹</button>
        <button type="button" id="bulk-lightbox-next" class="bulk-lightbox-nav next" title="Trang sau (ArrowRight)">›</button>

        <div id="bulk-lightbox-body" class="bulk-lightbox-body">
          <div id="bulk-lightbox-img-wrap" class="bulk-lightbox-img-wrap">
            <img id="bulk-lightbox-img" src="" alt="Trang truyện lớn" />
          </div>
        </div>
      `;
      document.body.appendChild(lightbox);

      lightbox.querySelector('#bulk-lightbox-close')?.addEventListener('click', closeLightbox);
      lightbox.querySelector('#bulk-lightbox-prev')?.addEventListener('click', prevLightboxPage);
      lightbox.querySelector('#bulk-lightbox-next')?.addEventListener('click', nextLightboxPage);
      lightbox.querySelector('#bulk-lightbox-zoom-in')?.addEventListener('click', () => zoomLightbox(0.2));
      lightbox.querySelector('#bulk-lightbox-zoom-out')?.addEventListener('click', () => zoomLightbox(-0.2));
      lightbox.querySelector('#bulk-lightbox-zoom-reset')?.addEventListener('click', resetLightboxZoom);

      const bodyEl = lightbox.querySelector('#bulk-lightbox-body');
      bodyEl?.addEventListener('click', (e) => {
        if (e.target === bodyEl || e.target.id === 'bulk-lightbox-img-wrap') {
          closeLightbox();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (!lightboxState.isOpen) return;
        if (e.key === 'Escape') {
          e.preventDefault();
          closeLightbox();
        } else if (e.key === 'ArrowLeft') {
          e.preventDefault();
          prevLightboxPage();
        } else if (e.key === 'ArrowRight') {
          e.preventDefault();
          nextLightboxPage();
        } else if (e.key === '+' || e.key === '=') {
          e.preventDefault();
          zoomLightbox(0.2);
        } else if (e.key === '-') {
          e.preventDefault();
          zoomLightbox(-0.2);
        } else if (e.key === '0') {
          e.preventDefault();
          resetLightboxZoom();
        }
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
          grid-template-columns:repeat(auto-fill,minmax(136px,1fr));
          gap:12px;
          margin-top:14px;
        }
        .bulk-preview-page {
          min-width:0;
          border:1px solid var(--admin-border);
          border-radius:9px;
          background:rgba(255,255,255,.035);
          overflow:hidden;
          position:relative;
          transition:transform .12s ease, border-color .12s ease, box-shadow .12s ease;
          user-select:none;
        }
        .bulk-preview-page.is-dragging {
          opacity: 0.35;
          border: 2px dashed #818cf8;
          transform: scale(0.96);
        }
        .bulk-preview-page.is-drag-over {
          border-color: #6366f1 !important;
          box-shadow: 0 0 0 2px #6366f1;
          background: rgba(99, 102, 241, 0.15);
        }
        .bulk-preview-page.is-batch-error {
          border-color: #ef4444 !important;
          box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.55);
          background: rgba(239, 68, 68, 0.08);
        }
        .bulk-preview-page-thumb {
          position: relative;
          overflow: hidden;
        }
        .bulk-preview-page-thumb img {
          display:block;
          width:100%;
          aspect-ratio:3/4;
          object-fit:contain;
          background:#09090b;
          cursor:zoom-in;
        }
        .bulk-preview-page-actions {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          padding: 4px 6px;
          display: flex;
          gap: 3px;
          background: linear-gradient(to bottom, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%);
          justify-content: flex-end;
          opacity: 0.92;
        }
        .bulk-card-action-btn {
          background: rgba(20, 20, 32, 0.85);
          border: 1px solid rgba(255, 255, 255, 0.2);
          color: #fff;
          border-radius: 4px;
          width: 22px;
          height: 22px;
          font-size: 11px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          padding: 0;
          transition: all .12s ease;
        }
        .bulk-card-action-btn:hover {
          background: #6366f1;
          border-color: #818cf8;
          transform: scale(1.1);
        }
        .bulk-card-action-btn.bulk-btn-delete:hover {
          background: #ef4444;
          border-color: #f87171;
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
        .bulk-page-batch-error-badge {
          display: inline-block;
          margin-top: 3px;
          padding: 1px 5px;
          border-radius: 4px;
          background: rgba(239,68,68,0.25);
          color: #fca5a5 !important;
          font-size: 9px;
          font-weight: 700;
        }
        .bulk-inspector-more-wrap { margin-top:12px; text-align:center; }

        /* Lightbox CSS */
        .bulk-lightbox-overlay {
          position: fixed;
          inset: 0;
          z-index: 99999;
          background: rgba(0, 0, 0, 0.92);
          backdrop-filter: blur(8px);
          display: flex;
          flex-direction: column;
        }
        body.bulk-lightbox-active {
          overflow: hidden !important;
        }
        .bulk-lightbox-bar {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 10px 18px;
          background: rgba(15, 15, 25, 0.9);
          border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          color: #fff;
          z-index: 10;
        }
        .bulk-lightbox-title-area {
          display: flex;
          align-items: center;
          gap: 12px;
          font-size: 13px;
        }
        .bulk-lightbox-title-area strong {
          background: #6366f1;
          padding: 2px 8px;
          border-radius: 999px;
          font-size: 11px;
        }
        .bulk-lightbox-dim {
          color: #94a3b8;
          font-size: 11px;
        }
        .bulk-lightbox-ctrls {
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .bulk-lightbox-btn {
          background: rgba(255, 255, 255, 0.1);
          border: 1px solid rgba(255, 255, 255, 0.15);
          color: #fff;
          border-radius: 6px;
          padding: 5px 10px;
          cursor: pointer;
          font-size: 13px;
          transition: all .15s ease;
        }
        .bulk-lightbox-btn:hover {
          background: rgba(255, 255, 255, 0.22);
        }
        .bulk-lightbox-btn.close {
          background: rgba(239, 68, 68, 0.25);
          border-color: rgba(239, 68, 68, 0.4);
        }
        .bulk-lightbox-btn.close:hover {
          background: rgba(239, 68, 68, 0.45);
        }
        .bulk-lightbox-body {
          flex: 1;
          overflow-y: auto;
          overflow-x: hidden;
          display: flex;
          align-items: flex-start;
          justify-content: center;
          padding: 20px;
          position: relative;
          cursor: default;
        }
        .bulk-lightbox-img-wrap {
          display: flex;
          justify-content: center;
          align-items: center;
          min-height: 100%;
        }
        .bulk-lightbox-img-wrap img {
          display: block;
          max-width: 900px;
          width: 100%;
          height: auto;
          object-fit: contain;
          box-shadow: 0 10px 40px rgba(0,0,0,0.85);
          border-radius: 4px;
          transition: transform 0.12s ease;
          transform-origin: center top;
        }
        .bulk-lightbox-nav {
          position: fixed;
          top: 50%;
          transform: translateY(-50%);
          background: rgba(20, 20, 30, 0.7);
          border: 1px solid rgba(255, 255, 255, 0.15);
          color: #fff;
          font-size: 32px;
          width: 50px;
          height: 70px;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          z-index: 10;
          transition: all .15s ease;
        }
        .bulk-lightbox-nav:hover:not(:disabled) {
          background: rgba(99, 102, 241, 0.8);
        }
        .bulk-lightbox-nav:disabled {
          opacity: 0.2;
          cursor: not-allowed;
        }
        .bulk-lightbox-nav.prev { left: 16px; }
        .bulk-lightbox-nav.next { right: 16px; }

        /* Error Panel CSS */
        .bulk-error-panel {
          margin-top: 16px;
          border: 1px solid rgba(239, 68, 68, 0.45);
          border-radius: 12px;
          background: rgba(239, 68, 68, 0.08);
          padding: 16px;
          color: #fecaca;
        }
        .bulk-error-header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          gap: 12px;
        }
        .bulk-error-badge {
          display: inline-block;
          padding: 2px 7px;
          background: #ef4444;
          color: #fff;
          border-radius: 4px;
          font-size: 10px;
          font-weight: 800;
          letter-spacing: .05em;
          margin-bottom: 4px;
        }
        .bulk-error-header h4 {
          margin: 0;
          font-size: 16px;
          color: #fff;
        }
        .bulk-error-grid {
          display: grid;
          grid-template-columns: repeat(3, minmax(0, 1fr));
          gap: 10px;
          margin-top: 14px;
          padding: 10px;
          border-radius: 8px;
          background: rgba(0, 0, 0, 0.25);
          border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .bulk-error-grid > div span {
          display: block;
          font-size: 11px;
          color: #cbd5e1;
        }
        .bulk-error-grid > div strong {
          display: block;
          font-size: 13px;
          margin-top: 2px;
          color: #fff;
        }
        .bulk-error-files-wrap {
          margin-top: 12px;
        }
        .bulk-error-files-wrap > span {
          display: block;
          font-size: 11px;
          color: #cbd5e1;
          margin-bottom: 5px;
        }
        .bulk-error-files-list {
          display: flex;
          flex-wrap: wrap;
          gap: 6px;
        }
        .bulk-error-file-item {
          display: inline-block;
          padding: 3px 8px;
          border-radius: 6px;
          background: rgba(255, 255, 255, 0.08);
          border: 1px solid rgba(255, 255, 255, 0.12);
          font-size: 11px;
          font-family: monospace;
          color: #f1f5f9;
        }
        .bulk-error-file-item.more {
          color: #94a3b8;
          border-style: dashed;
        }
        .bulk-error-message-box {
          margin-top: 12px;
          padding: 10px 12px;
          border-radius: 8px;
          background: rgba(239, 68, 68, 0.18);
          border: 1px solid rgba(239, 68, 68, 0.35);
        }
        .bulk-error-message-box strong {
          display: block;
          font-size: 11.5px;
          color: #fca5a5;
          margin-bottom: 3px;
        }
        .bulk-error-message-box p {
          margin: 0;
          font-size: 13px;
          color: #fff;
          font-weight: 500;
        }
        .bulk-error-actions {
          display: flex;
          gap: 10px;
          flex-wrap: wrap;
          margin-top: 14px;
        }
        .bulk-error-details {
          margin-top: 14px;
          border-top: 1px solid rgba(255, 255, 255, 0.08);
          padding-top: 10px;
        }
        .bulk-error-details summary {
          cursor: pointer;
          font-size: 12px;
          color: #cbd5e1;
        }
        .bulk-error-details pre {
          margin-top: 8px;
          padding: 10px;
          background: rgba(0, 0, 0, 0.4);
          border-radius: 6px;
          font-size: 11px;
          font-family: monospace;
          color: #e2e8f0;
          overflow-x: auto;
        }

        @media (max-width:700px) {
          .bulk-inspector-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
          .bulk-inspector-pages-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
          .bulk-error-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
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
    lastUploadError = null;
    failedBatchPages.clear();
    const errorPanel = document.getElementById('bulk-error-panel');
    if (errorPanel) errorPanel.style.display = 'none';
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

      // Keep originalFiles snapshot for Reset feature (A7)
      const originalFiles = group.files.map((item) => ({
        file: item.file,
        innerPath: item.innerPath,
      }));

      return {
        ...group,
        originalFiles,
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
      const comp = compareChapterNumbers(a.chapterNumber, b.chapterNumber);
      if (comp !== 0) return comp;
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

  function normalizeChapterNumber(val) {
    if (val === null || val === undefined) return null;
    const str = String(val).trim();
    if (!/^\d+(?:\.\d+)?$/.test(str)) return null;

    let [intPart, decPart] = str.split('.');
    intPart = intPart.replace(/^0+/, '') || '0';
    if (decPart !== undefined) {
      decPart = decPart.replace(/0+$/, '');
      if (decPart !== '') {
        return `${intPart}.${decPart}`;
      }
    }
    return intPart;
  }

  function compareChapterNumbers(a, b) {
    const normA = normalizeChapterNumber(a);
    const normB = normalizeChapterNumber(b);

    if (normA === null && normB === null) return 0;
    if (normA === null) return 1;
    if (normB === null) return -1;
    if (normA === normB) return 0;

    const [intA, decA = ''] = normA.split('.');
    const [intB, decB = ''] = normB.split('.');

    if (intA.length !== intB.length) {
      return intA.length - intB.length;
    }
    if (intA !== intB) {
      return intA.localeCompare(intB);
    }

    const maxDecLen = Math.max(decA.length, decB.length);
    const padA = decA.padEnd(maxDecLen, '0');
    const padB = decB.padEnd(maxDecLen, '0');

    return padA.localeCompare(padB);
  }

  function parseChapterFolderName(folderName) {
    let match = folderName.match(/\bCh(?:apter)?\.?\s*(\d+(?:\.\d+)?)(?=[^\d.]|$)/i);
    if (!match) {
      match = folderName.match(/^(?:Vol\.?\s*\d+[\s._\-–—]+)?(\d+(?:\.\d+)?)(?=[^\d.]|$)/i);
    }
    if (!match) {
      return { chapterNumber: null, title: cleanFolderTitle(folderName) };
    }

    const norm = normalizeChapterNumber(match[1]);
    let title = folderName.slice((match.index || 0) + match[0].length);
    title = title.replace(/^[\s._\-–—:]+/, '').trim();
    title = title.replace(/\s*\((?:en|vi|jp|ja|kr|ko)\)\s*(?:\[.*)?$/i, '').trim();
    title = title.replace(/\s*\[[^\]]*\]\s*$/g, '').trim();

    return {
      chapterNumber: norm,
      title: title || (norm !== null ? `Chapter ${norm}` : cleanFolderTitle(folderName)),
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
      numberInput.type = 'text';
      numberInput.className = 'form-control bulk-chapter-number';
      numberInput.dataset.testid = 'bulk-chapter-number';
      numberInput.value = chapter.chapterNumber ?? '';
      numberInput.placeholder = 'VD: 140 hoặc 187.5';
      numberInput.addEventListener('input', () => {
        const val = numberInput.value.trim();
        const norm = normalizeChapterNumber(val);
        chapter.chapterNumber = norm !== null ? norm : (val === '' ? null : val);
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

      const norm = normalizeChapterNumber(chapter.chapterNumber);
      const isNumValid = norm !== null;
      if (!isNumValid) {
        issues.push(`Folder "${chapter.folderName}" chưa nhận diện được số chapter hợp lệ.`);
      } else {
        const count = numbers.get(norm) || 0;
        numbers.set(norm, count + 1);
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
      message.textContent = 'Bấm kiểm tra để quét từng ảnh. Có thể thêm, xóa, thay ảnh hoặc kéo thả để đổi thứ tự.';
      runButton.disabled = uploadRunning;
      runButton.textContent = '🔎 Kiểm tra Chapter này';
    }
  }

  // ==========================================
  // PART A: PRE-UPLOAD CHECK & EDITING
  // ==========================================

  // Mandatory Invalidation Rule (A8)
  function invalidateChapterPreflight(index) {
    const chapter = chapters[index];
    if (!chapter) return;

    chapter.preflightStatus = 'unchecked';
    chapter.preflightErrors = [];
    chapter.pageMeta = [];
    chapter.size = chapter.files.reduce((sum, item) => sum + item.file.size, 0);

    totalBytes = chapters.reduce((sum, ch) => sum + ch.size, 0);

    // If lightbox is currently open on this chapter
    if (lightboxState.isOpen && lightboxState.chapterIndex === index) {
      if (chapter.files.length === 0) {
        closeLightbox();
      } else {
        lightboxState.pageIndex = Math.min(lightboxState.pageIndex, chapter.files.length - 1);
        renderLightboxPage();
      }
    }

    renderChapterTable();
    updateChapterStatus(index, 'pending', 'Chờ upload');

    if (inspectorChapterIndex === index) {
      document.getElementById('bulk-inspector-pages').textContent = String(chapter.files.length);
      document.getElementById('bulk-inspector-size').textContent = humanBytes(chapter.size);
      renderInspectorStatus(chapter);
      renderInspectorPages(index, true);
    }

    refreshValidation();
    updatePreflightToolbar();
  }

  // A3: Delete Page
  function deleteInspectorPage(chapterIndex, pageIndex) {
    const chapter = chapters[chapterIndex];
    if (!chapter || uploadRunning) return;

    if (chapter.files.length <= 1) {
      alert('Chapter phải có ít nhất 1 trang ảnh.');
      return;
    }

    chapter.files.splice(pageIndex, 1);
    invalidateChapterPreflight(chapterIndex);
  }

  // A4: Replace Page
  function startReplaceInspectorPage(chapterIndex, pageIndex) {
    if (uploadRunning) return;
    pendingReplacePageIndex = pageIndex;
    const input = document.getElementById('bulk-inspector-replace-input');
    if (input) {
      input.value = '';
      input.click();
    }
  }

  function replaceInspectorPage(chapterIndex, pageIndex, newFile) {
    const chapter = chapters[chapterIndex];
    if (!chapter || !newFile || uploadRunning) return;

    const ext = (newFile.name.split('.').pop() || '').toLowerCase();
    if (!allowedExtensions.has(ext)) {
      alert(`Định dạng .${ext} không được hỗ trợ. Chỉ nhận: jpg, png, webp, gif, avif.`);
      return;
    }

    chapter.files[pageIndex] = {
      file: newFile,
      innerPath: newFile.name,
    };

    checksumCache.delete(newFile);
    invalidateChapterPreflight(chapterIndex);
  }

  // A5: Add Pages
  function addInspectorPages(chapterIndex, newFiles) {
    const chapter = chapters[chapterIndex];
    if (!chapter || !newFiles.length || uploadRunning) return;

    let addedCount = 0;
    newFiles.forEach((file) => {
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (allowedExtensions.has(ext) && file.size > 0) {
        chapter.files.push({
          file,
          innerPath: file.name,
        });
        addedCount++;
      }
    });

    if (addedCount > 0) {
      invalidateChapterPreflight(chapterIndex);
    } else {
      alert('Không có file ảnh hợp lệ nào được thêm.');
    }
  }

  // A6: Reorder
  function moveInspectorPage(chapterIndex, fromIndex, toIndex) {
    const chapter = chapters[chapterIndex];
    if (!chapter || uploadRunning) return;
    if (toIndex < 0 || toIndex >= chapter.files.length || fromIndex === toIndex) return;

    const [item] = chapter.files.splice(fromIndex, 1);
    chapter.files.splice(toIndex, 0, item);

    invalidateChapterPreflight(chapterIndex);
  }

  // A7: Reset
  function resetInspectorChapter(chapterIndex) {
    const chapter = chapters[chapterIndex];
    if (!chapter || uploadRunning) return;
    if (!chapter.originalFiles || !chapter.originalFiles.length) return;

    chapter.files = chapter.originalFiles.map((item) => ({
      file: item.file,
      innerPath: item.innerPath,
    }));

    invalidateChapterPreflight(chapterIndex);
  }

  // A2: Lightbox Functions
  function openLightbox(chapterIndex, pageIndex) {
    const chapter = chapters[chapterIndex];
    if (!chapter || !chapter.files[pageIndex]) return;

    lightboxState.isOpen = true;
    lightboxState.chapterIndex = chapterIndex;
    lightboxState.pageIndex = pageIndex;
    lightboxState.zoomLevel = 1;

    const lightbox = document.getElementById('bulk-preflight-lightbox');
    if (lightbox) {
      lightbox.style.display = 'flex';
      document.body.classList.add('bulk-lightbox-active');
      renderLightboxPage();
    }
  }

  function closeLightbox() {
    lightboxState.isOpen = false;
    lightboxState.chapterIndex = -1;
    lightboxState.pageIndex = -1;
    lightboxState.zoomLevel = 1;

    const lightbox = document.getElementById('bulk-preflight-lightbox');
    if (lightbox) {
      lightbox.style.display = 'none';
      document.body.classList.remove('bulk-lightbox-active');
      const img = document.getElementById('bulk-lightbox-img');
      if (img) img.src = '';
    }

    if (lightboxTempUrl) {
      URL.revokeObjectURL(lightboxTempUrl);
      lightboxTempUrl = null;
    }
  }

  function renderLightboxPage() {
    if (!lightboxState.isOpen) return;
    const chapter = chapters[lightboxState.chapterIndex];
    if (!chapter || !chapter.files[lightboxState.pageIndex]) return;

    const item = chapter.files[lightboxState.pageIndex];
    const meta = chapter.pageMeta[lightboxState.pageIndex] || {};
    const img = document.getElementById('bulk-lightbox-img');
    const title = document.getElementById('bulk-lightbox-title');
    const pos = document.getElementById('bulk-lightbox-pos');
    const metaEl = document.getElementById('bulk-lightbox-meta');
    const prevBtn = document.getElementById('bulk-lightbox-prev');
    const nextBtn = document.getElementById('bulk-lightbox-next');

    let url = '';
    if (inspectorChapterIndex === lightboxState.chapterIndex && inspectorObjectUrls[lightboxState.pageIndex]) {
      url = inspectorObjectUrls[lightboxState.pageIndex];
    } else {
      if (lightboxTempUrl) URL.revokeObjectURL(lightboxTempUrl);
      lightboxTempUrl = URL.createObjectURL(item.file);
      url = lightboxTempUrl;
    }

    if (img) {
      img.src = url;
      img.style.transform = `scale(${lightboxState.zoomLevel})`;
    }

    if (pos) pos.textContent = `${lightboxState.pageIndex + 1} / ${chapter.files.length}`;
    if (title) title.textContent = item.file.name;
    if (metaEl) {
      metaEl.textContent = meta.width && meta.height
        ? `${meta.width}×${meta.height} · ${humanBytes(item.file.size)}`
        : humanBytes(item.file.size);
    }

    if (prevBtn) prevBtn.disabled = lightboxState.pageIndex <= 0;
    if (nextBtn) nextBtn.disabled = lightboxState.pageIndex >= chapter.files.length - 1;
  }

  function prevLightboxPage() {
    if (!lightboxState.isOpen) return;
    if (lightboxState.pageIndex > 0) {
      lightboxState.pageIndex--;
      lightboxState.zoomLevel = 1;
      renderLightboxPage();
    }
  }

  function nextLightboxPage() {
    if (!lightboxState.isOpen) return;
    const chapter = chapters[lightboxState.chapterIndex];
    if (chapter && lightboxState.pageIndex < chapter.files.length - 1) {
      lightboxState.pageIndex++;
      lightboxState.zoomLevel = 1;
      renderLightboxPage();
    }
  }

  function zoomLightbox(delta) {
    if (!lightboxState.isOpen) return;
    lightboxState.zoomLevel = Math.max(0.4, Math.min(3.0, lightboxState.zoomLevel + delta));
    const img = document.getElementById('bulk-lightbox-img');
    if (img) img.style.transform = `scale(${lightboxState.zoomLevel})`;
  }

  function resetLightboxZoom() {
    if (!lightboxState.isOpen) return;
    lightboxState.zoomLevel = 1;
    const img = document.getElementById('bulk-lightbox-img');
    if (img) img.style.transform = 'scale(1)';
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
      const isBatchError = inspectorChapterIndex === lastUploadError?.chapterIndex && failedBatchPages.has(pageIndex);

      const card = document.createElement('article');
      card.className = `bulk-preview-page${meta.error ? ' is-error' : ''}${isBatchError ? ' is-batch-error' : ''}`;
      card.dataset.pageIndex = String(pageIndex);
      card.dataset.testid = 'bulk-preview-page';
      card.setAttribute('draggable', 'true');

      // Drag and drop HTML5 handlers
      card.addEventListener('dragstart', (e) => {
        if (uploadRunning) { e.preventDefault(); return; }
        e.dataTransfer.setData('text/plain', String(pageIndex));
        e.dataTransfer.effectAllowed = 'move';
        card.classList.add('is-dragging');
      });

      card.addEventListener('dragend', () => {
        card.classList.remove('is-dragging');
        grid.querySelectorAll('.bulk-preview-page').forEach((el) => el.classList.remove('is-drag-over'));
      });

      card.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        card.classList.add('is-drag-over');
      });

      card.addEventListener('dragleave', () => {
        card.classList.remove('is-drag-over');
      });

      card.addEventListener('drop', (e) => {
        e.preventDefault();
        card.classList.remove('is-drag-over');
        const fromIndex = Number.parseInt(e.dataTransfer.getData('text/plain'), 10);
        const toIndex = pageIndex;
        if (!Number.isNaN(fromIndex) && fromIndex !== toIndex) {
          moveInspectorPage(index, fromIndex, toIndex);
        }
      });

      // Thumbnail with actions toolbar
      const thumbWrap = document.createElement('div');
      thumbWrap.className = 'bulk-preview-page-thumb';

      const img = document.createElement('img');
      img.loading = 'lazy';
      img.alt = `Trang ${pageIndex + 1} · ${item.file.name}`;
      const objectUrl = URL.createObjectURL(item.file);
      inspectorObjectUrls.push(objectUrl);
      img.src = objectUrl;

      img.addEventListener('click', () => {
        openLightbox(index, pageIndex);
      });

      img.addEventListener('error', () => {
        card.classList.add('is-error');
      });

      // Actions overlay
      const actions = document.createElement('div');
      actions.className = 'bulk-preview-page-actions';

      const viewBtn = document.createElement('button');
      viewBtn.type = 'button';
      viewBtn.className = 'bulk-card-action-btn bulk-btn-view';
      viewBtn.title = '🔍 Xem lớn';
      viewBtn.textContent = '🔍';
      viewBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openLightbox(index, pageIndex);
      });

      const replaceBtn = document.createElement('button');
      replaceBtn.type = 'button';
      replaceBtn.className = 'bulk-card-action-btn bulk-btn-replace';
      replaceBtn.title = '🔄 Thay ảnh';
      replaceBtn.textContent = '🔄';
      replaceBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        startReplaceInspectorPage(index, pageIndex);
      });

      const upBtn = document.createElement('button');
      upBtn.type = 'button';
      upBtn.className = 'bulk-card-action-btn bulk-btn-up';
      upBtn.title = '⬆ Di chuyển lên';
      upBtn.textContent = '⬆';
      upBtn.disabled = pageIndex === 0;
      upBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        moveInspectorPage(index, pageIndex, pageIndex - 1);
      });

      const downBtn = document.createElement('button');
      downBtn.type = 'button';
      downBtn.className = 'bulk-card-action-btn bulk-btn-down';
      downBtn.title = '⬇ Di chuyển xuống';
      downBtn.textContent = '⬇';
      downBtn.disabled = pageIndex === chapter.files.length - 1;
      downBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        moveInspectorPage(index, pageIndex, pageIndex + 1);
      });

      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'bulk-card-action-btn bulk-btn-delete';
      delBtn.title = '🗑 Xóa ảnh';
      delBtn.textContent = '🗑';
      delBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        deleteInspectorPage(index, pageIndex);
      });

      actions.append(viewBtn, replaceBtn, upBtn, downBtn, delBtn);
      thumbWrap.append(img, actions);

      // Meta info
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

      if (isBatchError) {
        const batchErrorLine = document.createElement('span');
        batchErrorLine.className = 'bulk-page-batch-error-badge';
        batchErrorLine.textContent = '⚠ Lỗi batch upload';
        info.appendChild(batchErrorLine);
      }

      card.append(thumbWrap, info);
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
      if (pageIndex < 0 || pageIndex >= chapter.files.length) return;

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

  // ==========================================
  // PART B: UPLOAD ERROR DIAGNOSTICS & RETRY
  // ==========================================

  function handleUploadError(error) {
    const errorId = 'UPL-' + Math.random().toString(36).slice(2, 8).toUpperCase();
    const chIndex = error.chapterIndex ?? activeChapterIndex;
    const chapter = chapters[chIndex];
    const chNumber = error.chapterNumber ?? (chapter?.chapterNumber ?? '?');
    const chKey = error.chapterKey ?? (chapter?.key ?? '');
    const bIndex = error.batchIndex ?? 0;
    const tBatches = error.totalBatches ?? 1;
    const pageIdxs = error.pageIndexes ?? [];
    const fNames = error.fileNames ?? [];
    const status = error.status ?? (error.name === 'TypeError' ? 'Network Error' : 500);

    failedBatchPages.clear();
    pageIdxs.forEach((p) => failedBatchPages.add(p));

    let friendlyMessage = '';
    const rawServerMessage = error.message || '';

    if (status === 413) {
      if (pageIdxs.length <= 1) {
        friendlyMessage = `File ${fNames[0] || 'này'} vẫn vượt giới hạn dung lượng request của server.`;
      } else {
        friendlyMessage = 'Batch quá lớn. Uploader đã thử chia nhỏ nhưng vẫn vượt giới hạn server.';
      }
    } else if (status === 422) {
      friendlyMessage = rawServerMessage || 'Dữ liệu tải lên không hợp lệ (Validation Error).';
    } else if (status === 401 || status === 403) {
      friendlyMessage = 'Phiên đăng nhập hoặc quyền Admin không còn hợp lệ.';
      activeSession = null;
    } else if (status === 419) {
      friendlyMessage = 'CSRF hoặc phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
      activeSession = null;
    } else if (status === 500) {
      friendlyMessage = rawServerMessage && rawServerMessage !== 'Upload lỗi HTTP 500.'
        ? rawServerMessage
        : 'Lỗi server khi xử lý upload.';
    } else if (status === 'Network Error' || status === 0) {
      friendlyMessage = 'Mất kết nối hoặc server không phản hồi.';
    } else {
      friendlyMessage = rawServerMessage || `Upload gặp lỗi HTTP ${status}.`;
    }

    lastUploadError = {
      errorId,
      chapterIndex: chIndex,
      chapterNumber: chNumber,
      chapterKey: chKey,
      batchIndex: bIndex,
      totalBatches: tBatches,
      pageIndexes: pageIdxs,
      fileNames: fNames,
      httpStatus: status,
      message: friendlyMessage,
      serverMessage: rawServerMessage,
      serverErrors: error.payload?.errors || null,
      uploadedBytes,
      totalBytes,
      timestamp: new Date().toISOString(),
    };

    console.error(`[Bulk Upload Error ${errorId}]`, lastUploadError);

    renderErrorPanel(lastUploadError);

    if (chIndex >= 0) {
      updateChapterStatus(chIndex, 'failed', `❌ Lỗi batch ${bIndex + 1}/${tBatches}`);
    }

    validationBox.className = 'bulk-validation bulk-validation-error';
    validationBox.textContent = `[${errorId}] Chapter ${chNumber}: ${friendlyMessage}`;
    uploadButton.textContent = `↻ Thử lại từ Chapter ${chNumber}`;

    if (inspectorChapterIndex === chIndex) {
      renderInspectorPages(chIndex, false);
    }
  }

  function renderErrorPanel(err) {
    const panel = document.getElementById('bulk-error-panel');
    if (!panel || !err) return;

    panel.style.display = 'block';

    const chEl = document.getElementById('bulk-error-chapter');
    const chIdxEl = document.getElementById('bulk-error-chapter-index');
    const batchEl = document.getElementById('bulk-error-batch');
    const httpEl = document.getElementById('bulk-error-http');
    const pagesEl = document.getElementById('bulk-error-pages');
    const progressEl = document.getElementById('bulk-error-progress');
    const filesEl = document.getElementById('bulk-error-files');
    const msgEl = document.getElementById('bulk-error-message-text');
    const rawEl = document.getElementById('bulk-error-raw');
    const retryBtn = document.getElementById('bulk-error-retry-btn');

    if (chEl) chEl.textContent = `Chapter ${err.chapterNumber}`;
    if (chIdxEl) chIdxEl.textContent = `${err.chapterIndex + 1} / ${chapters.length}`;
    if (batchEl) batchEl.textContent = `${err.batchIndex + 1} / ${err.totalBatches}`;
    if (httpEl) {
      httpEl.textContent = String(err.httpStatus);
      httpEl.style.color = err.httpStatus === 422 ? '#fbbf24' : '#f87171';
    }

    if (pagesEl) {
      if (err.pageIndexes.length > 0) {
        const minP = Math.min(...err.pageIndexes) + 1;
        const maxP = Math.max(...err.pageIndexes) + 1;
        pagesEl.textContent = minP === maxP ? `Trang ${minP}` : `Trang ${minP} - ${maxP}`;
      } else {
        pagesEl.textContent = 'Toàn chapter (Finalize)';
      }
    }

    if (progressEl) {
      progressEl.textContent = `${humanBytes(err.uploadedBytes)} / ${humanBytes(err.totalBytes)}`;
    }

    if (filesEl) {
      filesEl.innerHTML = '';
      if (err.fileNames.length > 0) {
        err.fileNames.slice(0, 8).forEach((name) => {
          const badge = document.createElement('span');
          badge.className = 'bulk-error-file-item';
          badge.textContent = name;
          filesEl.appendChild(badge);
        });
        if (err.fileNames.length > 8) {
          const more = document.createElement('span');
          more.className = 'bulk-error-file-item more';
          more.textContent = `+${err.fileNames.length - 8} file khác...`;
          filesEl.appendChild(more);
        }
      } else {
        filesEl.textContent = '(Không có danh sách file cụ thể)';
      }
    }

    if (msgEl) {
      msgEl.textContent = err.message;
    }

    if (retryBtn) {
      retryBtn.textContent = `↻ Thử lại Chapter ${err.chapterNumber}`;
    }

    if (rawEl) {
      const safeDetails = {
        errorId: err.errorId,
        chapterKey: err.chapterKey,
        chapterNumber: err.chapterNumber,
        batch: `${err.batchIndex + 1}/${err.totalBatches}`,
        pageIndexes: err.pageIndexes,
        fileCount: err.fileNames.length,
        httpStatus: err.httpStatus,
        serverMessage: err.serverMessage,
        serverErrors: err.serverErrors,
        timestamp: err.timestamp,
      };
      rawEl.textContent = JSON.stringify(safeDetails, null, 2);
    }
  }

  function copyErrorDetails() {
    if (!lastUploadError) return;
    const minP = lastUploadError.pageIndexes.length ? Math.min(...lastUploadError.pageIndexes) + 1 : '?';
    const maxP = lastUploadError.pageIndexes.length ? Math.max(...lastUploadError.pageIndexes) + 1 : '?';
    const pageStr = minP === maxP ? `Trang ${minP}` : `Trang ${minP} - ${maxP}`;

    const text = [
      `[LỖI BULK UPLOAD COMICX]`,
      `Mã lỗi: ${lastUploadError.errorId}`,
      `Thời gian: ${lastUploadError.timestamp}`,
      `Chapter: ${lastUploadError.chapterNumber} (Thứ tự: ${lastUploadError.chapterIndex + 1}/${chapters.length})`,
      `Batch: ${lastUploadError.batchIndex + 1}/${lastUploadError.totalBatches}`,
      `Các trang: ${pageStr}`,
      `Files: ${lastUploadError.fileNames.join(', ')}`,
      `HTTP Status: ${lastUploadError.httpStatus}`,
      `Lỗi: ${lastUploadError.message}`,
      `Server message: ${lastUploadError.serverMessage}`,
      `Tiến độ: ${humanBytes(lastUploadError.uploadedBytes)} / ${humanBytes(lastUploadError.totalBytes)}`,
    ].join('\n');

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(() => {
        const copyBtn = document.getElementById('bulk-error-copy-btn');
        if (copyBtn) {
          const oldText = copyBtn.textContent;
          copyBtn.textContent = '✓ Đã sao chép!';
          setTimeout(() => { copyBtn.textContent = oldText; }, 2000);
        }
      }).catch(() => {
        alert('Không thể sao chép tự động. Hãy mở "Chi tiết kỹ thuật" để sao chép.');
      });
    } else {
      prompt('Chi tiết lỗi:', text);
    }
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

    const errorPanel = document.getElementById('bulk-error-panel');
    if (errorPanel) errorPanel.style.display = 'none';

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

        // Uses current edited chapter.files (A10)
        const batches = createBatches(chapter.files);
        for (let batchIndex = 0; batchIndex < batches.length; batchIndex++) {
          await uploadBatchAdaptive(activeSession, chapter, batches[batchIndex], batchIndex, batches.length);
          const batchBytes = batches[batchIndex].reduce((sum, item) => sum + item.file.size, 0);
          uploadedBytes = Math.min(totalBytes, uploadedBytes + batchBytes);
          renderProgress(chapter, batchIndex + 1, batches.length);
        }

        try {
          const finalized = await postAction({
            bulk_action: 'finalize',
            session: activeSession,
            chapter_key: chapter.key,
            chapter_number: normalizeChapterNumber(chapter.chapterNumber) ?? String(chapter.chapterNumber),
            title: chapter.title || '',
            page_count: String(chapter.files.length),
          });

          updateChapterStatus(chapterIndex, 'done', `✓ Xong · ${finalized.pages} trang`);
        } catch (finError) {
          finError.chapterIndex = chapterIndex;
          finError.chapterNumber = chapter.chapterNumber;
          finError.chapterKey = chapter.key;
          finError.batchIndex = batches.length - 1;
          finError.totalBatches = batches.length;
          finError.pageIndexes = [];
          finError.fileNames = [];
          throw finError;
        }
      }

      const completed = await postAction({
        bulk_action: 'complete',
        session: activeSession,
      });

      activeSession = null;
      uploadedBytes = totalBytes;
      lastUploadError = null;
      failedBatchPages.clear();
      renderProgress(null, 1, 1);
      validationBox.className = 'bulk-validation bulk-validation-ok';
      validationBox.innerHTML = `Đã tạo thành công <strong>${completed.chapters_created}</strong> chapter. Ảnh đã qua kiểm tra trước upload, giữ nguyên byte gốc và xác minh SHA-256 trước + sau khi lưu. <a href="${chaptersUrl}">Mở danh sách chapter →</a>`;
      uploadButton.textContent = '✅ Upload hoàn tất';
    } catch (error) {
      handleUploadError(error);
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

  async function uploadBatchAdaptive(session, chapter, batch, batchIndex, totalBatches) {
    try {
      return await uploadBatch(session, chapter, batch);
    } catch (error) {
      if (error instanceof UploadHttpError && error.status === 413 && batch.length > 1) {
        const middle = Math.ceil(batch.length / 2);
        await uploadBatchAdaptive(session, chapter, batch.slice(0, middle), batchIndex, totalBatches);
        await uploadBatchAdaptive(session, chapter, batch.slice(middle), batchIndex, totalBatches);
        return;
      }
      // Attach structured context to error before re-throwing (B4)
      error.chapterIndex = activeChapterIndex;
      error.chapterNumber = chapter.chapterNumber;
      error.chapterKey = chapter.key;
      error.batchIndex = batchIndex;
      error.totalBatches = totalBatches;
      error.pageIndexes = batch.map((item) => item.pageIndex);
      error.fileNames = batch.map((item) => item.file.name);
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
