@extends('layouts.admin')

@section('title', 'Quản Lý Đơn Đăng Ký Đăng Truyện')
@section('breadcrumb', 'Đơn Đăng Truyện')

@push('styles')
<style>
  .req-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
  }
  .req-filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 20px;
  }
  .req-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  .req-tab-btn {
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    color: var(--admin-text-muted);
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--admin-border);
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .req-tab-btn:hover {
    color: var(--admin-text);
    background: rgba(255,255,255,0.08);
  }
  .req-tab-btn.active {
    background: rgba(108,99,255,0.18);
    color: var(--admin-primary);
    border-color: rgba(108,99,255,0.4);
  }
  .req-tab-count {
    padding: 1px 6px;
    border-radius: 999px;
    font-size: 10.5px;
    background: rgba(255,255,255,0.1);
  }
  .req-tab-btn.active .req-tab-count {
    background: var(--admin-primary);
    color: #fff;
  }
  .req-search-form {
    display: flex;
    align-items: center;
    gap: 8px;
  }
</style>
@endpush

@section('content')
<div class="ph">
  <h1>📥 Quản Lý Đơn Đăng Ký Đăng Truyện</h1>
  <p>Tiếp nhận thông tin tác giả, thẩm định bản thảo tác phẩm và phê duyệt xuất bản truyện mới.</p>
</div>

{{-- KPI Stats --}}
<div class="req-stats-grid">
  <div class="admin-stat-card">
    <div class="admin-stat-label">Tổng số đơn</div>
    <div class="admin-stat-value">{{ number_format($stats['total']) }}</div>
  </div>
  <div class="admin-stat-card" style="border-left: 3px solid #f59e0b;">
    <div class="admin-stat-label">⏳ Chờ duyệt</div>
    <div class="admin-stat-value" style="color: #f59e0b;">{{ number_format($stats['pending']) }}</div>
  </div>
  <div class="admin-stat-card" style="border-left: 3px solid #3b82f6;">
    <div class="admin-stat-label">🔍 Đang thẩm định</div>
    <div class="admin-stat-value" style="color: #3b82f6;">{{ number_format($stats['reviewing']) }}</div>
  </div>
  <div class="admin-stat-card" style="border-left: 3px solid #22c55e;">
    <div class="admin-stat-label">✅ Đã phê duyệt</div>
    <div class="admin-stat-value" style="color: #22c55e;">{{ number_format($stats['approved']) }}</div>
  </div>
  <div class="admin-stat-card" style="border-left: 3px solid #ef4444;">
    <div class="admin-stat-label">❌ Từ chối</div>
    <div class="admin-stat-value" style="color: #ef4444;">{{ number_format($stats['rejected']) }}</div>
  </div>
</div>

