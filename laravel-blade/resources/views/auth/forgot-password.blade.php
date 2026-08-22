@extends('layouts.main')

@section('title', 'Quên Mật Khẩu - WebComics')

@section('content')
<main class="page-container">
  <div class="container" style="max-width: 440px; padding: 40px 16px;">
    <div style="
      background: rgba(19, 22, 30, 0.95);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 32px 28px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.6);
    ">
      <h1 style="font-size: 22px; font-weight: 900; color: #fff; margin: 0 0 8px 0; text-align: center;">🔑 Quên Mật Khẩu?</h1>
      <p style="color: var(--text-sub); font-size: 13px; line-height: 1.6; margin: 0 0 20px 0; text-align: center;">
        Nhập địa chỉ Email đăng ký tài khoản của bạn. Chúng tôi sẽ gửi một liên kết đặt lại mật khẩu an toàn.
      </p>

      @if (session('success'))
        <div style="background: rgba(22, 163, 74, 0.15); border: 1px solid #16a34a; border-radius: 8px; padding: 12px; color: #4ade80; font-size: 13px; font-weight: 700; margin-bottom: 20px;">
          ✓ {{ session('success') }}
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div style="margin-bottom: 20px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Địa chỉ Email <span style="color: #ef4444;">*</span>
          </label>
          <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@example.com" style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 11px 14px;
            font-size: 14px;
            outline: none;
          ">
          @error('email')
            <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
          @enderror
        </div>

        <button type="submit" class="btn-spotlight-read" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 800; border-radius: 8px; cursor: pointer; margin-bottom: 16px;">
          📨 Gửi Liên Kết Đặt Lại Mật Khẩu
        </button>

        <div style="text-align: center; font-size: 13px; color: var(--text-sub);">
          <a href="{{ route('login') }}" style="color: var(--primary); text-decoration: none; font-weight: 700;">
            ← Quay lại Đăng Nhập
          </a>
        </div>
      </form>
    </div>
  </div>
</main>
@endsection
