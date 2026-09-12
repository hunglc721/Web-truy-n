{{-- resources/views/admin/comics/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Chỉnh sửa: ' . $comic->title . ' — WebComics')
@section('breadcrumb', 'Truyện / ' . Str::limit($comic->title, 25) . ' / Chỉnh sửa')

@section('topbar-actions')
  <a href="{{ route('admin.comics.index') }}" class="topbar-btn topbar-btn-ghost">← Danh sách truyện</a>
  <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="topbar-btn topbar-btn-primary">📑 Quản lý Chapters ({{ $comic->chapters()->count() }})</a>
@endsection

@push('styles')
<style>
  .comic-form-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    align-items: start;
  }
  @media(max-width: 980px) {
    .comic-form-grid {
      grid-template-columns: 1fr;
    }
  }
  .checkbox-group-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 8px;
    max-height: 220px;
    overflow-y: auto;
    padding: 12px;
    background: rgba(255, 255, 255, .03);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
  }
  .checkbox-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 6px;
    background: rgba(255, 255, 255, .04);
    border: 1px solid var(--admin-border);
    font-size: 12.5px;
    cursor: pointer;
    transition: .15s;
    user-select: none;
  }
  .checkbox-pill:hover {
    background: rgba(108, 99, 255, .15);
    border-color: rgba(108, 99, 255, .4);
  }
  .checkbox-pill input[type="checkbox"] {
    accent-color: var(--admin-primary);
    cursor: pointer;
  }
  .cover-preview-box {
    width: 100%;
    aspect-ratio: 3/4;
    max-width: 220px;
    margin: 0 auto 16px;
    border-radius: 10px;
    overflow: hidden;
    border: 2px dashed var(--admin-border);
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, .02);
    position: relative;
  }
  .cover-preview-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  .cover-placeholder {
    text-align: center;
    color: var(--admin-text-muted);
    font-size: 13px;
    padding: 16px;
  }
  .custom-file-upload {
    display: block;
    width: 100%;
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    background: rgba(108, 99, 255, .12);
    border: 1px dashed var(--admin-primary);
    color: var(--admin-primary);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: .15s;
  }
  .custom-file-upload:hover {
    background: rgba(108, 99, 255, .22);
  }
</style>
@endpush

@section('content')
<div class="admin-page-header">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px">
    <div>
      <h1 class="admin-page-title">✏️ Chỉnh Sửa Bộ Truyện: {{ $comic->title }}</h1>
      <p class="admin-page-sub">Cập nhật thông tin chi tiết, ảnh bìa, phân loại thể loại và tác giả.</p>
    </div>
    <div style="display:flex; gap:10px">
      <a href="{{ route('comics.show', $comic->slug) }}" target="_blank" rel="noopener" class="btn-admin btn-admin-ghost">
        ↗ Xem trang người đọc
      </a>
    </div>
  </div>
</div>

