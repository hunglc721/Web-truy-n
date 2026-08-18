{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.main')

@section('title', 'Đăng Nhập — WebComics')

@section('content')
<main class="page-container" style="padding: 60px 0; min-height: 75vh; display: flex; align-items: center; justify-content: center;">
  <div class="container" style="max-width: 440px;">

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
        <h1 style="font-size: 22px; font-weight: 900; color: var(--text-main); margin-bottom: 4px;">Đăng Nhập Tài Khoản</h1>
        <p style="font-size: 13px; color: var(--text-muted);">Đăng nhập để theo dõi truyện và đồng bộ tủ sách cá nhân</p>
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

      @if(session('error'))
        <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #ef4444; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 13px;">
          {{ session('error') }}
        </div>
      @endif

      <form action="{{ route('login') }}" method="POST" novalidate>
        @csrf

        {{-- Email --}}
        <div style="margin-bottom: 18px;">
          <label style="display: block; font-weight: 700; font-size: 13px; color: var(--text-main); margin-bottom: 6px;">
            Địa chỉ Email <span style="color: var(--primary);">*</span>
          </label>
          <input
            type="email" name="email" value="{{ old('email') }}" required autofocus
            placeholder="nhap.email@domain.com"
            style="width: 100%; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 14px; color: var(--text-main); font-size: 14px; outline: none;"
          />
        </div>

        {{-- Password --}}
        <div style="margin-bottom: 20px;">
          <label style="display: block; font-weight: 700; font-size: 13px; color: var(--text-main); margin-bottom: 6px;">
            Mật khẩu <span style="color: var(--primary);">*</span>
          </label>
          <input
            type="password" name="password" required
            placeholder="••••••••"
            style="width: 100%; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 14px; color: var(--text-main); font-size: 14px; outline: none;"
          />
        </div>

        {{-- Remember me --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 13px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-muted);">
            <input type="checkbox" name="remember" value="1" style="width: 16px; height: 16px; accent-color: var(--primary);">
            <span>Ghi nhớ đăng nhập</span>
          </label>
        </div>

        <button type="submit" class="btn btn-login" style="width: 100%; padding: 13px; font-weight: 800; font-size: 15px; justify-content: center;">
          🔑 Đăng Nhập
        </button>
      </form>

      <div style="border-top: 1px solid var(--border-color); margin-top: 24px; padding-top: 20px; text-align: center; font-size: 13px; color: var(--text-muted);">
        Chưa có tài khoản?
        <a href="{{ route('register') }}" style="color: var(--primary); font-weight: 700; text-decoration: none;">
          Đăng ký ngay
        </a>
      </div>

    </div>

  </div>
</main>
@endsection
