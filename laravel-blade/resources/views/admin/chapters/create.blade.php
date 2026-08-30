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
      <p class="admin-page-sub">Tải ảnh hàng loạt (Bulk Image Upload) hoặc dán link URL trang truyện.</p>
    </div>
  </div>
</div>

<form action="{{ route('admin.comics.chapters.store', $comic->id) }}" method="POST" enctype="multipart/form-data" id="chapter-form" novalidate>
  @csrf

  <div style="display:grid; grid-template-columns: 320px 1fr; gap:20px; align-items:start">

    {{-- ── CỘT TRÁI: THÔNG TIN CHAPTER ── --}}
    <div class="admin-card">
      <h2 style="font-size:15px; font-weight:700; color:var(--admin-text); margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--admin-border)">
        ⚙️ Thông tin Chapter
      </h2>

      {{-- Số Chapter --}}
      <div class="form-group">
        <label class="form-label" for="chapter_number">Số Chapter <span>*</span></label>
        <input
          type="number" step="0.1" id="chapter_number" name="chapter_number"
          class="form-control {{ $errors->has('chapter_number') ? 'is-invalid' : '' }}"
          value="{{ old('chapter_number', $nextChapterNumber) }}"
          required autofocus
        />
        <p class="form-hint">Ví dụ: 1, 2, 2.5, 100...</p>
        @error('chapter_number') <span class="invalid-feedback">{{ $message }}</span> @enderror
      </div>

      {{-- Tên Chapter --}}
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
        <button type="submit" class="btn-admin btn-admin-primary" style="width:100%; justify-content:center; padding:12px">
          🚀 Đăng Chapter Ngay
        </button>
        <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="btn-admin btn-admin-ghost" style="width:100%; justify-content:center; margin-top:8px">
          Hủy bỏ
        </a>
      </div>
    </div>

    {{-- ── CỘT PHẢI: BULK IMAGE UPLOAD DROPZONE ── --}}
    <div style="display:flex; flex-direction:column; gap:20px">

      {{-- Tab Switcher: Bulk Upload vs Raw URLs --}}
      <div class="admin-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px flex-wrap:wrap; gap:10px">
          <div style="display:flex; gap:8px" id="upload-tabs">
            <button type="button" class="btn-admin btn-admin-primary btn-sm tab-btn active" data-target="tab-dropzone">
              📁 Tải File Ảnh Hàng Loạt (Bulk Dropzone)
            </button>
            <button type="button" class="btn-admin btn-admin-ghost btn-sm tab-btn" data-target="tab-urls">
              🔗 Dán Link URL Ảnh (Raw URLs)
            </button>
          </div>
          <span style="font-size:12px; color:var(--admin-text-muted)" id="page-counter-badge">
            Đã chọn: <strong id="selected-count" style="color:var(--admin-primary)">0</strong> trang ảnh
          </span>
        </div>

        {{-- TAB 1: DROPZONE UPLOAD --}}
        <div id="tab-dropzone" class="tab-content-panel">
          <div id="dropzone" style="
            border: 2px dashed rgba(108,99,255,0.4);
            background: rgba(108,99,255,0.04);
            border-radius: 12px;
            padding: 36px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            position: relative;
          ">
            <input
              type="file" id="images-input" name="images[]" multiple
              accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
              style="position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; z-index:10"
            />
            <div style="font-size:42px; margin-bottom:10px">📂</div>
            <h3 style="font-size:16px; font-weight:700; color:var(--admin-text); margin-bottom:6px">
              Kéo &amp; thả danh sách ảnh vào đây hoặc <span style="color:var(--admin-primary); text-decoration:underline">Bấm để chọn file</span>
            </h3>
            <p style="font-size:13px; color:var(--admin-text-muted)">
              Hỗ trợ chọn cùng lúc nhiều ảnh (PNG, JPG, WEBP, GIF) · Tối đa 5MB / file
            </p>
          </div>
          @error('images') <span class="invalid-feedback" style="display:block; margin-top:8px">{{ $message }}</span> @enderror
          @error('images.*') <span class="invalid-feedback" style="display:block; margin-top:8px">{{ $message }}</span> @enderror

          {{-- Live Preview Grid --}}
          <div id="preview-section" style="margin-top:24px; display:none">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
              <span style="font-weight:700; font-size:14px">
                🖼️ Danh sách trang ảnh (<span id="preview-count">0</span> trang)
              </span>
              <div style="display:flex; gap:8px">
                <button type="button" class="btn-admin btn-admin-ghost btn-sm" id="btn-clear-all" style="color:var(--admin-danger)">
                  🗑️ Xóa tất cả
                </button>
              </div>
            </div>

            <p style="font-size:12px; color:var(--admin-text-muted); margin-bottom:12px">
              💡 Mẹo: Bạn có thể kéo thả để sắp xếp lại thứ tự trang truyện trước khi lưu.
            </p>

            <div id="preview-grid" style="
              display: grid;
              grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
              gap: 12px;
              max-height: 520px;
              overflow-y: auto;
              padding: 10px;
              background: rgba(0,0,0,0.2);
              border-radius: 10px;
              border: 1px solid var(--admin-border);
            ">
              <!-- Rendered via JS -->
            </div>
          </div>
        </div>

        {{-- TAB 2: RAW URLS --}}
        <div id="tab-urls" class="tab-content-panel" style="display:none">
          <div class="form-group" style="margin:0">
            <label class="form-label" for="pages_raw">Danh sách đường dẫn URL ảnh (Mỗi link 1 dòng)</label>
            <textarea
              id="pages_raw" name="pages_raw" class="form-control" rows="12"
              placeholder="https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg&#10;https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg"
              style="font-family:monospace; font-size:12.5px; line-height:1.6"
            >{{ old('pages_raw') }}</textarea>
            <p class="form-hint" style="margin-top:8px">
              Thích hợp khi ảnh đã được host trên CDN hoặc trang web khác.
            </p>
          </div>
        </div>

      </div>

    </div>

  </div>
