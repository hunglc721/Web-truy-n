@extends('layouts.main')

@section('title', 'Cài Đặt Xác Thực 2 Bước (2FA) - WebComics')

@section('content')
<main class="page-container">
  <div class="container" style="max-width: 620px; padding: 40px 16px;">
    <div style="
      background: rgba(19, 22, 30, 0.95);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 32px 28px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.6);
    ">
      <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
        <span style="font-size: 32px;">🛡️</span>
        <h1 style="font-size: 22px; font-weight: 900; color: #fff; margin: 0;">Xác Thực 2 Bước (Two-Factor Authentication)</h1>
      </div>

      <p style="color: var(--text-sub); font-size: 13.5px; line-height: 1.6; margin: 0 0 24px 0;">
        Bảo vệ tài khoản của bạn bằng lớp bảo mật bổ sung sử dụng mã OTP TOTP từ ứng dụng như Google Authenticator, Authy hoặc 1Password.
      </p>

      @if (session('success'))
        <div style="background: rgba(22, 163, 74, 0.15); border: 1px solid #16a34a; border-radius: 8px; padding: 14px; color: #4ade80; font-size: 13px; font-weight: 700; margin-bottom: 20px;">
          ✓ {{ session('success') }}
        </div>
      @endif

      @if (session('recovery_codes'))
        <div style="background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; border-radius: 10px; padding: 18px; color: #fde047; font-size: 13px; margin-bottom: 24px;">
          <h3 style="color: #fff; margin: 0 0 8px 0; font-size: 15px; font-weight: 800;">⚠️ Mã Khôi Phục Dự Phòng (LƯU LẠI NGAY)</h3>
          <p style="margin: 0 0 12px 0; color: var(--text-sub);">
            Nếu bạn làm mất điện thoại hoặc không thể truy cập ứng dụng Authenticator, bạn có thể dùng một trong các mã dưới đây để đăng nhập. Mỗi mã chỉ dùng được 1 lần:
          </p>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-family: monospace; font-size: 13px; color: #fff; background: rgba(0,0,0,0.4); padding: 12px; border-radius: 6px;">
            @foreach (session('recovery_codes') as $code)
              <div>{{ $code }}</div>
            @endforeach
          </div>
        </div>
      @endif

      @if ($has2FA)
        <div style="background: rgba(22, 163, 74, 0.1); border: 1px solid rgba(22, 163, 74, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
          <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
            <span style="color: #4ade80; font-size: 18px;">✓</span>
            <strong style="color: #fff; font-size: 15px;">2FA Đang Được Kích Hoạt</strong>
          </div>
          <p style="color: var(--text-sub); font-size: 13px; margin: 0;">
            Tài khoản của bạn hiện được bảo vệ với xác thực 2 yếu tố. Bạn sẽ được yêu cầu mã xác thực mỗi khi đăng nhập.
          </p>
        </div>

        <form method="POST" action="{{ route('2fa.disable') }}">
          @csrf
          <h3 style="font-size: 15px; color: #fff; margin: 0 0 8px 0;">Tắt xác thực 2 bước</h3>
          <p style="color: var(--text-sub); font-size: 13px; margin: 0 0 12px 0;">
            Nhập mật khẩu hiện tại của bạn để tắt bảo mật 2FA:
          </p>
          <div style="display: flex; gap: 10px; align-items: flex-start;">
            <div style="flex: 1;">
              <input type="password" name="password" required placeholder="Mật khẩu của bạn" style="
                width: 100%;
                background: rgba(255,255,255,0.06);
                border: 1px solid var(--border);
                border-radius: 8px;
                color: #fff;
                padding: 10px 14px;
                font-size: 14px;
                outline: none;
              ">
              @error('password')
                <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
              @enderror
            </div>
            <button type="submit" class="btn-spotlight-sub" style="color: #ef4444; border-color: #ef4444; padding: 10px 18px; font-weight: 700; cursor: pointer;">
              Tắt 2FA
            </button>
          </div>
        </form>
      @else
        <div style="margin-bottom: 24px;">
          <h3 style="font-size: 15px; color: #fff; margin: 0 0 10px 0;">Bước 1: Quét mã QR vào ứng dụng Authenticator</h3>
          
          <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; padding: 16px;">
            @if ($qrCodeUrl)
              <div style="background: #fff; padding: 8px; border-radius: 8px; width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                <img src="{{ $qrCodeUrl }}" alt="2FA QR Code" style="width: 100%; height: 100%;">
              </div>
            @endif

            <div style="flex: 1; min-width: 200px;">
              <p style="color: var(--text-sub); font-size: 13px; margin: 0 0 8px 0;">Hoặc nhập mã khóa bí mật thủ công:</p>
              <div style="font-family: monospace; font-size: 15px; font-weight: 800; color: var(--primary); background: rgba(0,0,0,0.5); padding: 8px 12px; border-radius: 6px; letter-spacing: 2px; display: inline-block;">
                {{ $secret }}
              </div>
            </div>
          </div>
        </div>

        <form method="POST" action="{{ route('2fa.enable') }}">
          @csrf
          <h3 style="font-size: 15px; color: #fff; margin: 0 0 8px 0;">Bước 2: Nhập mã 6 chữ số để kích hoạt</h3>
          <div style="display: flex; gap: 10px; align-items: flex-start;">
            <div style="flex: 1;">
              <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" required placeholder="VD: 123456" style="
                width: 100%;
                background: rgba(255,255,255,0.06);
                border: 1px solid var(--border);
                border-radius: 8px;
                color: #fff;
                padding: 11px 14px;
                font-size: 16px;
                letter-spacing: 4px;
                text-align: center;
                outline: none;
              ">
              @error('code')
                <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
              @enderror
            </div>
            <button type="submit" class="btn-spotlight-read" style="padding: 11px 20px; font-size: 14px; font-weight: 800; border-radius: 8px; cursor: pointer;">
              ✓ Xác Nhận & Kích Hoạt 2FA
            </button>
          </div>
        </form>
      @endif

    </div>
  </div>
</main>
@endsection
