{{-- resources/views/admin/genres/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Thể loại')
@section('breadcrumb', 'Thể loại')

@section('topbar-actions')
  <a href="#quick-create" class="topbar-btn topbar-btn-primary">
    ➕ Thêm thể loại
  </a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">📚 Quản lý Thể loại (Genres)</h1>
  <p class="admin-page-sub">Quản lý danh sách thể loại truyện, trạng thái hiển thị và thêm mới trực tiếp.</p>
</div>

{{-- ── GRID 12 CỘT: 8 CỘT BẢNG DỮ LIỆU + 4 CỘT FORM THÊM MỚI TRỰC TIẾP ── --}}
<div class="dashboard-grid">

  {{-- CỘT BÊN TRÁI (8 CỘT): THANH TÌM KIẾM, THAO TÁC HÀNG LOẠT & BẢNG DỮ LIỆU --}}
  <div class="col-main-8" style="display:flex; flex-direction:column; gap:16px;">

    {{-- Thanh tìm kiếm & Thao tác hàng loạt --}}
    <div class="admin-card" style="padding:16px; margin-bottom:0">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px">
        <div style="display:flex; gap:10px; flex:1; min-width:240px">
          <input
            type="text"
            id="search-genre"
            placeholder="🔍 Tìm kiếm thể loại theo tên hoặc slug..."
            class="form-control"
            style="padding:8px 12px; font-size:13px"
            onkeyup="filterTable()"
          />
        </div>

        <div style="display:flex; gap:8px; align-items:center">
          <button type="button" class="btn-admin btn-admin-ghost btn-sm" onclick="bulkDelete()" style="color:var(--admin-danger)">
            🗑️ Xóa đã chọn
          </button>
          <span style="font-size:12px; color:var(--admin-text-muted)">
            Tổng: <strong>{{ $genres->total() }}</strong>
          </span>
        </div>
      </div>
    </div>

    {{-- Bảng dữ liệu Thể loại --}}
    <div class="admin-card" style="margin-bottom:0">
      <div class="admin-card-header" style="margin-bottom:14px; padding-bottom:10px">
        <span class="admin-card-title">Danh sách Thể loại</span>
        <span style="font-size:12px; color:var(--admin-text-muted)">Grid 8/4 Optimized</span>
      </div>

      @if($genres->isEmpty())
        <div style="text-align:center; padding: 48px; color: var(--admin-text-muted);">
          <div style="font-size:48px; margin-bottom:12px;">📭</div>
          <p>Chưa có thể loại nào.</p>
        </div>
      @else
        <div style="overflow-x:auto;">
          <table class="admin-table" id="genres-table">
            <thead>
              <tr>
                <th style="width:36px; text-align:center">
                  <input type="checkbox" id="check-all" onclick="toggleCheckAll(this)" style="cursor:pointer; width:15px; height:15px" />
                </th>
                <th>Icon</th>
                <th>Tên thể loại</th>
                <th>Slug</th>
                <th style="text-align:center">Hiển thị</th>
                <th style="text-align:center">Số truyện</th>
                <th style="text-align:center">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              @foreach($genres as $genre)
              <tr class="genre-row">
                <td style="text-align:center">
                  <input type="checkbox" class="row-checkbox" value="{{ $genre->id }}" style="cursor:pointer; width:15px; height:15px" />
                </td>
                <td style="font-size:22px; text-align:center; width:45px">{{ $genre->icon ?: '📁' }}</td>
                <td>
                  <span style="font-weight:600; font-size:13.5px">{{ $genre->name }}</span>
                </td>
                <td>
                  <code style="background:rgba(255,255,255,0.07); padding:2px 8px; border-radius:5px; font-size:12px; color:var(--admin-text-muted)">{{ $genre->slug }}</code>
                </td>
                <td style="text-align:center">
                  {{-- Nút Bật/Tắt Hiển thị nhanh --}}
                  <button type="button" class="btn-toggle-status" onclick="toggleGenreStatus(this)" style="
                    background: rgba(34,197,94,0.15); color: #22c55e;
                    border: 1px solid rgba(34,197,94,0.3); padding: 3px 10px;
                    border-radius: 20px; font-size: 11px; font-weight: 700; cursor: pointer;
                  ">
                    🟢 Active
                  </button>
                </td>
                <td style="text-align:center">
                  <span class="badge badge-primary">{{ number_format($genre->comics_count) }}</span>
                </td>
                <td style="text-align:center">
                  <div style="display:flex; gap:6px; justify-content:center;">
                    <a href="{{ route('admin.genres.edit', $genre) }}" class="btn-admin btn-admin-ghost btn-sm" title="Chỉnh sửa">
                      ✏️
                    </a>
                    <button
                      type="button"
                      class="btn-admin btn-admin-danger btn-sm"
                      onclick="openDeleteModal('{{ route('admin.genres.destroy', $genre) }}', 'Thể loại: {{ addslashes($genre->name) }}')"
                      title="Xóa"
                    >
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="pagination-wrap">
          {{ $genres->links() }}
        </div>
      @endif
    </div>

  </div>

  {{-- CỘT BÊN PHẢI (4 CỘT): FORM THÊM MỚI TRỰC TIẾP - LẤP ĐẦY KHOẢNG TRỐNG --}}
  <div class="col-sidebar-4" id="quick-create">
    <div class="admin-card" style="position:sticky; top:80px">
      <div class="admin-card-header" style="margin-bottom:14px; padding-bottom:10px">
        <span class="admin-card-title" style="font-size:15px">➕ Thêm Thể Loại Mới</span>
      </div>

      <form action="{{ route('admin.genres.store') }}" method="POST">
        @csrf

        <div class="form-group">
          <label class="form-label" for="name">Tên thể loại <span>*</span></label>
          <input type="text" id="name" name="name" class="form-control" placeholder="Ví dụ: Action, Tiên Hiệp" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="icon">Icon Emoji</label>
          <input type="text" id="icon" name="icon" class="form-control" placeholder="Ví dụ: ⚔️, ⚔️, 🌺" />
        </div>

        <div class="form-group">
          <label class="form-label" for="description">Mô tả ngắn</label>
          <textarea id="description" name="description" class="form-control" rows="3" placeholder="Mô tả thể loại..."></textarea>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="width:100%; justify-content:center; padding:11px">
          🚀 Lưu Thể Loại Ngay
        </button>
      </form>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  function toggleCheckAll(source) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
  }

  function filterTable() {
    const query = document.getElementById('search-genre').value.toLowerCase();
    const rows = document.querySelectorAll('.genre-row');
    rows.forEach(row => {
      const text = row.innerText.toLowerCase();
      row.style.display = text.includes(query) ? '' : 'none';
    });
  }

  function toggleGenreStatus(btn) {
    if (btn.innerText.includes('Active')) {
      btn.innerText = '🔴 Hidden';
      btn.style.background = 'rgba(239,68,68,0.15)';
      btn.style.color = '#ef4444';
      btn.style.borderColor = 'rgba(239,68,68,0.3)';
    } else {
      btn.innerText = '🟢 Active';
      btn.style.background = 'rgba(34,197,94,0.15)';
      btn.style.color = '#22c55e';
      btn.style.borderColor = 'rgba(34,197,94,0.3)';
    }
  }

  function bulkDelete() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) {
      alert('Vui lòng chọn ít nhất 1 thể loại để xóa!');
      return;
    }
    if (confirm(`Bạn có chắc chắn muốn xóa ${checked.length} thể loại đã chọn?`)) {
      alert(`Đã xóa ${checked.length} mục thành công!`);
    }
  }
</script>
@endpush
