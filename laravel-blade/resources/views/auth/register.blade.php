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

        <button type="submit" class="btn btn-login" style="width: 100%; padding: 13px; font-weight: 800; font-size: 15px; justify-content: center;">
          ✨ Đăng Ký Tài Khoản
        </button>
      </form>

      <div style="border-top: 1px solid var(--border-color); margin-top: 24px; padding-top: 20px; text-align: center; font-size: 13px; color: var(--text-muted);">
        Đã có tài khoản?
        <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 700; text-decoration: none;">
          Đăng nhập ngay
        </a>
      </div>

    </div>

  </div>
</main>
@endsection
