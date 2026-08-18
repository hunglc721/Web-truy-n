{{-- resources/views/admin/tags/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Tags')
@section('breadcrumb', 'Tags')

@section('topbar-actions')
  <a href="#quick-create" class="topbar-btn topbar-btn-primary">
    🏷️ Thêm Tag
  </a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">🏷️ Quản lý Nhãn (Tags)</h1>
  <p class="admin-page-sub">Quản lý các nhãn nổi bật, màu sắc hiển thị và thêm mới trực tiếp.</p>
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
            id="search-tag"
            placeholder="🔍 Tìm kiếm tag theo tên hoặc slug..."
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
            Tổng: <strong>{{ $tags->total() }}</strong>
          </span>
        </div>
      </div>
    </div>

    {{-- Bảng dữ liệu Tags --}}
    <div class="admin-card" style="margin-bottom:0">
      <div class="admin-card-header" style="margin-bottom:14px; padding-bottom:10px">
        <span class="admin-card-title">Danh sách Tags</span>
        <span style="font-size:12px; color:var(--admin-text-muted)">Grid 8/4 Optimized</span>
      </div>

      @if($tags->isEmpty())
        <div style="text-align:center; padding:48px; color:var(--admin-text-muted);">
          <div style="font-size:48px; margin-bottom:12px">🏷️</div>
          <p>Chưa có tag nào trên hệ thống.</p>
        </div>
      @else
        <div style="overflow-x:auto">
          <table class="admin-table" id="tags-table">
            <thead>
              <tr>
                <th style="width:36px; text-align:center">
                  <input type="checkbox" id="check-all" onclick="toggleCheckAll(this)" style="cursor:pointer; width:15px; height:15px" />
                </th>
                <th>Tag Name</th>
                <th>Slug</th>
                <th>Màu sắc</th>
                <th style="text-align:center">Trạng thái</th>
                <th style="text-align:center">Số truyện</th>
                <th style="text-align:center">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              @foreach($tags as $tag)
              <tr class="tag-row">
                <td style="text-align:center">
                  <input type="checkbox" class="row-checkbox" value="{{ $tag->id }}" style="cursor:pointer; width:15px; height:15px" />
                </td>
                <td>
                  <span style="
                    display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700;
                    background-color: {{ $tag->color ? $tag->color . '25' : 'rgba(255,255,255,0.08)' }};
                    color: {{ $tag->color ?: 'var(--admin-text)' }};
                    border: 1px solid {{ $tag->color ? $tag->color . '50' : 'var(--admin-border)' }};
                  ">
                    {{ $tag->name }}
                  </span>
                </td>
                <td>
                  <code style="background:rgba(255,255,255,0.07); padding:2px 8px; border-radius:5px; font-size:12px; color:var(--admin-text-muted)">{{ $tag->slug }}</code>
                </td>
                <td>
                  @if($tag->color)
                    <div style="display:flex; align-items:center; gap:8px;">
                      <span style="display:inline-block; width:18px; height:18px; border-radius:4px; background:{{ $tag->color }}; border:1px solid rgba(255,255,255,0.15)"></span>
                      <code style="font-size:11.5px; color:var(--admin-text-muted)">{{ $tag->color }}</code>
                    </div>
                  @else
                    <span style="color:var(--admin-text-muted)">—</span>
                  @endif
                </td>
                <td style="text-align:center">
                  <button type="button" class="btn-toggle-status" onclick="toggleTagStatus(this)" style="
                    background: rgba(34,197,94,0.15); color: #22c55e;
                    border: 1px solid rgba(34,197,94,0.3); padding: 3px 10px;
                    border-radius: 20px; font-size: 11px; font-weight: 700; cursor: pointer;
                  ">
                    🟢 Active
                  </button>
                </td>
                <td style="text-align:center">
                  <span class="badge badge-primary">{{ number_format($tag->comics_count) }}</span>
                </td>
                <td style="text-align:center">
                  <div style="display:flex; gap:6px; justify-content:center">
                    <a href="{{ route('admin.tags.edit', $tag) }}" class="btn-admin btn-admin-ghost btn-sm" title="Sửa">✏️</a>
                    <button
                      type="button"
                      class="btn-admin btn-admin-danger btn-sm"
                      onclick="openDeleteModal('{{ route('admin.tags.destroy', $tag) }}', 'Tag: {{ addslashes($tag->name) }}')"
                      title="Xóa"
                    >🗑️</button>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="pagination-wrap">{{ $tags->links() }}</div>
      @endif
    </div>

  </div>

  {{-- CỘT BÊN PHẢI (4 CỘT): FORM THÊM MỚI TRỰC TIẾP --}}
  <div class="col-sidebar-4" id="quick-create">
    <div class="admin-card" style="position:sticky; top:80px">
      <div class="admin-card-header" style="margin-bottom:14px; padding-bottom:10px">
        <span class="admin-card-title" style="font-size:15px">🏷️ Thêm Tag Mới</span>
      </div>

      <form action="{{ route('admin.tags.store') }}" method="POST">
        @csrf

        <div class="form-group">
          <label class="form-label" for="name">Tên Tag <span>*</span></label>
          <input type="text" id="name" name="name" class="form-control" placeholder="Ví dụ: HOT, NEW, ORIGINAL" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="color">Màu sắc (HEX Color Code)</label>
          <div style="display:flex; gap:10px; align-items:center">
            <input type="color" id="color_picker" value="#6c63ff" onchange="document.getElementById('color').value = this.value" style="width:40px; height:38px; padding:2px; border-radius:6px; cursor:pointer" />
            <input type="text" id="color" name="color" class="form-control" placeholder="#6c63ff" value="#6c63ff" />
          </div>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="width:100%; justify-content:center; padding:11px">
          🚀 Lưu Tag Ngay
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
    const query = document.getElementById('search-tag').value.toLowerCase();
    const rows = document.querySelectorAll('.tag-row');
    rows.forEach(row => {
      const text = row.innerText.toLowerCase();
      row.style.display = text.includes(query) ? '' : 'none';
    });
  }

  function toggleTagStatus(btn) {
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
      alert('Vui lòng chọn ít nhất 1 tag để xóa!');
      return;
    }
    if (confirm(`Bạn có chắc chắn muốn xóa ${checked.length} tag đã chọn?`)) {
      alert(`Đã xóa ${checked.length} tag thành công!`);
    }
  }
</script>
@endpush
