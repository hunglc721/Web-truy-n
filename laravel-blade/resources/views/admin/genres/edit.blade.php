{{-- resources/views/admin/genres/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Chỉnh sửa: ' . $genre->name)
@section('breadcrumb', 'Thể loại / Chỉnh sửa')

@section('topbar-actions')
  <a href="{{ route('admin.genres.index') }}" class="topbar-btn topbar-btn-ghost">
    ← Quay lại
  </a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">✏️ Chỉnh sửa: {{ $genre->name }}</h1>
  <p class="admin-page-sub">Cập nhật thông tin thể loại. Slug thay đổi sẽ ảnh hưởng SEO.</p>
</div>

<div class="admin-card" style="max-width:600px">
  <form method="POST" action="{{ route('admin.genres.update', $genre) }}" novalidate>
    @csrf
    @method('PUT')

    {{-- Tên --}}
    <div class="form-group">
      <label class="form-label" for="name">Tên thể loại <span>*</span></label>
      <input
        type="text" id="name" name="name"
        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
        value="{{ old('name', $genre->name) }}"
        required autofocus
      />
      @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    {{-- Slug --}}
    <div class="form-group">
      <label class="form-label" for="slug">Slug (URL)</label>
      <input
        type="text" id="slug" name="slug"
        class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
        value="{{ old('slug', $genre->slug) }}"
      />
      <p class="form-hint">
        ⚠️ Thay đổi slug sẽ phá vỡ các đường dẫn cũ. URL hiện tại:
        <code style="font-size:11px; color:var(--admin-primary)">/genres/{{ $genre->slug }}</code>
      </p>
      @error('slug') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    {{-- Icon --}}
    <div class="form-group">
      <label class="form-label" for="icon">Icon (Emoji)</label>
      <div style="display:flex; gap:10px; align-items:center">
        <input
          type="text" id="icon" name="icon"
          class="form-control"
          value="{{ old('icon', $genre->icon) }}"
          maxlength="10"
          style="max-width:140px"
        />
        <span id="icon-preview" style="font-size:32px">{{ old('icon', $genre->icon ?: '📁') }}</span>
      </div>
    </div>

    {{-- Mô tả --}}
    <div class="form-group">
      <label class="form-label" for="description">Mô tả</label>
      <textarea
        id="description" name="description"
        class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
        rows="3"
      >{{ old('description', $genre->description) }}</textarea>
      @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    {{-- Info --}}
    <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:12px 14px; margin-bottom:20px; font-size:12.5px; color:var(--admin-text-muted);">
      📊 Đang dùng bởi <strong style="color:var(--admin-text)">{{ $genre->comics()->count() }} truyện</strong>
      &nbsp;·&nbsp; Tạo: {{ $genre->created_at->format('d/m/Y') }}
      &nbsp;·&nbsp; Sửa lần cuối: {{ $genre->updated_at->diffForHumans() }}
    </div>

    <div style="display:flex; gap:10px;">
      <button type="submit" class="btn-admin btn-admin-primary">💾 Lưu thay đổi</button>
      <a href="{{ route('admin.genres.index') }}" class="btn-admin btn-admin-ghost">Hủy</a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  const iconInput = document.getElementById('icon');
  const iconPreview = document.getElementById('icon-preview');
  iconInput.addEventListener('input', () => {
    iconPreview.textContent = iconInput.value || '📁';
  });
</script>
@endpush
