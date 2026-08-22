@extends('layouts.main')

@section('title', 'Đặt Lại Mật Khẩu Mới - WebComics')

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
      <h1 style="font-size: 22px; font-weight: 900; color: #fff; margin: 0 0 8px 0; text-align: center;">🔐 Đặt Lại Mật Khẩu Mới</h1>
      <p style="color: var(--text-sub); font-size: 13px; line-height: 1.6; margin: 0 0 20px 0; text-align: center;">
        Tạo mật khẩu mới an toàn cho tài khoản của bạn.
      </p>

      <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div style="margin-bottom: 16px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Địa chỉ Email <span style="color: #ef4444;">*</span>
          </label>
          <input type="email" name="email" value="{{ old('email', $email) }}" required readonly style="
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-sub);
            padding: 11px 14px;
            font-size: 14px;
            outline: none;
          ">
          @error('email')
            <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
          @enderror
        </div>

        <div style="margin-bottom: 16px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Mật khẩu mới <span style="color: #ef4444;">*</span>
          </label>
          <input type="password" name="password" required autofocus placeholder="Tối thiểu 6 ký tự" style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 11px 14px;
            font-size: 14px;
            outline: none;
          ">
          @error('password')
            <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
          @enderror
        </div>

        <div style="margin-bottom: 22px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Xác nhận mật khẩu mới <span style="color: #ef4444;">*</span>
          </label>
          <input type="password" name="password_confirmation" required placeholder="Nhập lại mật khẩu mới" style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 11px 14px;
            font-size: 14px;
            outline: none;
          ">
        </div>

        <button type="submit" class="btn-spotlight-read" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 800; border-radius: 8px; cursor: pointer;">
          ✓ Lưu Mật Khẩu Mới & Đăng Nhập
        </button>
      </form>
    </div>
  </div>
</main>
@endsection
