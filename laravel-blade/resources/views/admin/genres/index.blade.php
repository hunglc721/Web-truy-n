@extends('layouts.admin')
@section('title','Quản lý Thể loại')
@section('breadcrumb','Thể loại')
@section('topbar-actions')<a href="#quick-create" class="topbar-btn topbar-btn-primary">➕ Thêm thể loại</a>@endsection

@section('content')
<div class="admin-page-header"><h1 class="admin-page-title">📚 Quản lý Thể loại</h1><p class="admin-page-sub">CRUD thật từ database. Không còn nút Active/Hidden giả vì schema hiện tại không có trạng thái hiển thị cho Genre.</p></div>
<div class="dashboard-grid">
  <div class="col-main-8" style="display:flex;flex-direction:column;gap:16px">
    <div class="admin-card" style="padding:16px;margin-bottom:0"><div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap"><input id="search-genre" class="form-control" style="max-width:390px" placeholder="🔍 Tìm tên, slug..."><span style="font-size:12px;color:var(--admin-text-muted)">Tổng: <strong>{{ $genres->total() }}</strong></span></div></div>
    <div class="admin-card" style="margin-bottom:0">
      <form method="POST" action="{{ route('admin.genres.bulkDestroy') }}" id="genre-bulk-form">@csrf @method('DELETE')
        <div class="admin-card-header"><span class="admin-card-title">Danh sách Thể loại</span><button type="submit" id="genre-bulk-delete" class="btn-admin btn-admin-danger btn-sm" disabled>🗑️ Xóa đã chọn</button></div>
        @if($genres->isEmpty())<div style="text-align:center;padding:48px;color:var(--admin-text-muted)">📭 Chưa có thể loại nào.</div>@else
        <div style="overflow-x:auto"><table class="admin-table" id="genres-table"><thead><tr><th style="width:36px"><input type="checkbox" id="genre-check-all"></th><th>Icon</th><th>Tên</th><th>Slug</th><th>Mô tả</th><th style="text-align:center">Số truyện</th><th style="text-align:center">Thao tác</th></tr></thead><tbody>
          @foreach($genres as $genre)<tr class="genre-row"><td><input type="checkbox" class="genre-check" name="ids[]" value="{{ $genre->id }}"></td><td style="font-size:22px">{{ $genre->icon ?: '📁' }}</td><td><strong>{{ $genre->name }}</strong></td><td><code>{{ $genre->slug }}</code></td><td style="max-width:260px;color:var(--admin-text-muted)">{{ Str::limit($genre->description,70) ?: '—' }}</td><td style="text-align:center"><span class="badge badge-primary">{{ number_format($genre->comics_count) }}</span></td><td><div style="display:flex;gap:6px;justify-content:center"><a href="{{ route('admin.genres.edit',$genre) }}" class="btn-admin btn-admin-ghost btn-sm">✏️ Sửa</a><button type="button" class="btn-admin btn-admin-danger btn-sm" onclick="confirmDelete('{{ route('admin.genres.destroy',$genre) }}','Thể loại: {{ addslashes($genre->name) }}')" {{ $genre->comics_count>0?'title=\"Đang có truyện liên kết; backend sẽ từ chối xóa\"':'' }}>🗑️</button></div></td></tr>@endforeach
        </tbody></table></div><div class="pagination-wrap">{{ $genres->links() }}</div>@endif
      </form>
    </div>
  </div>
  <aside class="col-sidebar-4" id="quick-create"><div class="admin-card" style="position:sticky;top:80px"><div class="admin-card-header"><span class="admin-card-title">➕ Thêm Thể Loại</span></div><form action="{{ route('admin.genres.store') }}" method="POST">@csrf<div class="form-group"><label class="form-label">Tên <span>*</span></label><input class="form-control" name="name" required value="{{ old('name') }}"></div><div class="form-group"><label class="form-label">Slug</label><input class="form-control" name="slug" value="{{ old('slug') }}" placeholder="Để trống sẽ tự tạo"></div><div class="form-group"><label class="form-label">Icon Emoji</label><input class="form-control" name="icon" id="genre-icon" maxlength="10" value="{{ old('icon') }}"><div style="font-size:30px;margin-top:6px" id="genre-icon-preview">{{ old('icon') ?: '📁' }}</div></div><div class="form-group"><label class="form-label">Mô tả</label><textarea class="form-control" name="description">{{ old('description') }}</textarea></div><button class="btn-admin btn-admin-primary" style="width:100%;justify-content:center">💾 Lưu thể loại</button></form></div></aside>
</div>
@endsection

@push('scripts')
<script>
(() => { const search=document.getElementById('search-genre'),rows=[...document.querySelectorAll('.genre-row')],checks=[...document.querySelectorAll('.genre-check')],all=document.getElementById('genre-check-all'),bulk=document.getElementById('genre-bulk-delete'); search?.addEventListener('input',()=>{const q=search.value.toLowerCase();rows.forEach(r=>r.style.display=r.innerText.toLowerCase().includes(q)?'':'none')}); const sync=()=>{const n=checks.filter(c=>c.checked).length;bulk.disabled=n===0;if(all)all.checked=n>0&&n===checks.length;};checks.forEach(c=>c.addEventListener('change',sync));all?.addEventListener('change',()=>{checks.forEach(c=>c.checked=all.checked);sync()});document.getElementById('genre-bulk-form')?.addEventListener('submit',e=>{const n=checks.filter(c=>c.checked).length;if(!n||!confirm(`Xóa ${n} thể loại đã chọn? Các thể loại đang có truyện sẽ được bỏ qua.`))e.preventDefault()});document.getElementById('genre-icon')?.addEventListener('input',e=>document.getElementById('genre-icon-preview').textContent=e.target.value||'📁'); })();
</script>
@endpush
