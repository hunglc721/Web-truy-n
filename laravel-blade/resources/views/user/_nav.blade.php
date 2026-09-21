@php
  $unreadNotifications = auth()->user()?->unreadNotifications()->count() ?? 0;
  $userNav = [
    ['route' => 'user.dashboard', 'label' => 'Tổng quan'],
    ['route' => 'user.library', 'label' => 'Tủ truyện'],
    ['route' => 'user.history', 'label' => 'Lịch sử'],
    ['route' => 'user.likes', 'label' => 'Yêu thích'],
    ['route' => 'user.comments', 'label' => 'Bình luận'],
    ['route' => 'user.ratings', 'label' => 'Đánh giá'],
    ['route' => 'user.notifications.index', 'label' => 'Thông báo', 'count' => $unreadNotifications],
    ['route' => 'user.publishingRequests', 'label' => 'Đơn đăng truyện'],
  ];
@endphp

<nav class="user-hub-nav" aria-label="Khu vực thành viên">
  <div class="user-hub-links">
    @foreach($userNav as $item)
      @php($active = request()->routeIs($item['route']))
      <a href="{{ route($item['route']) }}" class="user-hub-link {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
        <span>{{ $item['label'] }}</span>
        @if(($item['count'] ?? 0) > 0)<span class="user-nav-count">{{ $item['count'] }}</span>@endif
      </a>
    @endforeach
  </div>
  <a href="{{ route('user.statistics.export') }}" class="user-export-link">Xuất dữ liệu</a>
</nav>