<div class="admin-card">
  {{-- Filter Bar --}}
  <div class="req-filter-bar">
    <div class="req-tabs">
      <a href="{{ route('admin.storyRequests.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" class="req-tab-btn {{ $statusFilter === 'all' ? 'active' : '' }}">
        <span>Tất cả</span>
        <span class="req-tab-count">{{ $stats['total'] }}</span>
      </a>
      <a href="{{ route('admin.storyRequests.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}" class="req-tab-btn {{ $statusFilter === 'pending' ? 'active' : '' }}">
        <span>⏳ Chờ duyệt</span>
        <span class="req-tab-count">{{ $stats['pending'] }}</span>
      </a>
      <a href="{{ route('admin.storyRequests.index', array_merge(request()->except('status', 'page'), ['status' => 'reviewing'])) }}" class="req-tab-btn {{ $statusFilter === 'reviewing' ? 'active' : '' }}">
        <span>🔍 Đang thẩm định</span>
        <span class="req-tab-count">{{ $stats['reviewing'] }}</span>
      </a>
      <a href="{{ route('admin.storyRequests.index', array_merge(request()->except('status', 'page'), ['status' => 'approved'])) }}" class="req-tab-btn {{ $statusFilter === 'approved' ? 'active' : '' }}">
        <span>✅ Đã duyệt</span>
        <span class="req-tab-count">{{ $stats['approved'] }}</span>
      </a>
      <a href="{{ route('admin.storyRequests.index', array_merge(request()->except('status', 'page'), ['status' => 'rejected'])) }}" class="req-tab-btn {{ $statusFilter === 'rejected' ? 'active' : '' }}">
        <span>❌ Từ chối</span>
        <span class="req-tab-count">{{ $stats['rejected'] }}</span>
      </a>
    </div>

    <form method="GET" action="{{ route('admin.storyRequests.index') }}" class="req-search-form">
      <input type="hidden" name="status" value="{{ $statusFilter }}" />
      <select name="type" class="form-control" style="width: auto; padding: 7px 10px; font-size: 12.5px;" onchange="this.form.submit()">
        <option value="all">Tất cả loại truyện</option>
        <option value="original" {{ $typeFilter === 'original' ? 'selected' : '' }}>Sáng tác</option>
        <option value="translation" {{ $typeFilter === 'translation' ? 'selected' : '' }}>Truyện dịch</option>
        <option value="novel" {{ $typeFilter === 'novel' ? 'selected' : '' }}>Tiểu thuyết</option>
      </select>
      <input type="text" name="search" class="form-control" style="width: 220px; padding: 7px 12px; font-size: 12.5px;" placeholder="Tìm tên truyện, tác giả, email..." value="{{ $search }}" />
      <button type="submit" class="btn-admin btn-admin-primary btn-sm">Tìm</button>
      @if($search || $typeFilter !== 'all')
        <a href="{{ route('admin.storyRequests.index', ['status' => $statusFilter]) }}" class="btn-admin btn-admin-ghost btn-sm">Xóa lọc</a>
      @endif
    </form>
  </div>

  {{-- Table --}}
  <div style="overflow-x: auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 50px;">ID</th>
          <th style="width: 240px;">Người gửi & Liên hệ</th>
          <th>Tác phẩm muốn đăng</th>
          <th style="width: 140px;">Loại truyện</th>
          <th style="width: 130px;">Trạng thái</th>
          <th style="width: 130px;">Ngày gửi</th>
          <th style="width: 150px; text-align: right;">Hành động</th>
        </tr>
      </thead>
      <tbody>
        @forelse($storyRequests as $item)
          <tr>
            <td style="font-weight: 700; color: var(--admin-text-muted);">#{{ $item->id }}</td>
            <td>
              <div style="font-weight: 700; color: var(--admin-text); font-size: 13.5px;">{{ $item->creator_name }}</div>
              <div style="font-size: 12px; color: var(--admin-text-muted); margin-top: 2px;">
                ✉️ {{ $item->email }}
              </div>
              @if($item->phone_or_social)
                <div style="font-size: 11.5px; color: var(--admin-primary); margin-top: 1px;">
                  📞 {{ $item->phone_or_social }}
                </div>
              @endif
              @if($item->team_name)
                <div style="font-size: 11px; color: var(--admin-warning); margin-top: 1px;">
                  👥 {{ $item->team_name }}
                </div>
              @endif
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 10px;">
                @if($item->cover_image_path)
                  <img src="{{ asset('storage/' . $item->cover_image_path) }}" alt="Bìa" style="width: 36px; height: 48px; object-fit: cover; border-radius: 4px; flex-shrink: 0;" />
                @endif
                <div>
                  <a href="{{ route('admin.storyRequests.show', $item->id) }}" style="font-weight: 700; color: var(--admin-text); text-decoration: none; font-size: 14px;">
                    {{ $item->story_title }}
                  </a>
                  @if($item->story_original_title)
                    <div style="font-size: 12px; color: var(--admin-text-muted);">({{ $item->story_original_title }})</div>
                  @endif
                  <div style="font-size: 12px; color: var(--admin-text-muted); margin-top: 3px; max-width: 380px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $item->summary }}
                  </div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge badge-muted" style="font-size: 11px;">
                {{ $item->story_type_label }}
              </span>
            </td>
            <td>
              <span class="badge {{ $item->status_badge_class }}">
                @if($item->status === 'pending') ⏳ @elseif($item->status === 'reviewing') 🔍 @elseif($item->status === 'approved') ✅ @else ❌ @endif
                {{ $item->status_label }}
              </span>
            </td>
            <td style="font-size: 12px; color: var(--admin-text-muted);">
              {{ $item->created_at->format('d/m/Y') }}
              <div style="font-size: 11px;">{{ $item->created_at->format('H:i') }}</div>
            </td>
            <td style="text-align: right; white-space: nowrap;">
              <a href="{{ route('admin.storyRequests.show', $item->id) }}" class="btn-admin btn-admin-primary btn-sm" title="Xem chi tiết & thẩm định">
                🔍 Thẩm định
              </a>
              <button type="button" class="btn-admin btn-admin-danger btn-sm" onclick="confirmDelete('{{ route('admin.storyRequests.destroy', $item->id) }}', 'Đơn đăng ký #{{ $item->id }} ({{ $item->story_title }})')" title="Xóa đơn">
                🗑️
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; padding: 36px; color: var(--admin-text-muted);">
              Không tìm thấy đơn đăng ký đăng truyện nào phù hợp với bộ lọc.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="pagination-wrap">
    {{ $storyRequests->links() }}
  </div>
</div>
@endsection
