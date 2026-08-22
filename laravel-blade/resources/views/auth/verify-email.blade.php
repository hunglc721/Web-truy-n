@extends('layouts.main')

@section('title', 'Xác Thực Địa Chỉ Email - WebComics')

@section('content')
<main class="page-container">
  <div class="container" style="max-width: 480px; padding: 40px 16px;">
    <div style="
      background: rgba(19, 22, 30, 0.95);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 32px 28px;
      text-align: center;
      box-shadow: 0 10px 40px rgba(0,0,0,0.6);
    ">
      <div style="font-size: 48px; margin-bottom: 12px;">📧</div>
      <h1 style="font-size: 22px; font-weight: 900; color: #fff; margin: 0 0 12px 0;">Xác Thực Địa Chỉ Email</h1>
      
      <p style="color: var(--text-sub); font-size: 13.5px; line-height: 1.6; margin: 0 0 24px 0;">
        Cảm ơn bạn đã đăng ký tài khoản! Trước khi bắt đầu, vui lòng kiểm tra hộp thư email (bao gồm cả thư mục Spam/Quảng cáo) và bấm vào liên kết xác thực chúng tôi vừa gửi cho bạn.
      </p>

      @if (session('success'))
        <div style="background: rgba(22, 163, 74, 0.15); border: 1px solid #16a34a; border-radius: 8px; padding: 12px; color: #4ade80; font-size: 13px; font-weight: 700; margin-bottom: 20px;">
          ✓ {{ session('success') }}
        </div>
      @endif

      <div style="display: flex; flex-direction: column; gap: 12px;">
        <form method="POST" action="{{ route('verification.send') }}">
          @csrf
          <button type="submit" class="btn-spotlight-read" style="width: 100%; padding: 11px; font-size: 14px; font-weight: 800; border-radius: 8px; cursor: pointer;">
            🔄 Gửi Lại Email Xác Thực
          </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn-spotlight-sub" style="width: 100%; padding: 10px; font-size: 13px; font-weight: 700; border-radius: 8px; cursor: pointer;">
            🚪 Đăng Xuất
          </button>
        </form>
      </div>
    </div>
  </div>
</main>
@endsection
