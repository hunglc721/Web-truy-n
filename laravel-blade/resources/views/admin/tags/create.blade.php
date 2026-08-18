{{-- resources/views/admin/tags/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Thêm Tag mới')
@section('breadcrumb', 'Tags / Thêm mới')

@section('topbar-actions')
  <a href="{{ route('admin.tags.index') }}" class="topbar-btn topbar-btn-ghost">← Quay lại</a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">➕ Thêm Tag mới</h1>
</div>

<div class="admin-card" style="max-width:520px">
  <form method="POST" action="{{ route('admin.tags.store') }}" novalidate>
    @csrf

    <div class="form-group">
      <label class="form-label" for="name">Tên Tag <span>*</span></label>
      <input
        type="text" id="name" name="name"
        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
        value="{{ old('name') }}"
        placeholder="Ví dụ: HOT, Isekai, Shounen..."
        required autofocus
      />
      @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="slug">Slug</label>
      <input
        type="text" id="slug" name="slug"
        class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
        value="{{ old('slug') }}"
        placeholder="hot (để trống sẽ tự tạo)"
      />
      @error('slug') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="color">Màu sắc (Hex)</label>
      <div style="display:flex; align-items:center; gap:12px">
        <input
          type="color" id="color-picker"
          value="{{ old('color', '#6c63ff') }}"
          style="width:48px; height:40px; border-radius:8px; border:1px solid var(--admin-border); background:none; cursor:pointer; padding:2px"
        />
        <input
          type="text" id="color" name="color"
          class="form-control {{ $errors->has('color') ? 'is-invalid' : '' }}"
          value="{{ old('color', '#6c63ff') }}"
          placeholder="#FF5733"
          style="flex:1"
          maxlength="7"
        />
        {{-- Live preview badge --}}
        <span id="tag-preview" style="
          display:inline-block; padding:5px 14px; border-radius:20px;
          font-size:13px; font-weight:700; white-space:nowrap;
          background: {{ old('color', '#6c63ff') }}25;
          color: {{ old('color', '#6c63ff') }};
          border: 1px solid {{ old('color', '#6c63ff') }}50;
        " id="tag-preview">Preview</span>
      </div>
      <p class="form-hint">Chọn màu bằng color picker hoặc nhập mã hex thủ công.</p>
      @error('color') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div style="display:flex; gap:10px; margin-top:24px">
      <button type="submit" class="btn-admin btn-admin-primary">💾 Lưu Tag</button>
      <a href="{{ route('admin.tags.index') }}" class="btn-admin btn-admin-ghost">Hủy</a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  const picker  = document.getElementById('color-picker');
  const hexInput= document.getElementById('color');
  const preview = document.getElementById('tag-preview');
  const nameInput = document.getElementById('name');

  function applyColor(hex) {
    preview.style.background = hex + '25';
    preview.style.color = hex;
    preview.style.borderColor = hex + '50';
  }

  picker.addEventListener('input', () => { hexInput.value = picker.value; applyColor(picker.value); });
  hexInput.addEventListener('input', () => {
    const v = hexInput.value;
    if (/^#[0-9A-Fa-f]{6}$/.test(v)) { picker.value = v; applyColor(v); }
  });
  nameInput.addEventListener('input', () => {
    preview.textContent = nameInput.value || 'Preview';
  });
</script>
@endpush
