{{-- resources/views/admin/authors/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Sửa tác giả: ' . $author->name)
@section('breadcrumb', 'Tác giả / Chỉnh sửa')

@section('topbar-actions')
  <a href="{{ route('admin.authors.index') }}" class="topbar-btn topbar-btn-ghost">← Quay lại</a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">✏️ Sửa tác giả: {{ $author->name }}</h1>
</div>

<div class="admin-card" style="max-width:640px">
  <form method="POST" action="{{ route('admin.authors.update', $author) }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    {{-- Avatar --}}
    <div class="form-group">
      <label class="form-label">Avatar tác giả</label>
      <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap">
        <div>
          @if($author->avatar)
            <img src="{{ asset('storage/' . $author->avatar) }}" alt="{{ $author->name }}" class="avatar-preview" id="avatar-img" />
          @else
            <div class="avatar-placeholder" id="avatar-placeholder-init">
              {{ strtoupper(substr($author->name, 0, 1)) }}
            </div>
            <img src="#" alt="Preview" class="avatar-preview" id="avatar-img" style="display:none" />
          @endif
        </div>
        <div>
          <label for="avatar" style="
            display:inline-flex; align-items:center; gap:7px;
            padding:9px 16px; border-radius:8px; cursor:pointer;
            background:rgba(255,255,255,0.07); border:1px solid var(--admin-border);
            font-size:13px; font-weight:600; color:var(--admin-text);
          ">
            📷 Đổi ảnh
          </label>
          <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" style="display:none">
          <p class="form-hint" style="margin-top:6px">
            {{ $author->avatar ? '✅ Đang dùng ảnh hiện tại. Upload ảnh mới để thay thế.' : 'Chưa có ảnh.' }}
          </p>
          @error('avatar') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="name">Tên tác giả <span>*</span></label>
      <input
        type="text" id="name" name="name"
        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
        value="{{ old('name', $author->name) }}"
        required autofocus
      />
      @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="slug">Slug</label>
      <input
        type="text" id="slug" name="slug"
        class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
        value="{{ old('slug', $author->slug) }}"
      />
      @error('slug') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="bio">Tiểu sử</label>
      <textarea
        id="bio" name="bio"
        class="form-control {{ $errors->has('bio') ? 'is-invalid' : '' }}"
        rows="4"
      >{{ old('bio', $author->bio) }}</textarea>
      @error('bio') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:12px 14px; margin-bottom:20px; font-size:12.5px; color:var(--admin-text-muted)">
      📊 Tham gia <strong style="color:var(--admin-text)">{{ $author->comics()->count() }} truyện</strong>
      &nbsp;·&nbsp; Tham gia: {{ $author->created_at->format('d/m/Y') }}
    </div>

    <div style="display:flex; gap:10px">
      <button type="submit" class="btn-admin btn-admin-primary">💾 Lưu thay đổi</button>
      <a href="{{ route('admin.authors.index') }}" class="btn-admin btn-admin-ghost">Hủy</a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  const avatarInput = document.getElementById('avatar');
  const avatarImg   = document.getElementById('avatar-img');

  avatarInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => {
        avatarImg.src = e.target.result;
        avatarImg.style.display = 'block';
        const ph = document.getElementById('avatar-placeholder-init');
        if (ph) ph.style.display = 'none';
      };
      reader.readAsDataURL(this.files[0]);
    }
  });
</script>
@endpush
