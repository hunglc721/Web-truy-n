{{-- resources/views/admin/authors/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Quản lý Tác giả')
@section('breadcrumb', 'Tác giả')

@section('topbar-actions')
  <a href="{{ route('admin.authors.create') }}" class="topbar-btn topbar-btn-primary">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
      <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Thêm tác giả
  </a>
@endsection

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">✍️ Quản lý Tác giả</h1>
  <p class="admin-page-sub">Tổng cộng {{ $authors->total() }} tác giả</p>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title">Danh sách Tác giả</span>
    <a href="{{ route('admin.authors.create') }}" class="btn-admin btn-admin-primary btn-sm">+ Thêm mới</a>
  </div>

  @if($authors->isEmpty())
    <div style="text-align:center; padding:48px; color:var(--admin-text-muted)">
      <div style="font-size:48px; margin-bottom:12px">✍️</div>
      <p>Chưa có tác giả nào. <a href="{{ route('admin.authors.create') }}" style="color:var(--admin-primary)">Thêm ngay?</a></p>
    </div>
  @else
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:50px">#</th>
            <th>Avatar</th>
            <th>Tên tác giả</th>
            <th>Slug</th>
            <th>Tiểu sử</th>
            <th style="text-align:center">Số truyện</th>
            <th style="text-align:center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @foreach($authors as $author)
          <tr>
            <td style="color:var(--admin-text-muted); font-size:12px">{{ $author->id }}</td>
            <td>
              @if($author->avatar)
                <img src="{{ asset('storage/' . $author->avatar) }}" alt="{{ $author->name }}" class="avatar-preview" style="width:40px; height:40px" />
              @else
                <div class="avatar-placeholder" style="width:40px; height:40px; font-size:16px">
                  {{ strtoupper(substr($author->name, 0, 1)) }}
                </div>
              @endif
            </td>
            <td>
              <span style="font-weight:600">{{ $author->name }}</span>
            </td>
            <td>
              <code style="background:rgba(255,255,255,0.07); padding:2px 8px; border-radius:5px; font-size:12px; color:var(--admin-text-muted)">{{ $author->slug }}</code>
            </td>
            <td style="color:var(--admin-text-muted); font-size:13px; max-width:200px">
              {{ Str::limit($author->bio, 60) ?: '—' }}
            </td>
            <td style="text-align:center">
              <span class="badge badge-primary">{{ number_format($author->comics_count) }}</span>
            </td>
            <td style="text-align:center">
              <div style="display:flex; gap:6px; justify-content:center">
                <a href="{{ route('admin.authors.edit', $author) }}" class="btn-admin btn-admin-ghost btn-sm">✏️ Sửa</a>
                <button
                  type="button"
                  class="btn-admin btn-admin-danger btn-sm"
                  onclick="confirmDelete('{{ route('admin.authors.destroy', $author) }}', '{{ addslashes($author->name) }}')"
                >🗑️ Xóa</button>
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
