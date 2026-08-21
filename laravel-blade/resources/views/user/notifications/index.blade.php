@extends('layouts.main')

@section('title', 'Thông báo - WebComics')

@push('styles')
<style>
  .notification-page{padding:34px 0 60px}.notification-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:20px}.notification-list{display:flex;flex-direction:column;gap:12px}.notification-item{display:flex;gap:14px;align-items:flex-start;padding:16px 18px;border:1px solid var(--border-color);background:var(--bg-surface-1);border-radius:14px}.notification-item.unread{border-color:rgba(255,94,54,.45);background:rgba(255,94,54,.06)}.notification-icon{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:var(--bg-surface-2);font-size:20px;flex-shrink:0}.notification-body{flex:1;min-width:0}.notification-title{font-weight:800;color:var(--text-main);margin-bottom:4px}.notification-message{font-size:13.5px;color:var(--text-sub);line-height:1.55}.notification-time{font-size:11.5px;color:var(--text-muted);margin-top:7px}.notification-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}.notification-actions a,.notification-actions button{border:1px solid var(--border-color);background:var(--bg-surface-2);color:var(--text-main);padding:7px 11px;border-radius:8px;text-decoration:none;cursor:pointer;font:inherit;font-size:12px}.empty-notification{text-align:center;padding:48px 20px;border:1px dashed var(--border-color);border-radius:14px;color:var(--text-muted)}@media(max-width:640px){.notification-head{align-items:flex-start;flex-direction:column}.notification-item{padding:14px}.notification-icon{width:36px;height:36px}}
</style>
@endpush

@section('content')
<main class="notification-page">
  <div class="container">
    @include('user._nav')

    <div class="notification-head">
      <div>
        <h1 style="font-size:26px;font-weight:900;color:var(--text-main)">🔔 Thông báo</h1>
        <p style="color:var(--text-sub);margin-top:4px">Chapter mới, thông báo hệ thống và cảnh báo dành cho tài khoản của bạn.</p>
      </div>
      @if(auth()->user()->unreadNotifications()->exists())
      <form method="POST" action="{{ route('user.notifications.readAll') }}">
        @csrf @method('PATCH')
        <button class="btn btn-login" type="submit">Đánh dấu tất cả đã đọc</button>
      </form>
      @endif
    </div>

    <div class="notification-list">
      @forelse($notifications as $notification)
        @php($data = $notification->data)
        <article class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
          <div class="notification-icon">{{ $data['icon'] ?? '🔔' }}</div>
          <div class="notification-body">
            <div class="notification-title">{{ $data['title'] ?? 'Thông báo' }}</div>
            <div class="notification-message">{{ $data['message'] ?? '' }}</div>
            <div class="notification-time">{{ $notification->created_at?->diffForHumans() }}</div>
            <div class="notification-actions">
              <a href="{{ route('user.notifications.open', $notification->id) }}">Mở</a>
              <form method="POST" action="{{ route('user.notifications.destroy', $notification->id) }}">
                @csrf @method('DELETE')
                <button type="submit">Xóa</button>
              </form>
            </div>
          </div>
        </article>
      @empty
        <div class="empty-notification">Chưa có thông báo nào. Một khoảnh khắc hiếm hoi internet chịu im lặng.</div>
      @endforelse
    </div>

    <div style="margin-top:20px">{{ $notifications->links() }}</div>
  </div>
</main>
@endsection
