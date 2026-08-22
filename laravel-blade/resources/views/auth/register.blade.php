{{-- resources/views/auth/register.blade.php --}}
@extends('layouts.main')

@section('title', 'Đăng Ký Tài Khoản — WebComics')

@section('content')
<main class="page-container" style="padding: 60px 0; min-height: 75vh; display: flex; align-items: center; justify-content: center;">
  <div class="container" style="max-width: 460px;">

    <div style="
      background: var(--bg-surface-1);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 36px 32px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    ">
      <div style="text-align: center; margin-bottom: 24px;">
        <div style="
          width: 54px; height: 54px; border-radius: 14px;
          background: linear-gradient(135deg, #FF5E36, #FF2A6D);
          display: flex; align-items: center; justify-content: center;
          font-weight: 900; font-size: 22px; color: #fff; margin: 0 auto 12px;
        ">WC</div>
        <h1 style="font-size: 22px; font-weight: 900; color: var(--text-main); margin-bottom: 4px;">Đăng Ký Tài Khoản</h1>
        <p style="font-size: 13px; color: var(--text-muted);">Tạo tài khoản mới để trải nghiệm đầy đủ tính năng tủ sách</p>
      </div>

      @if($errors->any())
        <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #ef4444; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 13px;">
          <ul style="margin: 0; padding-left: 18px;">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('register') }}" method="POST" novalidate>
        @csrf

        {{-- Họ và tên --}}
        <div style="margin-bottom: 18px;">
          <label style="display: block; font-weight: 700; font-size: 13px; color: var(--text-main); margin-bottom: 6px;">
            Họ và Tên <span style="color: var(--primary);">*</span>
          </label>
          <input
            type="text" name="name" value="{{ old('name') }}" required autofocus
            placeholder="Ví dụ: Nguyễn Văn A"
            style="width: 100%; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 14px; color: var(--text-main); font-size: 14px; outline: none;"
          />
        </div>

        {{-- Email --}}
        <div style="margin-bottom: 18px;">
          <label style="display: block; font-weight: 700; font-size: 13px; color: var(--text-main); margin-bottom: 6px;">
            Địa chỉ Email <span style="color: var(--primary);">*</span>
          </label>
          <input
            type="email" name="email" value="{{ old('email') }}" required
            placeholder="email@example.com"
            style="width: 100%; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 14px; color: var(--text-main); font-size: 14px; outline: none;"
          />
        </div>

        {{-- Mật khẩu --}}
        <div style="margin-bottom: 18px;">
          <label style="display: block; font-weight: 700; font-size: 13px; color: var(--text-main); margin-bottom: 6px;">
            Mật khẩu <span style="color: var(--primary);">*</span>
          </label>
          <input
            type="password" name="password" required
            placeholder="Tối thiểu 6 ký tự"
            style="width: 100%; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 14px; color: var(--text-main); font-size: 14px; outline: none;"
          />
        </div>

        {{-- Nhập lại mật khẩu --}}
        <div style="margin-bottom: 24px;">
          <label style="display: block; font-weight: 700; font-size: 13px; color: var(--text-main); margin-bottom: 6px;">
            Xác nhận Mật khẩu <span style="color: var(--primary);">*</span>
          </label>
          <input
            type="password" name="password_confirmation" required
            placeholder="Nhập lại mật khẩu ở trên"
            style="width: 100%; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 14px; color: var(--text-main); font-size: 14px; outline: none;"
          />
        </div>

        <button type="submit" class="btn btn-login" style="width: 100%; padding: 13px; font-weight: 800; font-size: 15px; justify-content: center; margin-bottom: 20px;">
          ✨ Đăng Ký Tài Khoản
        </button>
      </form>

      <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <div style="flex:1;height:1px;background:var(--border-color);"></div>
        <span style="font-size:12px;color:var(--text-muted);">HOẶC ĐĂNG KÝ VỚI</span>
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

      <div style="border-top: 1px solid var(--border-color); margin-top: 20px; padding-top: 20px; text-align: center; font-size: 13px; color: var(--text-muted);">
        Đã có tài khoản?
        <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 700; text-decoration: none;">
          Đăng nhập ngay
        </a>
      </div>

    </div>

  </div>
</main>
@endsection
