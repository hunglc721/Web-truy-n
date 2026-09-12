@extends('layouts.admin')

@section('title', 'Cài Đặt Website')
@section('breadcrumb', 'Cài đặt Website')

@section('topbar-actions')
  <button type="submit" form="settings-form" class="topbar-btn topbar-btn-primary">💾 Lưu cài đặt</button>
@endsection

@push('styles')
<style>
  .branding-preview{display:block;width:96px;height:96px;object-fit:contain;margin-bottom:12px;padding:8px;border:1px solid var(--admin-border);border-radius:10px;background:var(--admin-bg)}.branding-preview[hidden]{display:none}.branding-field{min-width:0}.branding-field input[type=file]{max-width:100%}.branding-actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:12px}
  .settings-grid{display:grid;grid-template-columns:1fr;gap:20px}.settings-card{background:var(--admin-card);border:1px solid var(--admin-border);border-radius:var(--admin-radius);padding:22px}.settings-card h2{font-size:15px;margin:0 0 18px;padding-bottom:14px;border-bottom:1px solid var(--admin-border)}.settings-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}.settings-check{display:flex;gap:12px;align-items:flex-start;padding:14px;border-radius:10px;background:rgba(255,255,255,.035);border:1px solid var(--admin-border)}.settings-check input{width:18px;height:18px;accent-color:var(--admin-primary);margin-top:2px}.settings-check strong{display:block;font-size:13.5px}.settings-check span{display:block;color:var(--admin-text-muted);font-size:12px;line-height:1.5;margin-top:3px}.danger-note{padding:12px 14px;border-radius:9px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.24);color:#fca5a5;font-size:12.5px;line-height:1.6}@media(max-width:760px){.settings-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="admin-page-header">
  <h1 class="admin-page-title">⚙️ Cài Đặt Website</h1>
  <p class="admin-page-sub">Cấu hình SEO, mạng xã hội và chế độ bảo trì bằng dữ liệu Laravel thật.</p>
</div>

<form id="settings-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">
  @csrf
  @method('PUT')

  <div class="settings-grid">
    <section class="settings-card">
      <h2>🎨 Nhận Diện Thương Hiệu</h2>
      <div class="settings-row">
        @foreach(['site_logo' => 'Logo Website', 'site_favicon' => 'Favicon trình duyệt'] as $key => $label)
          <div class="branding-field" data-branding-field>
            <label class="form-label" for="{{ $key }}">{{ $label }}</label>
            <img class="branding-preview" id="{{ $key }}-preview" src="{{ $settings[$key . '_url'] ?? ($key === 'site_favicon' ? $settings['favicon_url'] : asset('images/default-brand.svg')) }}" alt="{{ $label }} hiện tại" data-branding-preview />
            <input id="{{ $key }}" name="{{ $key }}" type="file" class="form-control" accept="{{ $key === 'site_logo' ? 'image/png,image/jpeg,image/webp' : 'image/png,image/x-icon,image/vnd.microsoft.icon,image/webp,.ico' }}" data-branding-input />
            <div class="form-hint">{{ $key === 'site_logo' ? 'PNG, JPG/JPEG, WEBP; tối đa 2 MB. Nên dùng logo vuông hoặc ngang, nền trong suốt (gợi ý 256 × 256 px).' : 'Icon trên tab trình duyệt: PNG, ICO, WEBP; tối đa 512 KB. Gợi ý 32 × 32 hoặc 64 × 64 px. Chưa có favicon sẽ dùng logo website.' }}</div>
            <div class="branding-actions">
              <label><input type="checkbox" name="remove_{{ $key }}" value="1" data-branding-remove @checked(old('remove_' . $key)) /> Xóa {{ $key === 'site_logo' ? 'logo' : 'favicon' }} khi lưu</label>
            </div>
            @error($key)<span class="invalid-feedback">{{ $message }}</span>@enderror
            @error('remove_' . $key)<span class="invalid-feedback">{{ $message }}</span>@enderror
          </div>
        @endforeach
      </div>
    </section>
    <section class="settings-card">
      <h2>🌐 Thông tin Website</h2>
      <div class="form-group">
        <label class="form-label">Tên Website <span>*</span></label>
        <input class="form-control @error('site_name') is-invalid @enderror" type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required maxlength="100" />
        @error('site_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label">Tagline</label>
        <input class="form-control" type="text" name="tagline" value="{{ old('tagline', $settings['tagline']) }}" maxlength="180" />
      </div>
      <div class="form-group">
        <label class="form-label">Meta Description</label>
        <textarea class="form-control" name="meta_description" maxlength="320">{{ old('meta_description', $settings['meta_description']) }}</textarea>
        <div class="form-hint">Khuyến nghị khoảng 150–160 ký tự cho SEO.</div>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Keywords SEO</label>
        <input class="form-control" type="text" name="seo_keywords" value="{{ old('seo_keywords', $settings['seo_keywords']) }}" maxlength="500" />
      </div>
    </section>

    <section class="settings-card">
      <h2>🔗 Mạng Xã Hội</h2>
      <div class="settings-row">
        <div class="form-group">
          <label class="form-label">Facebook</label>
          <input class="form-control" type="url" name="facebook_url" value="{{ old('facebook_url', $settings['facebook_url']) }}" placeholder="https://facebook.com/..." />
          @error('facebook_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Twitter / X</label>
          <input class="form-control" type="url" name="twitter_url" value="{{ old('twitter_url', $settings['twitter_url']) }}" placeholder="https://x.com/..." />
          @error('twitter_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Discord Invite</label>
        <input class="form-control" type="url" name="discord_url" value="{{ old('discord_url', $settings['discord_url']) }}" placeholder="https://discord.gg/..." />
        @error('discord_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Google Analytics ID</label>
        <input class="form-control" type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $settings['google_analytics_id']) }}" placeholder="G-XXXXXXXXXX" maxlength="50" />
      </div>
    </section>

    <section class="settings-card">
      <h2>🚧 Chế Độ Bảo Trì</h2>
      <label class="settings-check" style="margin-bottom:16px;">
        <input type="checkbox" name="maintenance_mode" value="1" {{ old('maintenance_mode', $settings['maintenance_mode']) ? 'checked' : '' }} />
        <span>
          <strong>Bật chế độ bảo trì</strong>
          <span>Khách và Member nhận HTTP 503. Admin, trang đăng nhập quản trị và các IP cho phép vẫn truy cập được.</span>
        </span>
      </label>
      <div class="form-group">
        <label class="form-label">Thông báo bảo trì</label>
        <textarea class="form-control" name="maintenance_message" maxlength="500">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
      </div>
      <div class="form-group">
        <label class="form-label">IP được phép</label>
        <input class="form-control" type="text" name="maintenance_ips" value="{{ old('maintenance_ips', $settings['maintenance_ips']) }}" placeholder="127.0.0.1, 192.168.1.10" />
        <div class="form-hint">Phân cách nhiều IP bằng dấu phẩy.</div>
      </div>
      <div class="danger-note">Khi bật maintenance, độc giả sẽ không vào được trang đọc truyện. Admin vẫn vào `/login` và `/admin` để tắt lại, khỏi tự khóa mình ngoài cửa như một thiên tài hệ thống.</div>
    </section>
  </div>
  <button type="submit" class="topbar-btn topbar-btn-primary" style="margin-top:20px">💾 Lưu cài đặt</button>
</form>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-branding.js') }}" defer></script>
@endpush
