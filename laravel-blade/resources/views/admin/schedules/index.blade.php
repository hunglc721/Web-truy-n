@extends('layouts.admin')

@section('title', 'Lịch Ra Truyện Tuần')

@push('styles')
<style>
  .week-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 12px;
  }
  .day-col {
    background: #1a1d27;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 14px 12px;
    min-height: 280px;
    display: flex;
    flex-direction: column;
  }
  .day-today .day-col {
    border-color: rgba(99, 102, 241, 0.6);
    background: rgba(99, 102, 241, 0.08);
    box-shadow: 0 0 20px rgba(99, 102, 241, 0.15);
  }
  .day-header {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .sched-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 8px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    margin-bottom: 8px;
    position: relative;
    transition: all 0.2s ease;
  }
  .sched-item:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.15);
  }
  .sched-cover {
    width: 32px;
    height: 44px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
  }
  .sched-cover-ph {
    width: 32px;
    height: 44px;
    border-radius: 4px;
    background: rgba(99, 102, 241, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }
  .sched-name {
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .sched-time {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 2px;
  }
  .sched-del-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    background: rgba(239,68,68,0.2);
    border: none;
    color: #f87171;
    font-size: 11px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.15s ease;
  }
  .sched-item:hover .sched-del-btn {
    opacity: 1;
  }
  .add-sched-btn {
    width: 100%;
    padding: 8px;
    border-radius: 8px;
    background: rgba(99, 102, 241, 0.08);
    border: 1px dashed rgba(99, 102, 241, 0.3);
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    transition: all 0.15s ease;
    margin-top: auto;
  }
  .add-sched-btn:hover {
    background: rgba(99, 102, 241, 0.18);
    color: #818cf8;
    border-color: #818cf8;
  }

  @media(max-width: 1100px) {
    .week-grid { grid-template-columns: repeat(4, 1fr); }
  }
  @media(max-width: 768px) {
    .week-grid { grid-template-columns: repeat(2, 1fr); }
  }
  @media(max-width: 480px) {
    .week-grid { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
<div class="ph" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1>📅 Quản lý Lịch Phát Sóng Tuần</h1>
    <p>Gán ngày phát hành theo tuần cho từng bộ truyện, tự động cập nhật ra trang chủ và lịch phát sóng.</p>
  </div>
  <button type="button" onclick="openAddModal()" class="btn btn-login" style="padding: 9px 18px; font-weight: 700;">
    + Thêm Lịch Mới
  </button>
</div>

{{-- Grid 7 ngày trong tuần --}}
<div class="week-grid">
  @foreach($daysData as $day)
    <div class="{{ $day['is_today'] ? 'day-today' : '' }}">
      <div class="day-col">
        {{-- Header Ngày --}}
        <div class="day-header">
          <span style="color: {{ $day['is_today'] ? '#818cf8' : '#fff' }};">
            {{ $day['is_today'] ? '⭐ ' : '' }}{{ $day['label'] }}
          </span>
          <span class="badge" style="background: rgba(255,255,255,0.08); color: var(--text-muted); font-size: 11px; padding: 2px 7px; border-radius: 10px;">
            {{ $day['count'] }}
          </span>
        </div>

        {{-- Danh sách truyện trong ngày --}}
        <div class="sched-list" style="flex: 1;">
          @forelse($day['schedules'] as $sched)
            <div class="sched-item">
              @if($sched->comic && $sched->comic->cover_image)
                <img src="{{ $sched->comic->cover_image }}" alt="" class="sched-cover" />
              @else
                <div class="sched-cover-ph">📚</div>
              @endif

              <div style="flex: 1; min-width: 0;">
                <div class="sched-name" title="{{ $sched->comic->title ?? 'Truyện' }}">
                  <a href="{{ $sched->comic ? route('comics.show', $sched->comic->slug) : '#' }}" target="_blank" class="hover:underline" style="color: #fff; text-decoration: none;">
                    {{ $sched->comic->title ?? 'Truyện đã xóa' }}
                  </a>
                </div>
                <div class="sched-time">
                  ⏰ {{ $sched->release_time ?: '20:00' }}
                </div>
              </div>

              {{-- Nút xóa lịch --}}
              <form method="POST" action="{{ route('admin.schedules.destroy', $sched) }}" onsubmit="return confirm('Xóa lịch phát hành của truyện này?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="sched-del-btn" title="Xóa lịch">✕</button>
              </form>
            </div>
          @empty
            <div style="text-align: center; padding: 30px 10px; color: var(--text-muted); font-size: 11.5px; font-style: italic;">
              Chưa có truyện
            </div>
          @endforelse
        </div>

        {{-- Nút thêm nhanh cho ngày này --}}
        <button type="button" class="add-sched-btn" onclick="openAddModal({{ $day['key'] }})">
          + Thêm vào {{ $day['short'] }}
        </button>
      </div>
    </div>
  @endforeach
</div>

{{-- MODAL THÊM LỊCH --}}
<div class="modal-overlay" id="add-sched-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: #1a1d27; border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 26px 28px; width: 90%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.8);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 style="color: #fff; font-size: 17px; font-weight: 800; margin: 0;">📅 Thêm Lịch Phát Hành</h3>
      <button type="button" onclick="closeAddModal()" style="background: none; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer;">✕</button>
    </div>

    <form method="POST" action="{{ route('admin.schedules.store') }}">
      @csrf

      {{-- Chọn Bộ Truyện --}}
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
          Bộ truyện <span style="color: var(--primary);">*</span>
        </label>
        <select name="comic_id" required style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 13.5px; outline: none;">
          <option value="" style="background: #1a1d27;">— Chọn bộ truyện —</option>
          @foreach($comics as $c)
            <option value="{{ $c->id }}" style="background: #1a1d27;">{{ $c->title }}</option>
          @endforeach
        </select>
      </div>

      {{-- Chọn Ngày Trong Tuần --}}
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
          Ngày phát sóng trong tuần <span style="color: var(--primary);">*</span>
        </label>
        <select name="day_of_week" id="modal-day-select" required style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 13.5px; outline: none;">
          <option value="1" style="background: #1a1d27;">Thứ Hai (Monday)</option>
          <option value="2" style="background: #1a1d27;">Thứ Ba (Tuesday)</option>
          <option value="3" style="background: #1a1d27;">Thứ Tư (Wednesday)</option>
          <option value="4" style="background: #1a1d27;">Thứ Năm (Thursday)</option>
          <option value="5" style="background: #1a1d27;">Thứ Sáu (Friday)</option>
          <option value="6" style="background: #1a1d27;">Thứ Bảy (Saturday)</option>
          <option value="0" style="background: #1a1d27;">Chủ Nhật (Sunday)</option>
        </select>
      </div>

      {{-- Giờ Phát Hành --}}
      <div style="margin-bottom: 24px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #e0e0e0; margin-bottom: 6px;">
          Khung giờ phát hành
        </label>
        <input type="time" name="release_time" value="20:00" style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 13.5px; outline: none;" />
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" onclick="closeAddModal()" class="btn btn-login" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15);">
          Hủy bỏ
        </button>
        <button type="submit" class="btn btn-login" style="font-weight: 700;">
          💾 Lưu Lịch Phát Hành
        </button>
      </div>
    </form>

  </div>
</div>

<script>
  function openAddModal(dayIndex = null) {
    const modal = document.getElementById('add-sched-modal');
    if (dayIndex !== null) {
      document.getElementById('modal-day-select').value = dayIndex;
    }
    if (modal) modal.style.display = 'flex';
  }

  function closeAddModal() {
    const modal = document.getElementById('add-sched-modal');
    if (modal) modal.style.display = 'none';
  }

  document.getElementById('add-sched-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
  });
</script>
@endsection
