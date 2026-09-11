{{-- resources/views/admin/chapters/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Đăng Chapter Mới — ' . $comic->title)
@section('breadcrumb', 'Truyện / ' . $comic->title . ' / Đăng Chapter')

@section('topbar-actions')
  <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="topbar-btn topbar-btn-ghost">← Danh sách Chapter</a>
@endsection

@section('content')
<div class="admin-page-header">
  <div style="display:flex; align-items:center; gap:12px">
    @if($comic->cover_image)
      <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" style="width:42px; height:56px; border-radius:6px; object-fit:cover; border:1px solid var(--admin-border)" />
    @endif
    <div>
      <h1 class="admin-page-title">➕ Đăng Chapter Mới: {{ $comic->title }}</h1>
      <p class="admin-page-sub">Upload 1 chapter bằng ZIP / ảnh rời / URL, hoặc chọn nguyên thư mục nhiều chapter dung lượng lớn.</p>
    </div>
  </div>
</div>

<form action="{{ route('admin.comics.chapters.store', $comic->id) }}" method="POST" enctype="multipart/form-data" id="chapter-form" novalidate>
  @csrf

  <div class="chapter-create-grid" style="display:grid; grid-template-columns:320px minmax(0,1fr); gap:20px; align-items:start">
    <div class="admin-card">
      <h2 style="font-size:15px; font-weight:700; color:var(--admin-text); margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--admin-border)">
        ⚙️ Thông tin Chapter
      </h2>

      <div class="form-group">
        <label class="form-label" for="chapter_number">Số Chapter <span>*</span></label>
        <input
          type="number" step="0.1" id="chapter_number" name="chapter_number"
          class="form-control {{ $errors->has('chapter_number') ? 'is-invalid' : '' }}"
          value="{{ old('chapter_number', $nextChapterNumber) }}"
          required autofocus
        />
        <p class="form-hint">Dùng cho chế độ upload 1 chapter. Folder nhiều chapter sẽ tự nhận diện số chương.</p>
        @error('chapter_number') <span class="invalid-feedback">{{ $message }}</span> @enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="title">Tên Chapter (Tùy chọn)</label>
        <input
          type="text" id="title" name="title"
          class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
          value="{{ old('title') }}"
          placeholder="Ví dụ: Trận chiến tại cổng Eden"
        />
        @error('title') <span class="invalid-feedback">{{ $message }}</span> @enderror
      </div>

      <div style="border-top:1px solid var(--admin-border); padding-top:16px; margin-top:20px">
        <div id="single-submit-actions">
          <button type="submit" class="btn-admin btn-admin-primary" style="width:100%; justify-content:center; padding:12px">
            🚀 Đăng Chapter Ngay
          </button>
        </div>
        <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="btn-admin btn-admin-ghost" style="width:100%; justify-content:center; margin-top:8px">
          Hủy bỏ
        </a>
      </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:20px; min-width:0">
      <div class="admin-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px">
          <div style="display:flex; gap:8px; flex-wrap:wrap" id="upload-tabs">
            <button type="button" class="btn-admin btn-admin-primary btn-sm tab-btn active" data-target="tab-zip">
              📦 Upload ZIP
            </button>
            <button type="button" class="btn-admin btn-admin-ghost btn-sm tab-btn" data-target="tab-dropzone">
              📁 Ảnh rời
            </button>
            <button type="button" class="btn-admin btn-admin-ghost btn-sm tab-btn" data-target="tab-urls">
              🔗 URL ảnh
            </button>
            <button type="button" class="btn-admin btn-admin-ghost btn-sm tab-btn" data-target="tab-bulk-folder">
              📚 Folder nhiều Chapter <span style="font-size:9px; font-weight:800; margin-left:3px">NEW</span>
            </button>
          </div>
          <span style="font-size:12px; color:var(--admin-text-muted)" id="page-counter-badge">
            Ảnh rời: <strong id="selected-count" style="color:var(--admin-primary)">0</strong> trang
          </span>
        </div>

        {{-- TAB 1: ZIP --}}
        <div id="tab-zip" class="tab-content-panel">
          <div style="border:2px dashed rgba(108,99,255,.45); background:rgba(108,99,255,.05); border-radius:12px; padding:32px 20px; text-align:center">
            <div style="font-size:44px; margin-bottom:10px">📦</div>
            <h3 style="font-size:17px; font-weight:800; margin-bottom:8px">Thả 1 file ZIP chứa toàn bộ trang truyện</h3>
            <p style="font-size:13px; color:var(--admin-text-muted); margin-bottom:16px; line-height:1.6">
              Hệ thống sẽ tự quét ảnh, sắp xếp theo tên file kiểu <strong>1, 2, 10</strong>, đổi tên thành <strong>001, 002, 003...</strong> và lưu đúng thứ tự đọc.
            </p>
            <input
              type="file"
              id="zip-file-input"
              name="zip_file"
              accept=".zip,application/zip"
              class="form-control {{ $errors->has('zip_file') ? 'is-invalid' : '' }}"
              style="max-width:560px; margin:0 auto"
            />
            @error('zip_file') <span class="invalid-feedback" style="display:block; margin-top:8px">{{ $message }}</span> @enderror
            <p id="zip-file-name" style="font-size:12px; color:var(--admin-text-muted); margin-top:10px">Tối đa 100MB · tối đa 1000 file · tối đa 500MB sau giải nén.</p>
          </div>

          <div style="margin-top:14px; padding:12px 14px; border-radius:10px; border:1px solid var(--admin-border); background:rgba(255,255,255,.03); font-size:12.5px; color:var(--admin-text-muted); line-height:1.65">
            <strong style="color:var(--admin-text)">Cách đặt file khuyên dùng:</strong>
            <code>1.jpg, 2.jpg, 3.jpg...</code> hoặc <code>page-001.jpg, page-002.jpg...</code>.
            Có thể để ảnh trong thư mục con; file rác hệ thống sẽ bị bỏ qua.
          </div>
        </div>

        {{-- TAB 2: BULK IMAGE UPLOAD --}}
        <div id="tab-dropzone" class="tab-content-panel" style="display:none">
          <div id="dropzone" style="
            border:2px dashed rgba(108,99,255,0.4);
            background:rgba(108,99,255,0.04);
            border-radius:12px;
            padding:36px 20px;
            text-align:center;
            cursor:pointer;
            transition:all .2s ease-in-out;
            position:relative;
          ">
            <input
              type="file" id="images-input" name="images[]" multiple
              accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
              style="position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; z-index:10"
            />
            <div style="font-size:42px; margin-bottom:10px">📂</div>
            <h3 style="font-size:16px; font-weight:700; color:var(--admin-text); margin-bottom:6px">
              Kéo &amp; thả ảnh vào đây hoặc <span style="color:var(--admin-primary); text-decoration:underline">bấm để chọn file</span>
            </h3>
            <p style="font-size:13px; color:var(--admin-text-muted)">
              Ảnh được <strong>tự sắp xếp theo tên file</strong> ngay sau khi chọn · tối đa 5MB / file
            </p>
          </div>
          @error('images') <span class="invalid-feedback" style="display:block; margin-top:8px">{{ $message }}</span> @enderror
          @error('images.*') <span class="invalid-feedback" style="display:block; margin-top:8px">{{ $message }}</span> @enderror

          <div id="preview-section" style="margin-top:24px; display:none">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:10px; flex-wrap:wrap">
              <span style="font-weight:700; font-size:14px">
                🖼️ Danh sách trang ảnh (<span id="preview-count">0</span> trang)
              </span>
              <div style="display:flex; gap:8px; flex-wrap:wrap">
                <button type="button" class="btn-admin btn-admin-ghost btn-sm" id="btn-auto-sort">
                  🔢 Sắp xếp theo tên
                </button>
                <button type="button" class="btn-admin btn-admin-ghost btn-sm" id="btn-clear-all" style="color:var(--admin-danger)">
                  🗑️ Xóa tất cả
                </button>
              </div>
            </div>

            <p style="font-size:12px; color:var(--admin-text-muted); margin-bottom:12px">
              Hệ thống dùng natural sort để <strong>1.jpg, 2.jpg, 10.jpg</strong> nằm đúng thứ tự. Sau đó vẫn có thể kéo thả để chỉnh tay.
            </p>

            <div id="preview-grid" style="
              display:grid;
              grid-template-columns:repeat(auto-fill,minmax(130px,1fr));
              gap:12px;
              max-height:520px;
              overflow-y:auto;
              padding:10px;
              background:rgba(0,0,0,.2);
              border-radius:10px;
              border:1px solid var(--admin-border);
            "></div>
          </div>
        </div>

        {{-- TAB 3: RAW URLS --}}
        <div id="tab-urls" class="tab-content-panel" style="display:none">
          <div class="form-group" style="margin:0">
            <label class="form-label" for="pages_raw">Danh sách URL ảnh, mỗi link một dòng</label>
            <textarea
              id="pages_raw" name="pages_raw" class="form-control" rows="12"
              placeholder="https://cdn.example.com/chapter-1/001.jpg&#10;https://cdn.example.com/chapter-1/002.jpg"
              style="font-family:monospace; font-size:12.5px; line-height:1.6"
            >{{ old('pages_raw') }}</textarea>
            <p class="form-hint" style="margin-top:8px">Thứ tự dòng URL chính là thứ tự trang truyện.</p>
          </div>
        </div>

        {{-- TAB 4: MULTI CHAPTER DIRECTORY --}}
        <div
          id="tab-bulk-folder"
          class="tab-content-panel"
          style="display:none"
          data-endpoint="{{ route('admin.comics.chapters.store', $comic->id) }}"
          data-csrf="{{ csrf_token() }}"
          data-chapters-url="{{ route('admin.comics.chapters.index', $comic->id) }}"
        >
          <div class="bulk-folder-hero">
            <div class="bulk-folder-icon">📚</div>
            <div>
              <h3>Upload nguyên thư mục chứa nhiều Chapter</h3>
              <p>
                Dành cho folder lớn vài GB như <code>Vol.16 Ch.0140 - ...</code>, <code>Vol.17 Ch.0141 - ...</code>.
                Trình duyệt chỉ đọc metadata trước, <strong>không dựng preview hàng nghìn ảnh vào RAM</strong>.
              </p>
            </div>
          </div>

          <label class="bulk-folder-picker" for="bulk-folder-input">
            <span class="bulk-folder-picker-icon">🗂️</span>
            <span>
              <strong>Chọn thư mục gốc</strong>
              <small>Ví dụ thư mục 2.8GB đang chứa hàng chục folder chapter</small>
            </span>
            <input
              type="file"
              id="bulk-folder-input"
              multiple
              webkitdirectory
              directory
              accept="image/jpeg,image/png,image/webp,image/gif,image/avif"
            />
          </label>

          <div class="bulk-safety-note">
            <strong>🛡️ Chế độ an toàn cho file lớn:</strong>
            upload theo batch nhỏ thay vì gửi 2.8GB trong một request; mỗi ảnh được kiểm tra Magic Bytes + kích thước + SHA-256, lưu nguyên byte gốc, không nén lại và kiểm tra checksum lần nữa sau khi ghi vào storage.
          </div>

          <div class="bulk-summary-grid">
            <div><span>Chapter</span><strong id="bulk-summary-chapters">0</strong></div>
            <div><span>Tổng trang</span><strong id="bulk-summary-pages">0</strong></div>
            <div><span>Dung lượng</span><strong id="bulk-summary-size">0 B</strong></div>
            <div><span>Cần sửa</span><strong id="bulk-summary-issues">0</strong></div>
          </div>

          <div id="bulk-validation-message" class="bulk-validation bulk-validation-neutral">
            Chọn thư mục gốc chứa nhiều folder chapter để hệ thống phân tích trước khi upload.
          </div>

          <div id="bulk-chapter-table-wrap" class="bulk-table-wrap" style="display:none">
            <table class="bulk-chapter-table">
              <thead>
                <tr>
                  <th>Folder nhận diện</th>
                  <th style="width:120px">Chapter</th>
                  <th>Tên chapter</th>
                  <th style="width:100px">Trang</th>
                  <th style="width:155px">Trạng thái</th>
                </tr>
              </thead>
              <tbody id="bulk-chapter-table-body"></tbody>
            </table>
          </div>

          <div id="bulk-progress-wrap" class="bulk-progress-wrap" style="display:none">
            <div class="bulk-progress-track">
              <div id="bulk-progress-bar" class="bulk-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
            </div>
            <div id="bulk-progress-text" class="bulk-progress-text">0%</div>
          </div>

          <div class="bulk-actions">
            <button type="button" id="bulk-start-upload" class="btn-admin btn-admin-primary" disabled>
              🚀 Upload toàn bộ Chapter
            </button>
            <span>Không đóng tab trong lúc upload. Nếu mạng chập chờn, batch lỗi có thể thử lại ngay trên trang này.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('styles')
<style>
  .bulk-folder-hero { display:flex; gap:14px; align-items:flex-start; padding:16px; border:1px solid rgba(108,99,255,.28); border-radius:12px; background:rgba(108,99,255,.06); }
  .bulk-folder-icon { font-size:38px; line-height:1; }
  .bulk-folder-hero h3 { font-size:17px; margin:0 0 6px; }
  .bulk-folder-hero p { margin:0; font-size:13px; line-height:1.65; color:var(--admin-text-muted); }
  .bulk-folder-picker { margin-top:14px; display:flex; align-items:center; gap:12px; padding:18px; border:2px dashed rgba(108,99,255,.45); border-radius:12px; cursor:pointer; background:rgba(108,99,255,.035); position:relative; }
  .bulk-folder-picker:hover { border-color:var(--admin-primary); background:rgba(108,99,255,.08); }
  .bulk-folder-picker input { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
  .bulk-folder-picker-icon { font-size:30px; }
  .bulk-folder-picker strong, .bulk-folder-picker small { display:block; }
  .bulk-folder-picker small { color:var(--admin-text-muted); margin-top:3px; }
  .bulk-safety-note { margin-top:12px; padding:12px 14px; border-radius:10px; background:rgba(16,185,129,.07); border:1px solid rgba(16,185,129,.22); font-size:12.5px; line-height:1.65; color:var(--admin-text-muted); }
  .bulk-safety-note strong { color:var(--admin-text); }
  .bulk-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:14px; }
  .bulk-summary-grid > div { padding:12px; border:1px solid var(--admin-border); border-radius:10px; background:rgba(255,255,255,.03); }
  .bulk-summary-grid span { display:block; font-size:11px; color:var(--admin-text-muted); margin-bottom:4px; }
  .bulk-summary-grid strong { font-size:17px; }
  .bulk-validation { margin-top:12px; padding:10px 12px; border-radius:9px; font-size:12.5px; line-height:1.5; }
  .bulk-validation-neutral { background:rgba(255,255,255,.04); border:1px solid var(--admin-border); color:var(--admin-text-muted); }
  .bulk-validation-ok { background:rgba(16,185,129,.09); border:1px solid rgba(16,185,129,.28); color:#b7f7dc; }
  .bulk-validation-error { background:rgba(239,68,68,.09); border:1px solid rgba(239,68,68,.28); color:#fecaca; }
  .bulk-validation a { color:inherit; font-weight:700; text-decoration:underline; }
  .bulk-table-wrap { margin-top:14px; border:1px solid var(--admin-border); border-radius:10px; overflow:auto; max-height:480px; }
  .bulk-chapter-table { width:100%; min-width:820px; border-collapse:collapse; }
  .bulk-chapter-table th, .bulk-chapter-table td { padding:10px; text-align:left; border-bottom:1px solid var(--admin-border); vertical-align:middle; }
  .bulk-chapter-table th { position:sticky; top:0; z-index:2; background:var(--admin-surface, #171722); font-size:11px; color:var(--admin-text-muted); text-transform:uppercase; letter-spacing:.04em; }
  .bulk-folder-name { max-width:310px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12.5px; font-weight:700; }
  .bulk-folder-meta { font-size:10.5px; color:var(--admin-text-muted); margin-top:3px; }
  .bulk-chapter-number, .bulk-chapter-title { min-width:0; }
  .bulk-status { display:inline-flex; align-items:center; padding:5px 8px; border-radius:999px; font-size:10.5px; font-weight:700; white-space:nowrap; }
  .bulk-status-pending { background:rgba(148,163,184,.12); color:#cbd5e1; }
  .bulk-status-uploading { background:rgba(59,130,246,.14); color:#bfdbfe; }
  .bulk-status-done { background:rgba(16,185,129,.14); color:#a7f3d0; }
  .bulk-status-failed { background:rgba(239,68,68,.14); color:#fecaca; }
  .bulk-progress-wrap { margin-top:16px; }
  .bulk-progress-track { height:10px; border-radius:999px; background:rgba(255,255,255,.08); overflow:hidden; }
  .bulk-progress-bar { width:0; height:100%; border-radius:inherit; background:linear-gradient(90deg,#6c63ff,#22c55e); transition:width .25s ease; }
  .bulk-progress-text { font-size:11.5px; color:var(--admin-text-muted); margin-top:6px; }
  .bulk-actions { display:flex; align-items:center; gap:12px; margin-top:16px; flex-wrap:wrap; }
  .bulk-actions span { font-size:11.5px; color:var(--admin-text-muted); }

  @media (max-width: 900px) {
    .chapter-create-grid { grid-template-columns:1fr !important; }
    .bulk-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
</style>
@endpush

@push('scripts')
<script>
  let selectedFiles = [];
  let dragSrcEl = null;

  const naturalCollator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
  const dropzone = document.getElementById('dropzone');
  const imagesInput = document.getElementById('images-input');
  const zipInput = document.getElementById('zip-file-input');
  const zipFileName = document.getElementById('zip-file-name');
  const previewGrid = document.getElementById('preview-grid');
  const previewSec = document.getElementById('preview-section');
  const previewCount = document.getElementById('preview-count');
  const selectedCount = document.getElementById('selected-count');
  const btnClearAll = document.getElementById('btn-clear-all');
  const btnAutoSort = document.getElementById('btn-auto-sort');

  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active', 'btn-admin-primary');
        b.classList.add('btn-admin-ghost');
      });
      this.classList.add('active', 'btn-admin-primary');
      this.classList.remove('btn-admin-ghost');

      const targetId = this.getAttribute('data-target');
      document.querySelectorAll('.tab-content-panel').forEach(panel => {
        panel.style.display = panel.id === targetId ? 'block' : 'none';
      });
    });
  });

  zipInput.addEventListener('change', function () {
    const file = this.files?.[0];
    zipFileName.textContent = file
      ? `Đã chọn: ${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`
      : 'Tối đa 100MB · tối đa 1000 file · tối đa 500MB sau giải nén.';
  });

  ['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, e => {
      e.preventDefault();
      dropzone.style.borderColor = 'var(--admin-primary)';
      dropzone.style.background = 'rgba(108,99,255,.12)';
    });
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, e => {
      e.preventDefault();
      dropzone.style.borderColor = 'rgba(108,99,255,.4)';
      dropzone.style.background = 'rgba(108,99,255,.04)';
    });
  });

  imagesInput.addEventListener('change', function () {
    if (this.files?.length) addFiles(Array.from(this.files));
  });

  dropzone.addEventListener('drop', e => {
    if (e.dataTransfer.files?.length) addFiles(Array.from(e.dataTransfer.files));
  });

  function addFiles(files) {
    const validFiles = files.filter(file => file.type.startsWith('image/'));
    const known = new Set(selectedFiles.map(file => `${file.name}:${file.size}:${file.lastModified}`));

    validFiles.forEach(file => {
      const key = `${file.name}:${file.size}:${file.lastModified}`;
      if (!known.has(key)) {
        selectedFiles.push(file);
        known.add(key);
      }
    });

    sortSelectedFiles();
  }

  function sortSelectedFiles() {
    selectedFiles.sort((a, b) => naturalCollator.compare(a.name, b.name));
    syncAndRender();
  }

  function syncAndRender() {
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => dataTransfer.items.add(file));
    imagesInput.files = dataTransfer.files;

    selectedCount.textContent = selectedFiles.length;
    previewCount.textContent = selectedFiles.length;
    renderPreview();
  }

  function renderPreview() {
    previewGrid.innerHTML = '';

    if (selectedFiles.length === 0) {
      previewSec.style.display = 'none';
      return;
    }

    previewSec.style.display = 'block';

    selectedFiles.forEach((file, index) => {
      const card = document.createElement('div');
      card.className = 'preview-card';
      card.setAttribute('draggable', 'true');
      card.dataset.index = index;
      card.style.cssText = 'background:rgba(255,255,255,.05);border:1px solid var(--admin-border);border-radius:8px;padding:8px;position:relative;display:flex;flex-direction:column;align-items:center;cursor:grab;min-width:0';

      const img = document.createElement('img');
      img.style.cssText = 'width:100%;height:110px;object-fit:cover;border-radius:6px;background:#000';
      const reader = new FileReader();
      reader.onload = e => { img.src = e.target.result; };
      reader.readAsDataURL(file);

      const label = document.createElement('div');
      label.dataset.testid = 'preview-page-label';
      label.style.cssText = 'font-size:11px;font-weight:700;color:var(--admin-primary);margin-top:6px;text-align:center';
      label.textContent = `Trang ${index + 1}`;

      const name = document.createElement('div');
      name.dataset.testid = 'preview-file-name';
      name.style.cssText = 'font-size:10.5px;color:var(--admin-text-muted);margin-top:2px;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%';
      name.textContent = file.name;

      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.textContent = '✕';
      delBtn.title = 'Xóa trang này';
      delBtn.style.cssText = 'position:absolute;top:4px;right:4px;background:rgba(239,68,68,.85);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center';
      delBtn.onclick = e => {
        e.stopPropagation();
        selectedFiles.splice(index, 1);
        syncAndRender();
      };

      card.append(img, label, name, delBtn);
      card.addEventListener('dragstart', handleDragStart);
      card.addEventListener('dragover', handleDragOver);
      card.addEventListener('drop', handleDrop);
      card.addEventListener('dragend', handleDragEnd);
      previewGrid.appendChild(card);
    });
  }

  btnAutoSort.addEventListener('click', sortSelectedFiles);
  btnClearAll.addEventListener('click', () => {
    selectedFiles = [];
    syncAndRender();
  });

  function handleDragStart(e) {
    dragSrcEl = this;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.index);
    this.style.opacity = '.4';
  }

  function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  }

  function handleDrop(e) {
    e.stopPropagation();
    const fromIndex = Number.parseInt(e.dataTransfer.getData('text/plain'), 10);
    const toIndex = Number.parseInt(this.dataset.index, 10);

    if (Number.isInteger(fromIndex) && Number.isInteger(toIndex) && fromIndex !== toIndex) {
      const [movedItem] = selectedFiles.splice(fromIndex, 1);
      selectedFiles.splice(toIndex, 0, movedItem);
      syncAndRender();
    }
  }

  function handleDragEnd() {
    this.style.opacity = '1';
    dragSrcEl = null;
  }
</script>
<script src="{{ asset('js/admin-bulk-chapter-upload.js') }}"></script>
@endpush
