{{-- resources/views/admin/chapters/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Sửa Chapter ' . $chapter->chapter_number . ' — ' . $comic->title)
@section('breadcrumb', 'Truyện / ' . $comic->title . ' / Chapter ' . $chapter->chapter_number . ' / Chỉnh sửa')

@section('topbar-actions')
  <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="topbar-btn topbar-btn-ghost">← Quay lại</a>
@endsection

@section('content')
<div class="admin-page-header">
  <div style="display:flex; align-items:center; gap:12px">
    @if($comic->cover_image)
      <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" style="width:42px; height:56px; border-radius:6px; object-fit:cover; border:1px solid var(--admin-border)" />
    @endif
    <div>
      <h1 class="admin-page-title">✏️ Chỉnh sửa Chapter {{ $chapter->chapter_number }}: {{ $comic->title }}</h1>
      <p class="admin-page-sub">Thay đổi thông tin, sắp xếp thứ tự trang, xóa trang cũ hoặc bổ sung ảnh mới.</p>
    </div>
  </div>
</div>

<form action="{{ route('admin.comics.chapters.update', [$comic->id, $chapter->id]) }}" method="POST" enctype="multipart/form-data" novalidate>
  @csrf
  @method('PUT')

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
          value="{{ old('chapter_number', $chapter->chapter_number) }}"
          required autofocus
        />
        @error('chapter_number') <span class="invalid-feedback">{{ $message }}</span> @enderror
      </div>

      {{-- Tên Chapter --}}
      <div class="form-group">
        <label class="form-label" for="title">Tên Chapter</label>
        <input
          type="text" id="title" name="title"
          class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
          value="{{ old('title', $chapter->title) }}"
        />
        @error('title') <span class="invalid-feedback">{{ $message }}</span> @enderror
      </div>

      {{-- Miễn phí / Trả phí --}}
      <div class="form-group">
        <label class="form-label">Quyền truy cập</label>
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13.5px; margin-top:6px">
          <input type="checkbox" name="is_free" value="1" {{ old('is_free', $chapter->is_free) ? 'checked' : '' }} style="width:18px; height:18px; accent-color:var(--admin-primary)">
          <span>✅ Miễn phí đọc (Free Chapter)</span>
        </label>
      </div>

      <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:12px; font-size:12.5px; color:var(--admin-text-muted); margin-bottom:20px">
        📊 Lượt xem: <strong style="color:var(--admin-text)">{{ number_format($chapter->views) }}</strong><br>
        📅 Đăng lúc: {{ $chapter->published_at?->format('d/m/Y H:i') ?? 'N/A' }}
      </div>

      <div style="border-top:1px solid var(--admin-border); padding-top:16px">
        <button type="submit" class="btn-admin btn-admin-primary" style="width:100%; justify-content:center; padding:12px">
          💾 Lưu thay đổi
        </button>
        <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="btn-admin btn-admin-ghost" style="width:100%; justify-content:center; margin-top:8px">
          Hủy bỏ
        </a>
      </div>
    </div>

    {{-- ── CỘT PHẢI: QÚAN LÝ TRANG ẢNH ── --}}
    <div style="display:flex; flex-direction:column; gap:20px">

      {{-- Quản lý trang ảnh hiện tại --}}
      <div class="admin-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
          <h2 style="font-size:15px; font-weight:700">🖼️ Các trang ảnh hiện tại (<span id="page-count-badge">{{ count($chapter->pages ?? []) }}</span> trang)</h2>
          <span style="font-size:12px; color:var(--admin-text-muted)">Kéo thả để sắp xếp lại thứ tự</span>
        </div>

        {{-- Container các trang ảnh đang có --}}
        <div id="existing-grid" style="
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
          gap: 12px;
          max-height: 480px;
          overflow-y: auto;
          padding: 10px;
          background: rgba(0,0,0,0.2);
          border-radius: 10px;
          border: 1px solid var(--admin-border);
        ">
          @forelse($chapter->pages ?? [] as $index => $pagePath)
            @php
              $imgUrl = str_starts_with($pagePath, 'http') ? $pagePath : asset('storage/' . $pagePath);
            @endphp
            <div class="page-card" draggable="true" data-path="{{ $pagePath }}" style="
              background: rgba(255,255,255,0.05);
              border: 1px solid var(--admin-border);
              border-radius: 8px;
              padding: 8px;
              position: relative;
              display: flex;
              flex-direction: column;
              align-items: center;
              cursor: grab;
            ">
              <img src="{{ $imgUrl }}" alt="Trang {{ $index + 1 }}" style="width:100%; height:110px; object-fit:cover; border-radius:6px; background:#000" />
              <div class="page-num-label" style="font-size:11px; font-weight:700; color:var(--admin-primary); margin-top:6px">Trang {{ $index + 1 }}</div>

              <input type="hidden" name="existing_pages[]" value="{{ $pagePath }}" class="existing-page-input" />

              <button type="button" class="btn-delete-page" onclick="removeExistingPage(this, '{{ $pagePath }}')" title="Xóa trang này" style="
                position: absolute; top: 4px; right: 4px;
                background: rgba(239, 68, 68, 0.85); color: #fff;
                border: none; border-radius: 50%; width: 20px; height: 20px;
                font-size: 11px; cursor: pointer; display: flex;
                align-items: center; justify-content: center;
              ">✕</button>
            </div>
          @empty
            <p style="grid-column: 1 / -1; text-align:center; padding:30px; color:var(--admin-text-muted)">Chưa có trang ảnh nào trong chapter này.</p>
          @endforelse
        </div>

        {{-- Input ẩn để chứa danh sách các trang bị xóa khỏi Storage --}}
        <div id="removed-pages-container"></div>
      </div>

      {{-- Bổ sung thêm ảnh mới --}}
      <div class="admin-card">
        <h2 style="font-size:15px; font-weight:700; margin-bottom:12px">➕ Bổ sung thêm ảnh mới</h2>

        <div class="form-group">
          <label class="form-label" for="new_images">Tải thêm file ảnh (Tùy chọn)</label>
          <input type="file" id="new_images" name="new_images[]" multiple accept="image/*" class="form-control" />
          <p class="form-hint" style="margin-top:4px">Các ảnh mới sẽ được tự động nối vào cuối danh sách trang hiện tại.</p>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="add_urls">Hoặc dán thêm URL ảnh (Mỗi link 1 dòng)</label>
          <textarea id="add_urls" name="add_urls" class="form-control" rows="4" placeholder="https://example.com/page-extra.jpg" style="font-family:monospace; font-size:12.5px"></textarea>
        </div>
      </div>

    </div>

  </div>
</form>
@endsection

@push('scripts')
<script>
  const existingGrid = document.getElementById('existing-grid');
  const removedContainer = document.getElementById('removed-pages-container');

  function removeExistingPage(btn, pagePath) {
    if (!confirm('Bạn có chắc muốn xóa trang ảnh này?')) return;

    const card = btn.closest('.page-card');
    card.remove();

    // Tạo hidden input báo cho backend xóa file này
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'removed_pages[]';
    input.value = pagePath;
    removedContainer.appendChild(input);

    updatePageNumbers();
  }

  function updatePageNumbers() {
    const cards = existingGrid.querySelectorAll('.page-card');
    cards.forEach((card, index) => {
      const label = card.querySelector('.page-num-label');
      if (label) label.textContent = `Trang ${index + 1}`;
    });

    const badge = document.getElementById('page-count-badge');
    if (badge) badge.textContent = cards.length;
  }

  // ── Reordering drag and drop for existing cards ──
  let dragSrc = null;

  existingGrid.addEventListener('dragstart', (e) => {
    const card = e.target.closest('.page-card');
    if (!card) return;
    dragSrc = card;
    e.dataTransfer.effectAllowed = 'move';
    card.style.opacity = '0.4';
  });

  existingGrid.addEventListener('dragover', (e) => {
    e.preventDefault();
    const targetCard = e.target.closest('.page-card');
    if (targetCard && targetCard !== dragSrc) {
      const rect = targetCard.getBoundingClientRect();
      const next = (e.clientX - rect.left) / (rect.right - rect.left) > 0.5;
      existingGrid.insertBefore(dragSrc, next ? targetCard.nextSibling : targetCard);
    }
  });

  existingGrid.addEventListener('dragend', (e) => {
    const card = e.target.closest('.page-card');
    if (card) card.style.opacity = '1';
    updatePageNumbers();
  });
</script>
@endpush
