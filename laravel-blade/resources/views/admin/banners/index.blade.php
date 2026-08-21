@extends('layouts.admin')

@section('title', 'Quản lý Banner Trang Chủ')

@push('styles')
<style>
  .banner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
  }
  .banner-card {
    background: #1a1d27;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
  }
  .banner-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    border-color: rgba(255,255,255,0.15);
  }
  .banner-img-wrap {
    width: 100%;
    height: 150px;
    background: #0f1118;
    position: relative;
  }
  .banner-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  .banner-body {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .banner-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 6px;
  }
  .banner-link {
    font-size: 12px;
    color: var(--primary);
    text-decoration: none;
    display: block;
    margin-bottom: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .banner-link:hover {
    text-decoration: underline;
  }
</style>
@endpush

@section('content')
<div class="ph" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1>🖼️ Quản lý Banner Hero Slider Trang Chủ</h1>
    <p>Thêm, sửa, bật/tắt, tải ảnh và thiết lập thời hạn hiệu lực cho banner hiển thị trên trang chủ.</p>
  </div>
  <button type="button" onclick="openAddModal()" class="btn btn-login" style="padding: 9px 18px; font-weight: 700;">
    + Thêm Banner Mới
  </button>
</div>

{{-- Thống kê nhanh --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
  <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-3.5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tổng Banner</div>
    <div class="text-2xl font-black text-indigo-400 mt-1">{{ $stats['total'] }}</div>
  </div>
  <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-3.5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">🟢 Đang Bật</div>
    <div class="text-2xl font-black text-emerald-400 mt-1">{{ $stats['active'] }}</div>
  </div>
  <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-3.5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">⭕ Đã Tắt</div>
    <div class="text-2xl font-black text-slate-400 mt-1">{{ $stats['inactive'] }}</div>
  </div>
  <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-3.5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">⌛ Đã Hết Hạn</div>
    <div class="text-2xl font-black text-rose-400 mt-1">{{ $stats['expired'] }}</div>
  </div>
  <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-3.5">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">⏰ Chờ Lên Lịch</div>
    <div class="text-2xl font-black text-amber-400 mt-1">{{ $stats['scheduled'] }}</div>
  </div>
</div>

{{-- Danh sách Banner Cards --}}
<div class="banner-grid">
  @forelse($banners as $banner)
    <div class="banner-card">
      <div class="banner-img-wrap">
        <img src="{{ $banner->display_image }}" alt="{{ $banner->title }}" class="banner-img" />
        
        <div style="position: absolute; top: 8px; left: 8px;">
          <span style="background: rgba(0,0,0,0.7); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
            Thứ tự: {{ $banner->order }}
          </span>
        </div>

        <div style="position: absolute; top: 8px; right: 8px;">
          @if(!$banner->is_active)
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">
              ⭕ Đã tắt
            </span>
          @elseif($banner->is_expired)
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
              ⌛ Hết hạn (Tự ẩn)
            </span>
          @elseif($banner->is_scheduled)
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
              ⏰ Chờ lịch
            </span>
          @else
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
              🟢 Đang hiển thị
            </span>
          @endif
        </div>
      </div>

      <div class="banner-body">
        <div class="banner-title" title="{{ $banner->title }}">{{ $banner->title }}</div>

        @if($banner->link_url)
          <a href="{{ $banner->link_url }}" target="_blank" class="banner-link">🔗 {{ $banner->link_url }}</a>
        @else
          <span style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 12px;">🔗 Không gắn link</span>
        @endif

        {{-- Thời hạn hiệu lực --}}
        <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.5;">
          <div>• Bắt đầu: <strong>{{ $banner->start_at ? $banner->start_at->format('d/m/Y H:i') : 'Ngay lập tức' }}</strong></div>
          <div>• Hết hạn: <strong>{{ $banner->end_at ? $banner->end_at->format('d/m/Y H:i') : 'Vô thời hạn' }}</strong></div>
        </div>

        {{-- Thao tác --}}
        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px;">
          {{-- Toggle Bật / Tắt --}}
          <form method="POST" action="{{ route('admin.banners.toggleActive', $banner) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg transition {{ $banner->is_active ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/25' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-750' }}">
              {{ $banner->is_active ? '🟢 BẬT' : '⭕ TẮT' }}
            </button>
          </form>

          <div style="display: flex; gap: 6px;">
            <button type="button" onclick="openEditModal({{ json_encode($banner) }})" class="px-2.5 py-1 bg-indigo-500/15 hover:bg-indigo-500/30 text-indigo-400 border border-indigo-500/30 rounded-md text-xs font-semibold transition">
              ✏️ Sửa
            </button>

            <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Xóa banner này?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="px-2 py-1 bg-rose-500/15 hover:bg-rose-500/30 text-rose-400 border border-rose-500/30 rounded-md text-xs font-semibold transition" title="Xóa">
                🗑️
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  @empty
    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: var(--text-muted);">
      <div style="font-size: 48px; margin-bottom: 12px;">🖼️</div>
      <p style="font-size: 16px; font-weight: 700; color: #fff;">Chưa có banner nào.</p>
      <p style="font-size: 13.5px; margin-top: 4px;">Nhấp vào nút "+ Thêm Banner Mới" để tạo banner quảng cáo trang chủ đầu tiên.</p>
    </div>
  @endforelse
</div>

{{-- MODAL THÊM / SỬA BANNER --}}
<div class="modal-overlay" id="banner-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: #1a1d27; border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 26px 28px; width: 90%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.8); max-height: 90vh; overflow-y: auto;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 id="modal-title" style="color: #fff; font-size: 17px; font-weight: 800; margin: 0;">🖼️ Thêm Banner Mới</h3>
      <button type="button" onclick="closeBannerModal()" style="background: none; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">✕</button>
    </div>

    <form id="banner-form" method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
      @csrf
      <div id="method-container"></div>

      {{-- Tiêu đề --}}
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
          Tiêu đề Banner <span style="color: var(--primary);">*</span>
        </label>
        <input type="text" name="title" id="inp-title" required placeholder="VD: Solo Leveling Season 2 — Siêu Phẩm Trở Lại" style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 13.5px; outline: none;" />
      </div>

      {{-- Upload File Ảnh hoặc Nhập URL --}}
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
          Hình ảnh Banner (Upload hoặc URL)
        </label>
        <input type="file" name="image" accept="image/*" style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 12.5px; margin-bottom: 6px;" />
        <input type="url" name="image_url" id="inp-image-url" placeholder="Hoặc nhập URL ảnh: https://..." style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 13px; outline: none;" />
        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Khuyến nghị: Tỉ lệ 3:1 hoặc 1200×400px.</div>
      </div>

      {{-- Link URL khi click --}}
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
          Đường dẫn liên kết khi click
        </label>
        <input type="url" name="link_url" id="inp-link-url" placeholder="https://webcomics.vn/truyen/solo-leveling" style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 13.5px; outline: none;" />
      </div>

      {{-- Thứ tự hiển thị --}}
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
          Thứ tự ưu tiên (Số nhỏ hiển thị trước)
        </label>
        <input type="number" name="order" id="inp-order" value="0" min="0" style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 13.5px; outline: none;" />
      </div>

      {{-- Thời gian bắt đầu & Hết hạn --}}
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
        <div>
          <label style="display: block; font-size: 12px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
            Ngày bắt đầu (Tùy chọn)
          </label>
          <input type="datetime-local" name="start_at" id="inp-start-at" style="width: 100%; padding: 8px 10px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 12px; outline: none;" />
        </div>
        <div>
          <label style="display: block; font-size: 12px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
            Ngày hết hạn (Tự động ẩn)
          </label>
          <input type="datetime-local" name="end_at" id="inp-end-at" style="width: 100%; padding: 8px 10px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 12px; outline: none;" />
        </div>
      </div>

      {{-- Bật hiển thị --}}
      <div style="margin-bottom: 24px;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: #fff;">
          <input type="checkbox" name="is_active" id="inp-is-active" value="1" checked style="accent-color: var(--primary); width: 16px; height: 16px;" />
          Kích hoạt hiển thị banner ngay
        </label>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" onclick="closeBannerModal()" class="btn btn-login" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15);">
          Hủy
        </button>
        <button type="submit" class="btn btn-login" style="font-weight: 700;">
          💾 Lưu Banner
        </button>
      </div>
    </form>

  </div>
</div>

<script>
  function openAddModal() {
    document.getElementById('modal-title').textContent = '🖼️ Thêm Banner Mới';
    const form = document.getElementById('banner-form');
    form.action = "{{ route('admin.banners.store') }}";
    document.getElementById('method-container').innerHTML = '';

    document.getElementById('inp-title').value = '';
    document.getElementById('inp-image-url').value = '';
    document.getElementById('inp-link-url').value = '';
    document.getElementById('inp-order').value = '0';
    document.getElementById('inp-start-at').value = '';
    document.getElementById('inp-end-at').value = '';
    document.getElementById('inp-is-active').checked = true;

    document.getElementById('banner-modal').style.display = 'flex';
  }

  function openEditModal(banner) {
    document.getElementById('modal-title').textContent = '✏️ Chỉnh Sửa Banner';
    const form = document.getElementById('banner-form');
    form.action = `/admin/banners/${banner.id}`;
    document.getElementById('method-container').innerHTML = '@method("PUT")';

    document.getElementById('inp-title').value = banner.title || '';
    document.getElementById('inp-image-url').value = strStartsWithHttp(banner.image_url) ? banner.image_url : '';
    document.getElementById('inp-link-url').value = banner.link_url || '';
    document.getElementById('inp-order').value = banner.order || 0;
    
    document.getElementById('inp-start-at').value = banner.start_at ? banner.start_at.substring(0, 16) : '';
    document.getElementById('inp-end-at').value = banner.end_at ? banner.end_at.substring(0, 16) : '';
    document.getElementById('inp-is-active').checked = Boolean(banner.is_active);

    document.getElementById('banner-modal').style.display = 'flex';
  }

  function strStartsWithHttp(str) {
    return str && (str.startsWith('http://') || str.startsWith('https://'));
  }

  function closeBannerModal() {
    document.getElementById('banner-modal').style.display = 'none';
  }

  document.getElementById('banner-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeBannerModal();
  });
</script>
@endsection
