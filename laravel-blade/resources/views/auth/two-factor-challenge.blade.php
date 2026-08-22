@extends('layouts.main')

@section('title', 'Xác Thực 2 Bước (2FA Challenge) - WebComics')

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
      <div style="text-align: center; margin-bottom: 20px;">
        <div style="font-size: 44px; margin-bottom: 8px;">🛡️</div>
        <h1 style="font-size: 22px; font-weight: 900; color: #fff; margin: 0 0 6px 0;">Xác Thực 2 Bước</h1>
        <p style="color: var(--text-sub); font-size: 13px; margin: 0;">
          Vui lòng nhập mã OTP 6 chữ số từ ứng dụng Authenticator của bạn.
        </p>
      </div>

      <form method="POST" action="{{ route('2fa.challenge.verify') }}">
        @csrf

        <div id="otp-group" style="margin-bottom: 20px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Mã xác thực OTP (6 chữ số)
          </label>
          <input type="text" name="code" autofocus maxlength="6" pattern="[0-9]{6}" placeholder="------" style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 12px 14px;
            font-size: 20px;
            letter-spacing: 6px;
            text-align: center;
            outline: none;
          ">
          @error('code')
            <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
          @enderror
        </div>

        <div id="recovery-group" style="display: none; margin-bottom: 20px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Mã khôi phục dự phòng
          </label>
          <input type="text" name="recovery_code" placeholder="xxxxxxxxxx-xxxxxxxxxx" style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 11px 14px;
            font-size: 14px;
            font-family: monospace;
            outline: none;
          ">
        </div>

        <button type="submit" class="btn-spotlight-read" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 800; border-radius: 8px; cursor: pointer; margin-bottom: 14px;">
          ✓ Xác Thực & Tiếp Tục
        </button>

        <div style="text-align: center;">
          <button type="button" id="toggle-recovery-btn" onclick="toggleRecovery()" style="background: none; border: none; color: var(--primary); font-size: 13px; font-weight: 700; cursor: pointer;">
            Sử dụng mã khôi phục dự phòng
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

@push('scripts')
<script>
  let useRecovery = false;
  function toggleRecovery() {
    useRecovery = !useRecovery;
    document.getElementById('otp-group').style.display = useRecovery ? 'none' : 'block';
    document.getElementById('recovery-group').style.display = useRecovery ? 'block' : 'none';
    document.getElementById('toggle-recovery-btn').textContent = useRecovery ? 'Sử dụng mã OTP từ Authenticator' : 'Sử dụng mã khôi phục dự phòng';
  }
</script>
@endpush
@endsection
