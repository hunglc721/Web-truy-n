{{-- resources/views/admin/genres/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Thêm thể loại mới')
@section('breadcrumb', 'Thể loại / Thêm mới')

@section('topbar-actions')
  <a href="{{ route('admin.genres.index') }}" class="topbar-btn topbar-btn-ghost">
    ← Quay lại
  </a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">➕ Thêm thể loại mới</h1>
  <p class="admin-page-sub">Điền thông tin để tạo thể loại mới cho hệ thống.</p>
</div>

<div class="admin-card" style="max-width:600px">
  <form method="POST" action="{{ route('admin.genres.store') }}" novalidate>
    @csrf

    {{-- Tên --}}
    <div class="form-group">
      <label class="form-label" for="name">Tên thể loại <span>*</span></label>
      <input
        type="text" id="name" name="name"
        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
        value="{{ old('name') }}"
        placeholder="Ví dụ: Hành động, Lãng mạn, Kinh dị..."
        required
        autofocus
      />
      @error('name')
        <span class="invalid-feedback">{{ $message }}</span>
      @enderror
    </div>

    {{-- Slug --}}
    <div class="form-group">
      <label class="form-label" for="slug">Slug (URL)</label>
      <input
        type="text" id="slug" name="slug"
        class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
        value="{{ old('slug') }}"
        placeholder="hanh-dong (để trống sẽ tự tạo)"
      />
      <p class="form-hint">Chỉ dùng chữ thường, số và dấu gạch ngang. Để trống sẽ tự tạo từ Tên.</p>
      @error('slug')
        <span class="invalid-feedback">{{ $message }}</span>
      @enderror
    </div>

    {{-- Icon --}}
    <div class="form-group">
      <label class="form-label" for="icon">Icon (Emoji)</label>
      <div style="display:flex; gap:10px; align-items:center">
        <input
          type="text" id="icon" name="icon"
          class="form-control {{ $errors->has('icon') ? 'is-invalid' : '' }}"
          value="{{ old('icon') }}"
          placeholder="⚔️"
          maxlength="10"
          style="max-width:140px"
        />
        <span id="icon-preview" style="font-size:32px">{{ old('icon', '📁') }}</span>
      </div>
      <p class="form-hint">Dán emoji hoặc ký tự icon vào đây.</p>
      @error('icon')
        <span class="invalid-feedback">{{ $message }}</span>
      @enderror
    </div>

    {{-- Mô tả --}}
    <div class="form-group">
      <label class="form-label" for="description">Mô tả</label>
      <textarea
        id="description" name="description"
        class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
        placeholder="Mô tả ngắn về thể loại này..."
        rows="3"
      >{{ old('description') }}</textarea>
      @error('description')
        <span class="invalid-feedback">{{ $message }}</span>
      @enderror
    </div>

    {{-- Actions --}}
    <div style="display:flex; gap:10px; margin-top:24px;">
      <button type="submit" class="btn-admin btn-admin-primary">
        💾 Lưu thể loại
      </button>
      <a href="{{ route('admin.genres.index') }}" class="btn-admin btn-admin-ghost">
        Hủy bỏ
      </a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  // Live slug preview
  const nameInput = document.getElementById('name');
  const slugInput = document.getElementById('slug');
  nameInput.addEventListener('input', function() {
    if (!slugInput.value) {
      // Basic slug preview
      slugInput.placeholder = this.value
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim().replace(/\s+/g, '-');
    }
  });

  // Icon preview
  const iconInput = document.getElementById('icon');
  const iconPreview = document.getElementById('icon-preview');
  iconInput.addEventListener('input', () => {
    iconPreview.textContent = iconInput.value || '📁';
  });
</script>
@endpush
