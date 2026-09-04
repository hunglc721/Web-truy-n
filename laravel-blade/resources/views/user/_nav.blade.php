@once
<style>
  :root {
    --card-bg: var(--bg-surface-1);
    --border: var(--border-color);
  }

  @media (max-width: 768px) {
    .user-hub-nav {
      flex-wrap: nowrap !important;
      overflow-x: auto;
      padding-bottom: 6px;
      scrollbar-width: none;
      scroll-snap-type: x proximity;
      -webkit-overflow-scrolling: touch;
    }
    .user-hub-nav::-webkit-scrollbar { display: none; }
    .user-hub-nav > a {
      flex: 0 0 auto;
      min-height: 42px;
      display: inline-flex;
      align-items: center;
      scroll-snap-align: start;
    }
    .user-hub-nav > a:last-child { margin-left: 0 !important; }
  }
</style>
@endonce

<nav class="user-hub-nav" aria-label="Khu vực thành viên" style="display:flex;flex-wrap:wrap;gap:8px;margin:0 0 24px;">
    @php
        $unreadNotifications = auth()->user()?->unreadNotifications()->count() ?? 0;
        $userNav = [
            ['route' => 'user.dashboard', 'label' => '🏠 Tổng quan'],
            ['route' => 'user.library', 'label' => '📚 Tủ truyện'],
            ['route' => 'user.history', 'label' => '🕘 Lịch sử đọc'],
            ['route' => 'user.likes', 'label' => '❤️ Yêu thích'],
            ['route' => 'user.comments', 'label' => '💬 Bình luận'],
            ['route' => 'user.ratings', 'label' => '⭐ Đánh giá'],
            ['route' => 'user.notifications.index', 'label' => '🔔 Thông báo' . ($unreadNotifications ? ' (' . $unreadNotifications . ')' : '')],
            ['route' => 'user.publishingRequests', 'label' => '📝 Đơn đăng truyện'],
        ];
    @endphp

    @foreach($userNav as $item)
        <a href="{{ route($item['route']) }}"
           style="padding:9px 14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;border:1px solid {{ request()->routeIs($item['route']) ? 'var(--primary)' : 'var(--border-color)' }};background:{{ request()->routeIs($item['route']) ? 'rgba(255,94,54,.12)' : 'rgba(255,255,255,.04)' }};color:{{ request()->routeIs($item['route']) ? 'var(--primary)' : 'var(--text-main)' }};">
            {{ $item['label'] }}
        </a>
    @endforeach

    <a href="{{ route('user.statistics.export') }}"
       style="padding:9px 14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;border:1px solid var(--border-color);background:rgba(255,255,255,.04);color:var(--text-main);margin-left:auto;">
        ⬇️ Xuất dữ liệu
    </a>
</nav>