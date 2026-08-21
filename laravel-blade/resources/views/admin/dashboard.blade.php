@extends('layouts.admin')

@section('title', 'Bảng Điều Khiển Tổng Quan')
@section('breadcrumb', 'Dashboard')

@push('styles')
<style>
  .dash-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
  }
  .stat-card {
    background: #1a1d27;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 18px 16px;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
  }
  .stat-card:hover {
    border-color: rgba(108,99,255,0.4);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
  }
  .stat-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--admin-text-muted);
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .stat-value {
    font-size: 26px;
    font-weight: 900;
    color: #fff;
  }
  .quick-modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
    margin-bottom: 26px;
  }
  .module-card {
    background: #1a1d27;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    text-decoration: none;
    transition: all 0.2s ease;
  }
  .module-card:hover {
    background: rgba(108,99,255,0.08);
    border-color: rgba(108,99,255,0.35);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
  }
  .module-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
  }
  .module-title {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 2px;
  }
  .module-desc {
    font-size: 12px;
    color: var(--admin-text-muted);
  }
  .dash-layout-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
  }
  @media(min-width: 1024px) {
    .dash-layout-grid {
      grid-template-columns: 7fr 5fr;
    }
  }
  .activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
  }
  .activity-item:last-child {
    border-bottom: none;
  }
</style>
@endpush

@section('content')
<div class="ph" style="margin-bottom: 22px;">
  <h1>📊 Bảng Điều Khiển Tổng Quan</h1>
  <p>Thống kê hệ thống, theo dõi hoạt động thời gian thực và điều hướng nhanh đến các khu vực quản trị.</p>
</div>

{{-- 1. THỐNG KÊ NHANH (METRICS) --}}
<div class="dash-stats-grid">
  <div class="stat-card">
    <div class="stat-label">
      <span>📚 Tổng Bộ Truyện</span>
      <span style="font-size: 16px;">📖</span>
    </div>
    <div class="stat-value" style="color: #818cf8;">{{ number_format($stats['total_comics']) }}</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">
      <span>📑 Tổng Số Chapter</span>
      <span style="font-size: 16px;">📄</span>
    </div>
    <div class="stat-value" style="color: #38bdf8;">{{ number_format($stats['total_chapters']) }}</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">
      <span>👥 Tổng Thành Viên</span>
      <span style="font-size: 16px;">👤</span>
    </div>
    <div class="stat-value" style="color: #34d399;">{{ number_format($stats['total_users']) }}</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">
      <span>👁️ Tổng Lượt Xem</span>
      <span style="font-size: 16px;">🔥</span>
    </div>
    <div class="stat-value" style="color: #fbbf24;">{{ number_format($stats['total_views']) }}</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">
      <span>💬 Bình Luận Chờ Duyệt</span>
      @if($stats['pending_comments'] > 0)
        <span style="background: rgba(245,158,11,0.2); color: #fbbf24; font-size: 10px; padding: 2px 6px; border-radius: 6px; font-weight: 800;">CẦN XỬ LÝ</span>
      @endif
    </div>
    <div class="stat-value" style="color: {{ $stats['pending_comments'] > 0 ? '#f59e0b' : '#94a3b8' }};">
      {{ number_format($stats['pending_comments']) }}
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-label">
      <span>⚠️ Báo Lỗi Chapter</span>
      @if($stats['pending_reports'] > 0)
        <span style="background: rgba(239,68,68,0.2); color: #f87171; font-size: 10px; padding: 2px 6px; border-radius: 6px; font-weight: 800;">MỚI</span>
      @endif
    </div>
    <div class="stat-value" style="color: {{ $stats['pending_reports'] > 0 ? '#ef4444' : '#94a3b8' }};">
      {{ number_format($stats['pending_reports']) }}
    </div>
  </div>
</div>

