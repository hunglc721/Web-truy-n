@extends('layouts.main')

@section('title', 'Đăng Nhập - WebComics')

@push('styles')
<style>
  .auth-card{background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:36px 32px;box-shadow:0 18px 50px rgba(0,0,0,.42)}
  .auth-logo{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#FF5E36,#FF2A6D);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:22px;color:#fff;margin:0 auto 12px;box-shadow:0 8px 24px rgba(255,94,54,.3)}
  .auth-field{margin-bottom:18px}.auth-label{display:block;font-weight:700;font-size:13px;color:var(--text-main);margin-bottom:7px}.auth-input{width:100%;background:var(--bg-surface-2);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:12px 14px;color:var(--text-main);font-size:14px;outline:none;transition:.2s}.auth-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(255,94,54,.16)}
  .auth-pw-wrap{position:relative}.auth-pw-wrap .auth-input{padding-right:48px}.auth-pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:transparent;border:0;color:var(--text-muted);cursor:pointer;font-size:16px;padding:6px}
  .auth-role-note{margin:0 0 22px;padding:12px 14px;border-radius:10px;background:rgba(108,99,255,.1);border:1px solid rgba(108,99,255,.22);color:var(--text-sub);font-size:12.5px;line-height:1.6}.auth-role-note strong{color:#a5b4fc}
</style>
@endpush

@section('content')
<main class="page-container" style="padding:60px 0;min-height:75vh;display:flex;align-items:center;justify-content:center;">
  <div class="container" style="max-width:460px;">
    <div class="auth-card">
      <div style="text-align:center;margin-bottom:22px;">
        <div class="auth-logo">WC</div>
        <h1 style="font-size:22px;font-weight:900;color:var(--text-main);margin-bottom:5px;">Đăng Nhập WebComics</h1>
        <p style="font-size:13px;color:var(--text-muted);">Một tài khoản, một form đăng nhập cho cả độc giả và quản trị viên.</p>
      </div>

      <div class="auth-role-note">
        <strong>Không cần chọn role.</strong> Laravel tự kiểm tra tài khoản sau khi đăng nhập. Member được đưa về Tủ Truyện; tài khoản có <code>is_admin = true</code> được đưa tới Admin Dashboard.
      </div>

      @if($errors->any())
        <div style="background:rgba(239,68,68,.13);border:1px solid rgba(239,68,68,.35);color:#f87171;padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:13px;">
          <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif

      <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="auth-field">
          <label for="login-email" class="auth-label">Địa chỉ Email <span style="color:var(--primary);">*</span></label>
          <input id="login-email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="email@example.com" />
        </div>

        <div class="auth-field">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
            <label for="login-password" class="auth-label" style="margin-bottom:0;">Mật khẩu <span style="color:var(--primary);">*</span></label>
            <a href="{{ route('password.request') }}" style="color:var(--text-sub);font-size:12px;text-decoration:none;">Quên mật khẩu?</a>
          </div>
          <div class="auth-pw-wrap">
            <input id="login-password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <button type="button" class="auth-pw-toggle" id="toggle-password" aria-label="Hiện hoặc ẩn mật khẩu">👁</button>
          </div>
        </div>

        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--text-muted);font-size:13px;margin-bottom:20px;">
          <input type="checkbox" name="remember" value="1" style="width:16px;height:16px;accent-color:var(--primary);" />
          <span>Ghi nhớ đăng nhập</span>
        </label>

        <button type="submit" class="btn btn-login" style="width:100%;padding:13px;font-weight:800;font-size:15px;justify-content:center;margin-bottom:20px;">🔐 Đăng Nhập</button>
      </form>

      <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <div style="flex:1;height:1px;background:var(--border-color);"></div>
        <span style="font-size:12px;color:var(--text-muted);">HOẶC TIẾP TỤC VỚI</span>
        <div style="flex:1;height:1px;background:var(--border-color);"></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;">
        <a href="{{ route('auth.social.redirect', 'google') }}" style="text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;background:rgba(255,255,255,0.06);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:10px;color:#fff;font-size:13px;font-weight:700;transition:0.2s;">
          <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.6h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.9z"/><path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 12.3 0 15.2s.7 5.5 1.9 7.9l3.7-2.9z"/><path fill="#34A853" d="M12 23.5c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.4-6.4-5.2L1.9 16.5C3.7 20.2 7.5 23.5 12 23.5z"/></svg>
          Google
        </a>
        <a href="{{ route('auth.social.redirect', 'facebook') }}" style="text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;background:rgba(255,255,255,0.06);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:10px;color:#fff;font-size:13px;font-weight:700;transition:0.2s;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          Facebook
        </a>
      </div>

      <div style="border-top:1px solid var(--border-color);margin-top:20px;padding-top:20px;text-align:center;font-size:13px;color:var(--text-muted);">
        Chưa có tài khoản? <a href="{{ route('register') }}" style="color:var(--primary);font-weight:700;text-decoration:none;">Đăng ký ngay</a>
      </div>
    </div>
  </div>
</main>
@endsection

@push('scripts')
<script>
  document.getElementById('toggle-password')?.addEventListener('click', function () {
    const input = document.getElementById('login-password');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    this.textContent = showing ? '👁' : '🙈';
  });
</script>
@endpush