<form action="{{ route('admin.comics.update', $comic->id) }}" method="POST" enctype="multipart/form-data">
  @csrf
  @method('PUT')

  <div class="comic-form-grid">
    {{-- ── CỘT TRÁI: THÔNG TIN CHÍNH ── --}}
    <div style="display:flex; flex-direction:column; gap:20px;">
      <div class="admin-card">
        <h2 style="font-size:15px; font-weight:700; color:var(--admin-text); margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid var(--admin-border)">
          📝 Thông tin cơ bản
        </h2>

        {{-- Tiêu đề truyện --}}
        <div class="form-group">
          <label class="form-label" for="title">Tên bộ truyện <span>*</span></label>
          <input
            type="text"
            id="title"
            name="title"
            class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
            value="{{ old('title', $comic->title) }}"
            placeholder="Ví dụ: Solo Leveling, Đại Quản Gia Ma Hoàng..."
            required
            autofocus
          />
          @error('title') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        {{-- Slug (Đường dẫn tĩnh) --}}
        <div class="form-group">
          <label class="form-label" for="slug">Đường dẫn tĩnh (Slug)</label>
          <input
            type="text"
            id="slug"
            name="slug"
            class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
            value="{{ old('slug', $comic->slug) }}"
            placeholder="tu-dong-sinh-neu-de-trong"
          />
          <span class="form-hint">Được sử dụng cho đường dẫn: /truyen/<strong>slug-cua-truyen</strong>. Chỉ chữ thường, số và dấu gạch ngang.</span>
          @error('slug') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        {{-- Mô tả nội dung --}}
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="description">Mô tả tóm tắt nội dung</label>
          <textarea
            id="description"
            name="description"
            rows="6"
            class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
            placeholder="Nhập nội dung giới thiệu, cốt truyện..."
          >{{ old('description', $comic->description) }}</textarea>
          @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
      </div>

      {{-- Thể loại & Phân loại --}}
      <div class="admin-card">
        <h2 style="font-size:15px; font-weight:700; color:var(--admin-text); margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--admin-border)">
          🏷️ Thể loại (Genre) <span>*</span>
        </h2>
        <p style="font-size:12.5px; color:var(--admin-text-muted); margin-bottom:12px">
          Chọn ít nhất 1 thể loại chính cho bộ truyện.
        </p>

        @php
          $selectedGenreIds = old('genre_ids', $comic->genres->pluck('id')->toArray());
        @endphp
        <div class="checkbox-group-grid">
          @foreach($genres as $genre)
            <label class="checkbox-pill">
              <input
                type="checkbox"
                name="genre_ids[]"
                value="{{ $genre->id }}"
                {{ in_array($genre->id, $selectedGenreIds) ? 'checked' : '' }}
              />
              <span>{{ $genre->name }}</span>
            </label>
          @endforeach
        </div>
        @error('genre_ids') <span class="invalid-feedback" style="display:block; margin-top:8px">{{ $message }}</span> @enderror
      </div>

      {{-- Tác giả & Họa sĩ --}}
      <div class="admin-card">
        <h2 style="font-size:15px; font-weight:700; color:var(--admin-text); margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--admin-border)">
          ✍️ Tác giả / Họa sĩ
        </h2>

        @php
          $selectedAuthorIds = old('author_ids', $comic->authors->pluck('id')->toArray());
        @endphp
        <div class="checkbox-group-grid">
          @foreach($authors as $author)
            <label class="checkbox-pill">
              <input
                type="checkbox"
                name="author_ids[]"
                value="{{ $author->id }}"
                {{ in_array($author->id, $selectedAuthorIds) ? 'checked' : '' }}
              />
              <span>{{ $author->name }}</span>
            </label>
          @endforeach
        </div>
        @error('author_ids') <span class="invalid-feedback" style="display:block; margin-top:8px">{{ $message }}</span> @enderror
      </div>

      {{-- Nhãn Tags --}}
      <div class="admin-card">
        <h2 style="font-size:15px; font-weight:700; color:var(--admin-text); margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--admin-border)">
          🔖 Nhãn Tags
        </h2>

        @php
          $selectedTagIds = old('tag_ids', $comic->tags->pluck('id')->toArray());
        @endphp
        <div class="checkbox-group-grid">
          @foreach($tags as $tag)
            <label class="checkbox-pill">
              <input
                type="checkbox"
                name="tag_ids[]"
                value="{{ $tag->id }}"
                {{ in_array($tag->id, $selectedTagIds) ? 'checked' : '' }}
              />
              <span>#{{ $tag->name }}</span>
            </label>
          @endforeach
        </div>
        @error('tag_ids') <span class="invalid-feedback" style="display:block; margin-top:8px">{{ $message }}</span> @enderror
      </div>
    </div>

    {{-- ── CỘT PHẢI: TRẠNG THÁI & ẢNH BÌA ── --}}
    <div style="display:flex; flex-direction:column; gap:20px;">
      {{-- Card Xuất bản --}}
      <div class="admin-card">
        <h2 style="font-size:15px; font-weight:700; color:var(--admin-text); margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--admin-border)">
          ⚡ Xuất bản & Trạng thái
        </h2>

        {{-- Trạng thái --}}
        <div class="form-group">
          <label class="form-label" for="status">Tiến độ phát hành <span>*</span></label>
          @php $currentStatus = old('status', $comic->status); @endphp
          <select id="status" name="status" class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" required>
            <option value="ongoing" {{ $currentStatus === 'ongoing' ? 'selected' : '' }}>🟢 Đang ra (Ongoing)</option>
            <option value="completed" {{ $currentStatus === 'completed' ? 'selected' : '' }}>🔵 Hoàn thành (Completed)</option>
            <option value="hiatus" {{ $currentStatus === 'hiatus' ? 'selected' : '' }}>🟡 Tạm ngưng (Hiatus)</option>
            <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>🔴 Đã hủy (Cancelled)</option>
          </select>
          @error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        {{-- Tùy chọn cờ --}}
        <div class="form-group" style="display:flex; flex-direction:column; gap:10px; margin-top:14px">
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer">
            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $comic->is_featured) ? 'checked' : '' }} style="accent-color:var(--admin-primary)">
            <span>⭐ Đặt làm truyện Nổi bật</span>
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer">
            <input type="checkbox" name="is_original" value="1" {{ old('is_original', $comic->is_original) ? 'checked' : '' }} style="accent-color:var(--admin-primary)">
            <span>🎨 Tác phẩm Gốc / Sáng tác</span>
          </label>
        </div>

        {{-- Ngày phát hành --}}
        <div class="form-group" style="margin-top:14px">
          <label class="form-label" for="published_at">Ngày công bố</label>
          <input
            type="date"
            id="published_at"
            name="published_at"
            class="form-control {{ $errors->has('published_at') ? 'is-invalid' : '' }}"
            value="{{ old('published_at', $comic->published_at ? $comic->published_at->format('Y-m-d') : '') }}"
          />
          @error('published_at') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div style="border-top:1px solid var(--admin-border); padding-top:18px; margin-top:18px; display:flex; flex-direction:column; gap:10px">
          <button type="submit" class="btn-admin btn-admin-primary" style="justify-content:center; padding:12px">
            💾 Lưu Thay Đổi
          </button>
          <a href="{{ route('admin.comics.index') }}" class="btn-admin btn-admin-ghost" style="justify-content:center">
            Hủy bỏ
          </a>
        </div>
      </div>

      {{-- Card Ảnh Bìa --}}
      <div class="admin-card">
        <h2 style="font-size:15px; font-weight:700; color:var(--admin-text); margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--admin-border)">
          🖼️ Ảnh bìa (Cover Image)
        </h2>

        <div class="cover-preview-box" id="cover-preview-box">
          @if($comic->cover_url)
            <img src="{{ $comic->cover_url }}" alt="Bìa {{ $comic->title }}" id="cover-img-preview" />
          @else
            <div class="cover-placeholder" id="cover-placeholder">
              <div style="font-size:32px; margin-bottom:8px">📖</div>
              Chưa có ảnh bìa
            </div>
          @endif
        </div>

        <label class="custom-file-upload" for="cover_image">
          📁 Chọn ảnh bìa mới...
        </label>
        <input
          type="file"
          id="cover_image"
          name="cover_image"
          accept="image/jpeg,image/png,image/jpg,image/webp"
          style="display:none"
          onchange="previewCoverImage(this)"
        />
        <span class="form-hint" style="text-align:center; display:block; margin-top:8px">
          Định dạng: JPG, PNG, WEBP (Tối đa 2MB).
        </span>
        @error('cover_image') <span class="invalid-feedback" style="display:block; text-align:center">{{ $message }}</span> @enderror
      </div>
    </div>
  </div>
</form>

<script>
  function previewCoverImage(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const box = document.getElementById('cover-preview-box');
        box.innerHTML = `<img src="${e.target.result}" alt="Preview" id="cover-img-preview" />`;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }
</script>
@endsection