</form>
@endsection

@push('scripts')
<script>
  let selectedFiles = []; // Mảng chứa các File object đã chọn

  const dropzone      = document.getElementById('dropzone');
  const imagesInput   = document.getElementById('images-input');
  const previewGrid   = document.getElementById('preview-grid');
  const previewSec    = document.getElementById('preview-section');
  const previewCount  = document.getElementById('preview-count');
  const selectedCount = document.getElementById('selected-count');
  const btnClearAll   = document.getElementById('btn-clear-all');

  // ── Tab switching ──
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
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

  // ── Drag & Drop highlight ──
  ['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      dropzone.style.borderColor = 'var(--admin-primary)';
      dropzone.style.background  = 'rgba(108,99,255,0.12)';
    });
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      dropzone.style.borderColor = 'rgba(108,99,255,0.4)';
      dropzone.style.background  = 'rgba(108,99,255,0.04)';
    });
  });

  // ── Handle file selection ──
  imagesInput.addEventListener('change', function() {
    if (this.files && this.files.length > 0) {
      addFiles(Array.from(this.files));
    }
  });

  dropzone.addEventListener('drop', (e) => {
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      addFiles(Array.from(e.dataTransfer.files));
    }
  });

  function addFiles(files) {
    const validFiles = files.filter(f => f.type.startsWith('image/'));
    selectedFiles = selectedFiles.concat(validFiles);
    updateFileInput();
    renderPreview();
  }

  function updateFileInput() {
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => dataTransfer.items.add(file));
    imagesInput.files = dataTransfer.files;

    selectedCount.textContent = selectedFiles.length;
    previewCount.textContent = selectedFiles.length;
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
      card.style.cssText = `
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        padding: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: grab;
      `;

      const img = document.createElement('img');
      img.style.cssText = 'width:100%; height:110px; object-fit:cover; border-radius:6px; background:#000';

      const reader = new FileReader();
      reader.onload = (e) => { img.src = e.target.result; };
      reader.readAsDataURL(file);

      const label = document.createElement('div');
      label.style.cssText = 'font-size:11px; font-weight:700; color:var(--admin-primary); margin-top:6px; text-align:center';
      label.textContent = `Trang ${index + 1}`;

      const name = document.createElement('div');
      name.style.cssText = 'font-size:10.5px; color:var(--admin-text-muted); margin-top:2px; text-align:center; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:100%';
      name.textContent = file.name;

      // Nút xóa
      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.textContent = '✕';
      delBtn.title = 'Xóa trang này';
      delBtn.style.cssText = `
        position: absolute; top: 4px; right: 4px;
        background: rgba(239, 68, 68, 0.85); color: #fff;
        border: none; border-radius: 50%; width: 20px; height: 20px;
        font-size: 11px; cursor: pointer; display: flex;
        align-items: center; justify-content: center;
      `;
      delBtn.onclick = (e) => {
        e.stopPropagation();
        removeFile(index);
      };

      card.appendChild(img);
      card.appendChild(label);
      card.appendChild(name);
      card.appendChild(delBtn);

      // Drag and drop reordering events
      card.addEventListener('dragstart', handleDragStart);
      card.addEventListener('dragover', handleDragOver);
      card.addEventListener('drop', handleDrop);
      card.addEventListener('dragend', handleDragEnd);

      previewGrid.appendChild(card);
    });
  }

  function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFileInput();
    renderPreview();
  }

  btnClearAll.addEventListener('click', () => {
    selectedFiles = [];
    updateFileInput();
    renderPreview();
  });

  // ── Drag & Drop reordering logic ──
  let dragSrcEl = null;

  function handleDragStart(e) {
    dragSrcEl = this;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.index);
    this.style.opacity = '0.4';
  }

  function handleDragOver(e) {
    if (e.preventDefault) e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    return false;
  }

  function handleDrop(e) {
    if (e.stopPropagation) e.stopPropagation();
    const fromIndex = parseInt(e.dataTransfer.getData('text/plain'));
    const toIndex = parseInt(this.dataset.index);

    if (fromIndex !== toIndex && !isNaN(fromIndex) && !isNaN(toIndex)) {
      const movedItem = selectedFiles.splice(fromIndex, 1)[0];
      selectedFiles.splice(toIndex, 0, movedItem);

      updateFileInput();
      renderPreview();
    }
    return false;
  }

  function handleDragEnd() {
    this.style.opacity = '1';
  }
</script>
@endpush
