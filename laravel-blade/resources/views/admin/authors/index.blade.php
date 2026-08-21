{{-- resources/views/admin/authors/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Tác giả')
@section('breadcrumb', 'Tác giả')

@section('topbar-actions')
  <a href="{{ route('admin.authors.create') }}" class="topbar-btn topbar-btn-primary">➕ Thêm tác giả</a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">✍️ Quản lý Tác giả</h1>
  <p class="admin-page-sub">Tìm kiếm, thêm, sửa và quản lý tác giả liên kết với truyện.</p>
</div>

<div class="admin-card" style="margin-bottom:18px;padding:16px 20px">
  <form method="GET" action="{{ route('admin.authors.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="search" value="{{ request('search') }}" class="form-control" style="flex:1;min-width:220px" placeholder="🔍 Tìm tên, slug hoặc tiểu sử..." />
    <button type="submit" class="btn-admin btn-admin-primary">Tìm</button>
    @if(request('search'))<a href="{{ route('admin.authors.index') }}" class="btn-admin btn-admin-ghost">Xóa lọc</a>@endif
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title">Danh sách Tác giả</span>
    <span style="font-size:13px;color:var(--admin-text-muted)">{{ $authors->count() }} / {{ $authors->total() }} kết quả</span>
  </div>

  @if($authors->isEmpty())
    <div style="text-align:center;padding:48px;color:var(--admin-text-muted)"><div style="font-size:48px;margin-bottom:12px">✍️</div><p>Không tìm thấy tác giả nào.</p></div>
  @else
    <div style="overflow-x:auto">
      <table class="admin-table" style="min-width:780px">
        <thead><tr><th>#</th><th>Avatar</th><th>Tên tác giả</th><th>Slug</th><th>Tiểu sử</th><th style="text-align:center">Số truyện</th><th style="text-align:center">Thao tác</th></tr></thead>
        <tbody>
          @foreach($authors as $author)
          <tr>
            <td style="color:var(--admin-text-muted);font-size:12px">{{ $author->id }}</td>
            <td>
              @if($author->avatar)
                <img src="{{ asset('storage/' . $author->avatar) }}" alt="{{ $author->name }}" class="avatar-preview" style="width:42px;height:42px" loading="lazy" />
              @else
                <div class="avatar-placeholder" style="width:42px;height:42px;font-size:16px">{{ strtoupper(substr($author->name,0,1)) }}</div>
              @endif
            </td>
            <td><strong>{{ $author->name }}</strong></td>
            <td><code style="background:rgba(255,255,255,.07);padding:2px 8px;border-radius:5px;font-size:12px;color:var(--admin-text-muted)">{{ $author->slug }}</code></td>
            <td style="color:var(--admin-text-muted);font-size:13px;max-width:260px">{{ Str::limit($author->bio,80) ?: '—' }}</td>
            <td style="text-align:center"><span class="badge badge-primary">{{ number_format($author->comics_count) }}</span></td>
            <td style="text-align:center">
              <div style="display:flex;gap:6px;justify-content:center">
                <a href="{{ route('admin.authors.edit',$author) }}" class="btn-admin btn-admin-ghost btn-sm">✏️ Sửa</a>
                <button type="button" class="btn-admin btn-admin-danger btn-sm" data-delete-url="{{ route('admin.authors.destroy',$author) }}" data-delete-name="{{ $author->name }}" onclick="confirmDelete(this.dataset.deleteUrl,this.dataset.deleteName)">🗑️ Xóa</button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="pagination-wrap">{{ $authors->links() }}</div>
  @endif
</div>
@endsection
