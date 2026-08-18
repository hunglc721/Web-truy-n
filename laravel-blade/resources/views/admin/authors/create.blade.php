{{-- resources/views/admin/authors/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Thêm Tác giả mới')
@section('breadcrumb', 'Tác giả / Thêm mới')

@section('topbar-actions')
  <a href="{{ route('admin.authors.index') }}" class="topbar-btn topbar-btn-ghost">← Quay lại</a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">➕ Thêm Tác giả mới</h1>
</div>

<div class="admin-card" style="max-width:640px">
  <form method="POST" action="{{ route('admin.authors.store') }}" enctype="multipart/form-data" novalidate>
    @csrf

    {{-- Avatar Upload --}}
    <div class="form-group">
      <label class="form-label">Avatar tác giả</label>
      <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap">
        <div id="avatar-container">
          <div class="avatar-placeholder" id="avatar-placeholder">?</div>
          <img src="#" alt="Preview" class="avatar-preview" id="avatar-img" style="display:none" />
        </div>
        <div>
          <label for="avatar" style="
            display:inline-flex; align-items:center; gap:7px;
            padding:9px 16px; border-radius:8px; cursor:pointer;
            background:rgba(255,255,255,0.07); border:1px solid var(--admin-border);
            font-size:13px; font-weight:600; color:var(--admin-text);
            transition:background 0.15s;
          " onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.07)'">
            📷 Chọn ảnh
          </label>
          <input type="file" id="avatar" name="avatar" class="hidden" accept="image/jpeg,image/png,image/jpg,image/webp" style="display:none">
          <p class="form-hint" style="margin-top:6px">JPG, PNG, WEBP · Tối đa 2MB</p>
          @error('avatar') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
      </div>
    </div>

    {{-- Tên --}}
    <div class="form-group">
      <label class="form-label" for="name">Tên tác giả <span>*</span></label>
      <input
        type="text" id="name" name="name"
        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
        value="{{ old('name') }}"
        placeholder="Ví dụ: Chugong, Yongje Park..."
        required autofocus
      />
      @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    {{-- Slug --}}
    <div class="form-group">
      <label class="form-label" for="slug">Slug</label>
      <input
        type="text" id="slug" name="slug"
        class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
        value="{{ old('slug') }}"
        placeholder="chugong (để trống sẽ tự tạo)"
      />
      @error('slug') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    {{-- Tiểu sử --}}
    <div class="form-group">
      <label class="form-label" for="bio">Tiểu sử</label>
      <textarea
        id="bio" name="bio"
        class="form-control {{ $errors->has('bio') ? 'is-invalid' : '' }}"
        rows="4"
        placeholder="Giới thiệu ngắn về tác giả, phong cách sáng tác..."
      >{{ old('bio') }}</textarea>
      @error('bio') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div style="display:flex; gap:10px; margin-top:24px">
      <button type="submit" class="btn-admin btn-admin-primary">💾 Lưu tác giả</button>
      <a href="{{ route('admin.authors.index') }}" class="btn-admin btn-admin-ghost">Hủy</a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  const avatarInput = document.getElementById('avatar');
  const avatarImg   = document.getElementById('avatar-img');
  const placeholder = document.getElementById('avatar-placeholder');
  const nameInput   = document.getElementById('name');

  avatarInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => {
        avatarImg.src = e.target.result;
        avatarImg.style.display = 'block';
        placeholder.style.display = 'none';
      };
      reader.readAsDataURL(this.files[0]);
    }
  });

  nameInput.addEventListener('input', function() {
    placeholder.textContent = this.value ? this.value[0].toUpperCase() : '?';
  });
</script>
@endpush
