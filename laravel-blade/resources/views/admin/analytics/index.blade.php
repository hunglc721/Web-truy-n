@extends('layouts.admin')

@section('title', 'Thống Kê & Báo Cáo')
@section('breadcrumb', 'Thống kê & Báo cáo')

@push('styles')
<style>
  .analytics-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:14px;margin-bottom:22px}
  .analytics-kpi{background:var(--admin-card);border:1px solid var(--admin-border);border-radius:var(--admin-radius);padding:16px 18px;min-width:0;overflow:hidden}
  .analytics-kpi-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--admin-text-muted);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .analytics-kpi-value{font-size:22px;font-weight:900;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:-.3px;line-height:1.25}
  .analytics-kpi-sub{font-size:11.5px;color:var(--admin-text-muted);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .analytics-two-col{display:grid;grid-template-columns:1.2fr .8fr;gap:20px;margin-bottom:20px}
  .analytics-bars{display:flex;flex-direction:column;gap:12px}
  .analytics-bar-row{display:grid;grid-template-columns:minmax(130px,1fr) 3fr 60px;align-items:center;gap:12px}
  .analytics-bar-track{height:9px;border-radius:999px;background:rgba(255,255,255,.06);overflow:hidden}
  .analytics-bar-fill{height:100%;background:linear-gradient(90deg,#6c63ff,#ff2a6d);border-radius:999px}
  .status-list{display:flex;flex-direction:column;gap:10px}
  .status-item{display:flex;justify-content:space-between;align-items:center;padding:11px 12px;background:rgba(255,255,255,.035);border:1px solid var(--admin-border);border-radius:9px}
  .activity-line{padding:10px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:12.5px}
  .activity-line:last-child{border-bottom:0}
  @media(max-width:950px){.analytics-two-col{grid-template-columns:1fr}.analytics-bar-row{grid-template-columns:120px 1fr 50px}}
</style>
@endpush

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">📊 Thống Kê & Báo Cáo</h1>
  <p class="admin-page-sub">Dữ liệu lấy trực tiếp từ MySQL/SQLite, không còn phụ thuộc localStorage của prototype.</p>
</div>

<div class="analytics-kpis">
  <div class="analytics-kpi"><div class="analytics-kpi-label">📚 Bộ Truyện</div><div class="analytics-kpi-value" title="{{ number_format($stats['comics']) }}">{{ number_format($stats['comics']) }}</div><div class="analytics-kpi-sub">Tổng số bộ trong hệ thống</div></div>
  <div class="analytics-kpi"><div class="analytics-kpi-label">📖 Chapter</div><div class="analytics-kpi-value" title="{{ number_format($stats['chapters']) }}">{{ number_format($stats['chapters']) }}</div><div class="analytics-kpi-sub">Tổng số chương</div></div>
  <div class="analytics-kpi"><div class="analytics-kpi-label">👥 Thành Viên</div><div class="analytics-kpi-value" title="{{ number_format($stats['users']) }}">{{ number_format($stats['users']) }}</div><div class="analytics-kpi-sub">Tài khoản đã tạo</div></div>
  <div class="analytics-kpi"><div class="analytics-kpi-label">👁 Tổng Lượt Xem</div><div class="analytics-kpi-value" title="{{ number_format($stats['views']) }}" style="color:#60a5fa">{{ number_format($stats['views']) }}</div><div class="analytics-kpi-sub">Cộng dồn lượt xem truyện</div></div>
  <div class="analytics-kpi"><div class="analytics-kpi-label">💬 Bình Luận</div><div class="analytics-kpi-value" title="{{ number_format($stats['comments']) }}">{{ number_format($stats['comments']) }}</div><div class="analytics-kpi-sub">Mọi trạng thái</div></div>
  <div class="analytics-kpi"><div class="analytics-kpi-label">⚠️ Báo Cáo</div><div class="analytics-kpi-value" title="{{ number_format($stats['reports']) }}">{{ number_format($stats['reports']) }}</div><div class="analytics-kpi-sub">Báo lỗi từ độc giả</div></div>
  <div class="analytics-kpi"><div class="analytics-kpi-label">🖼️ Banner</div><div class="analytics-kpi-value" title="{{ number_format($stats['banners']) }}">{{ number_format($stats['banners']) }}</div><div class="analytics-kpi-sub">{{ number_format($stats['active_banners']) }} đang hiệu lực</div></div>
</div>

<div class="analytics-two-col">
  <section class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">🏆 Top 10 Truyện Theo Lượt Xem</h2></div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Truyện</th><th>Thể loại</th><th>Chapter</th><th style="text-align:right;">Lượt xem</th></tr></thead>
        <tbody>
          @forelse($topComics as $comic)
            <tr>
              <td style="font-weight:900; color:{{ $loop->iteration <= 3 ? '#fbbf24' : 'var(--admin-text-muted)' }};">#{{ $loop->iteration }}</td>
              <td><a href="{{ route('comics.show', $comic->slug) }}" target="_blank" rel="noopener" style="color:#fff;text-decoration:none;font-weight:700;">{{ $comic->title }}</a></td>
              <td style="color:var(--admin-text-muted);">{{ $comic->genres->pluck('name')->take(2)->join(', ') ?: '—' }}</td>
              <td>{{ number_format($comic->chapters_count) }}</td>
              <td style="text-align:right;font-weight:800;color:#fbbf24;">{{ number_format($comic->views) }}</td>
            </tr>
          @empty
            <tr><td colspan="5" style="text-align:center;color:var(--admin-text-muted);">Chưa có dữ liệu.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">📜 Hoạt Động Gần Nhất</h2></div>
    @forelse($recentActivities as $activity)
      <div class="activity-line">
        <div><strong>{{ $activity->user->name ?? 'Hệ thống' }}</strong> <span style="color:var(--admin-text-muted);">{{ $activity->action }}</span></div>
        <div style="color:var(--admin-text-muted);font-size:11px;margin-top:3px;">{{ $activity->created_at?->diffForHumans() ?? 'Vừa xong' }}</div>
      </div>
    @empty
      <p style="color:var(--admin-text-muted);">Chưa có nhật ký.</p>
    @endforelse
  </section>
</div>

<div class="analytics-two-col">
  <section class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">📖 Số Chapter Theo Truyện</h2></div>
    @php $maxChapters = max(1, (int) ($chapterLeaders->max('chapters_count') ?? 1)); @endphp
    <div class="analytics-bars">
      @forelse($chapterLeaders as $comic)
        <div class="analytics-bar-row">
          <div style="font-size:12.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $comic->title }}">{{ $comic->title }}</div>
          <div class="analytics-bar-track"><div class="analytics-bar-fill" style="width:{{ min(100, ($comic->chapters_count / $maxChapters) * 100) }}%;"></div></div>
          <div style="text-align:right;font-weight:800;">{{ $comic->chapters_count }}</div>
        </div>
      @empty
        <p style="color:var(--admin-text-muted);">Chưa có chapter.</p>
      @endforelse
    </div>
  </section>

  <div style="display:grid;gap:20px;">
    <section class="admin-card">
      <div class="admin-card-header"><h2 class="admin-card-title">⚠️ Tình Trạng Báo Cáo</h2></div>
      <div class="status-list">
        @forelse($reportStatuses as $status => $total)
          <div class="status-item"><span>{{ ucfirst($status) }}</span><strong>{{ number_format($total) }}</strong></div>
        @empty
          <div class="status-item"><span>Chưa có báo cáo</span><strong>0</strong></div>
        @endforelse
      </div>
    </section>

    <section class="admin-card">
      <div class="admin-card-header"><h2 class="admin-card-title">💬 Tình Trạng Bình Luận</h2></div>
      <div class="status-list">
        @forelse($commentStatuses as $status => $total)
          <div class="status-item"><span>{{ ucfirst($status) }}</span><strong>{{ number_format($total) }}</strong></div>
        @empty
          <div class="status-item"><span>Chưa có bình luận</span><strong>0</strong></div>
        @endforelse
      </div>
    </section>
  </div>
</div>
@endsection
