{{-- resources/views/admin/tags/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Sửa Tag: ' . $tag->name)
@section('breadcrumb', 'Tags / Chỉnh sửa')

@section('topbar-actions')
  <a href="{{ route('admin.tags.index') }}" class="topbar-btn topbar-btn-ghost">← Quay lại</a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">✏️ Sửa Tag: {{ $tag->name }}</h1>
</div>

<div class="admin-card" style="max-width:520px">
  <form method="POST" action="{{ route('admin.tags.update', $tag) }}" novalidate>
    @csrf
    @method('PUT')

    <div class="form-group">
      <label class="form-label" for="name">Tên Tag <span>*</span></label>
      <input
        type="text" id="name" name="name"
        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
        value="{{ old('name', $tag->name) }}"
        required autofocus
      />
      @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="slug">Slug</label>
      <input
        type="text" id="slug" name="slug"
        class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
        value="{{ old('slug', $tag->slug) }}"
      />
      @error('slug') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="color">Màu sắc</label>
      <div style="display:flex; align-items:center; gap:12px">
        <input
          type="color" id="color-picker"
          value="{{ old('color', $tag->color ?: '#6c63ff') }}"
          style="width:48px; height:40px; border-radius:8px; border:1px solid var(--admin-border); background:none; cursor:pointer; padding:2px"
        />
        <input
          type="text" id="color" name="color"
          class="form-control"
          value="{{ old('color', $tag->color) }}"
          placeholder="#FF5733"
          style="flex:1" maxlength="7"
        />
        <span id="tag-preview" style="
          display:inline-block; padding:5px 14px; border-radius:20px;
          font-size:13px; font-weight:700; white-space:nowrap;
          background: {{ old('color', $tag->color ?: '#6c63ff') }}25;
          color: {{ old('color', $tag->color ?: '#6c63ff') }};
          border: 1px solid {{ old('color', $tag->color ?: '#6c63ff') }}50;
        ">{{ old('name', $tag->name) }}</span>
      </div>
    </div>

    <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:12px 14px; margin-bottom:20px; font-size:12.5px; color:var(--admin-text-muted)">
      📊 Đang gán cho <strong style="color:var(--admin-text)">{{ $tag->comics()->count() }} truyện</strong>
      &nbsp;·&nbsp; Tạo: {{ $tag->created_at->format('d/m/Y') }}
    </div>

    <div style="display:flex; gap:10px">
      <button type="submit" class="btn-admin btn-admin-primary">💾 Lưu thay đổi</button>
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
  nameInput.addEventListener('input', () => { preview.textContent = nameInput.value || 'Preview'; });
</script>
@endpush