{{-- 2. ĐIỀU HƯỚNG NHANH CÁC CHỨC NĂNG (QUICK ACTIONS) --}}
<div class="quick-modules-grid">
  <a href="{{ route('admin.comics.index') }}" class="module-card">
    <div class="module-icon" style="background: rgba(99,102,241,0.15); color: #818cf8;">📚</div>
    <div>
      <div class="module-title">Quản Lý Truyện & Tập</div>
      <div class="module-desc">Thêm truyện, upload ảnh chapter, sửa xóa</div>
    </div>
  </a>

  <a href="{{ route('admin.comments.index') }}" class="module-card">
    <div class="module-icon" style="background: rgba(245,158,11,0.15); color: #fbbf24;">💬</div>
    <div>
      <div class="module-title">Kiểm Duyệt Bình Luận</div>
      <div class="module-desc">Duyệt, ẩn, xóa spam và ban tác giả vi phạm</div>
    </div>
  </a>

  <a href="{{ route('admin.reports.index') }}" class="module-card">
    <div class="module-icon" style="background: rgba(239,68,68,0.15); color: #f87171;">⚠️</div>
    <div>
      <div class="module-title">Trung Tâm Báo Cáo Lỗi</div>
      <div class="module-desc">Xử lý báo cáo ảnh hỏng từ reader trực tiếp</div>
    </div>
  </a>

  <a href="{{ route('admin.schedules.index') }}" class="module-card">
    <div class="module-icon" style="background: rgba(16,185,129,0.15); color: #34d399;">📅</div>
    <div>
      <div class="module-title">Lịch Phát Sóng Tuần</div>
      <div class="module-desc">Gán lịch 7 ngày, hẹn giờ tự động publish</div>
    </div>
  </a>

  <a href="{{ route('admin.banners.index') }}" class="module-card">
    <div class="module-icon" style="background: rgba(168,85,247,0.15); color: #c084fc;">🖼️</div>
    <div>
      <div class="module-title">Banner Slider Trang Chủ</div>
      <div class="module-desc">Cấu hình banner, hẹn giờ bắt đầu/kết thúc</div>
    </div>
  </a>

  <a href="{{ route('admin.users.index') }}" class="module-card">
    <div class="module-icon" style="background: rgba(59,130,246,0.15); color: #60a5fa;">👥</div>
    <div>
      <div class="module-title">Quản Lý Thành Viên</div>
      <div class="module-desc">Tra cứu tài khoản, phân quyền, khóa nick</div>
    </div>
  </a>
</div>

{{-- 3. BẢNG TOP TRUYỆN & HOẠT ĐỘNG GẦN ĐÂY --}}
<div class="dash-layout-grid">
  
  {{-- Cột trái: Top truyện nhiều lượt xem nhất --}}
  <div style="display: flex; flex-direction: column; gap: 20px;">
    <div class="admin-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h3 style="font-size: 15px; font-weight: 800; color: #fff; margin: 0;">🔥 Top Truyện Xem Nhiều Nhất</h3>
        <a href="{{ route('admin.comics.index') }}" style="font-size: 12.5px; color: var(--admin-primary); text-decoration: none; font-weight: 600;">Xem tất cả &rarr;</a>
      </div>

      <div style="overflow-x: auto;">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 50px;">Bìa</th>
              <th>Tên truyện</th>
              <th>Thể loại</th>
              <th>Tập mới</th>
              <th style="text-align: right;">Lượt xem</th>
            </tr>
          </thead>
          <tbody>
            @forelse($topComics as $comic)
              <tr>
                <td>
                  <img src="{{ $comic->cover_image }}" alt="" style="width: 34px; height: 46px; object-fit: cover; border-radius: 4px;" />
                </td>
                <td>
                  <a href="{{ route('comics.show', $comic->slug) }}" target="_blank" style="color: #fff; font-weight: 700; text-decoration: none;" class="hover:underline">
                    {{ $comic->title }}
                  </a>
                </td>
                <td>
                  <span style="font-size: 12px; color: var(--admin-text-muted);">
                    {{ $comic->genres->pluck('name')->take(2)->join(', ') ?: '—' }}
                  </span>
                </td>
                <td>
                  <span style="font-size: 12px; font-weight: 600; color: #818cf8;">
                    {{ $comic->latestChapter ? $comic->latestChapter->label : 'Chưa có' }}
                  </span>
                </td>
                <td style="text-align: right; font-weight: 800; color: #fbbf24;">
                  {{ number_format($comic->views) }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="text-align: center; color: var(--admin-text-muted); padding: 20px;">Chưa có dữ liệu truyện.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Cột phải: Nhật ký hoạt động gần đây (Recent Audit Logs) --}}
  <div>
    <div class="admin-card" style="height: 100%;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h3 style="font-size: 15px; font-weight: 800; color: #fff; margin: 0;">📜 Hoạt Động Quản Trị Gần Đây</h3>
        <span style="font-size: 11px; color: var(--admin-text-muted);">Tự động ghi nhận</span>
      </div>

      <div style="display: flex; flex-direction: column;">
        @forelse($recentActivities as $act)
          <div class="activity-item">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(99,102,241,0.15); color: #818cf8; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">
              {{ strtoupper(substr($act->user->name ?? 'A', 0, 1)) }}
            </div>
            <div style="flex: 1; min-width: 0;">
              <div style="font-size: 12.5px; color: #e2e8f0;">
                <strong style="color: #fff;">{{ $act->user->name ?? 'Hệ thống' }}</strong>
                <span style="color: var(--admin-text-muted);">{{ $act->action }}</span>
              </div>
              <div style="font-size: 11px; color: var(--admin-text-muted); margin-top: 2px;">
                {{ $act->created_at ? $act->created_at->diffForHumans() : 'Vừa xong' }}
              </div>
            </div>
          </div>
        @empty
          <div style="text-align: center; padding: 30px 10px; color: var(--admin-text-muted); font-size: 13px; font-style: italic;">
            Chưa có hoạt động quản trị nào được ghi lại.
          </div>
        @endforelse
      </div>
    </div>
  </div>

</div>
@endsection
