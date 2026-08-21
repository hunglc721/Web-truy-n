@extends('layouts.admin')

@section('title', 'Lịch Ra Truyện Tuần')
@section('breadcrumb', 'Lịch ra truyện')

@push('styles')
<style>
  .week-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:12px}.day-col{background:var(--admin-card);border:1px solid var(--admin-border);border-radius:12px;padding:14px 12px;min-height:300px;display:flex;flex-direction:column}.day-today .day-col{border-color:rgba(108,99,255,.65);background:rgba(108,99,255,.08)}.day-header{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;margin-bottom:12px;display:flex;justify-content:space-between;gap:8px}.sched-item{display:flex;gap:8px;align-items:center;padding:9px;border-radius:9px;background:rgba(255,255,255,.04);border:1px solid var(--admin-border);margin-bottom:8px}.sched-item.inactive{opacity:.55}.sched-cover{width:34px;height:46px;object-fit:cover;border-radius:5px;flex-shrink:0}.sched-info{min-width:0;flex:1}.sched-name{font-size:12px;font-weight:700;color:var(--admin-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.sched-meta{font-size:10.5px;color:var(--admin-text-muted);margin-top:2px}.sched-actions{display:flex;gap:4px;flex-shrink:0}.icon-mini{width:27px;height:27px;border-radius:6px;border:1px solid var(--admin-border);background:rgba(255,255,255,.05);color:var(--admin-text);cursor:pointer}.add-sched-btn{width:100%;padding:8px;border-radius:8px;background:rgba(108,99,255,.08);border:1px dashed rgba(108,99,255,.35);color:#9d98ff;font-size:12px;font-weight:700;cursor:pointer;margin-top:auto}.sched-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.72);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center;padding:16px}.sched-modal.open{display:flex}.sched-modal-box{background:var(--admin-card);border:1px solid var(--admin-border);border-radius:14px;padding:24px;width:min(480px,100%);max-height:90vh;overflow-y:auto}.sched-form-grid{display:grid;gap:14px}.modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}.modal-close{border:0;background:transparent;color:var(--admin-text-muted);font-size:20px;cursor:pointer}@media(max-width:1180px){.week-grid{grid-template-columns:repeat(4,1fr)}}@media(max-width:760px){.week-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:480px){.week-grid{grid-template-columns:1fr}.admin-page-header{gap:12px}}
</style>
@endpush

@section('content')
<div class="admin-page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap">
  <div><h1 class="admin-page-title">📅 Quản lý Lịch Phát Hành</h1><p class="admin-page-sub">Thêm, sửa, tắt/bật và xóa lịch; trang public /schedule đọc trực tiếp dữ liệu này.</p></div>
  <button type="button" class="btn-admin btn-admin-primary" onclick="openScheduleModal()">➕ Thêm lịch</button>
</div>

@if($errors->any())
  <div class="admin-alert admin-alert-error"><span>❌</span><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
@endif

<div class="week-grid">
  @foreach($daysData as $day)
    <div class="{{ $day['is_today'] ? 'day-today' : '' }}">
      <div class="day-col">
        <div class="day-header"><span>{{ $day['is_today'] ? '⭐ ' : '' }}{{ $day['label'] }}</span><span class="badge badge-primary">{{ $day['count'] }}</span></div>
        <div style="flex:1">
          @forelse($day['schedules'] as $sched)
            <div class="sched-item {{ $sched->is_active ? '' : 'inactive' }}">
              @if($sched->comic?->cover_image)<img src="{{ $sched->comic->cover_image }}" alt="{{ $sched->comic->title }}" class="sched-cover" loading="lazy">@else<div class="sched-cover" style="display:grid;place-items:center;background:rgba(108,99,255,.1)">📚</div>@endif
              <div class="sched-info">
                <div class="sched-name">{{ $sched->comic->title ?? 'Truyện đã xóa' }}</div>
                <div class="sched-meta">⏰ {{ substr((string)$sched->release_time,0,5) }} · {{ $sched->is_active ? 'Đang bật' : 'Đang tắt' }}</div>
              </div>
              <div class="sched-actions">
                <button type="button" class="icon-mini" title="Sửa" onclick='openScheduleModal(@json(["id"=>$sched->id,"comic_id"=>$sched->comic_id,"day_of_week"=>$sched->day_of_week,"release_time"=>substr((string)$sched->release_time,0,5),"is_active"=>$sched->is_active]))'>✏️</button>
                <form method="POST" action="{{ route('admin.schedules.destroy',$sched) }}" onsubmit="return confirm('Xóa lịch phát hành này?')">@csrf @method('DELETE')<button class="icon-mini" style="color:var(--admin-danger)" title="Xóa">🗑️</button></form>
              </div>
            </div>
          @empty
            <div style="text-align:center;padding:28px 8px;color:var(--admin-text-muted);font-size:12px">Chưa có lịch</div>
          @endforelse
        </div>
        <button class="add-sched-btn" type="button" onclick="openScheduleModal(null,{{ $day['key'] }})">+ Thêm vào {{ $day['short'] }}</button>
      </div>
    </div>
  @endforeach
</div>

<div class="sched-modal" id="schedule-modal">
  <div class="sched-modal-box">
    <div class="modal-head"><h3 id="schedule-modal-title">📅 Thêm lịch phát hành</h3><button class="modal-close" type="button" onclick="closeScheduleModal()">✕</button></div>
    <form id="schedule-form" method="POST" action="{{ route('admin.schedules.store') }}" class="sched-form-grid">
      @csrf
      <div id="schedule-method"></div>
      <div><label class="form-label">Bộ truyện <span>*</span></label><select name="comic_id" id="schedule-comic" class="form-control" required><option value="">— Chọn bộ truyện —</option>@foreach($comics as $comic)<option value="{{ $comic->id }}">{{ $comic->title }}</option>@endforeach</select></div>
      <div><label class="form-label">Ngày trong tuần <span>*</span></label><select name="day_of_week" id="schedule-day" class="form-control" required><option value="1">Thứ Hai</option><option value="2">Thứ Ba</option><option value="3">Thứ Tư</option><option value="4">Thứ Năm</option><option value="5">Thứ Sáu</option><option value="6">Thứ Bảy</option><option value="0">Chủ Nhật</option></select></div>
      <div><label class="form-label">Giờ phát hành <span>*</span></label><input type="time" name="release_time" id="schedule-time" class="form-control" value="20:00" required></div>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" id="schedule-active" value="1" checked> Đang áp dụng lịch này</label>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px"><button type="button" class="btn-admin btn-admin-ghost" onclick="closeScheduleModal()">Hủy</button><button class="btn-admin btn-admin-primary" type="submit">💾 Lưu lịch</button></div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const scheduleModal = document.getElementById('schedule-modal');
  const scheduleForm = document.getElementById('schedule-form');
  const scheduleBaseUrl = @json(url('/admin/schedules'));
  const scheduleStoreUrl = @json(route('admin.schedules.store'));

  function openScheduleModal(data = null, preferredDay = null) {
    document.getElementById('schedule-method').innerHTML = '';
    scheduleForm.action = scheduleStoreUrl;
    document.getElementById('schedule-modal-title').textContent = data ? '✏️ Sửa lịch phát hành' : '📅 Thêm lịch phát hành';
    document.getElementById('schedule-comic').value = data?.comic_id ?? '';
    document.getElementById('schedule-day').value = data?.day_of_week ?? preferredDay ?? 1;
    document.getElementById('schedule-time').value = data?.release_time ?? '20:00';
    document.getElementById('schedule-active').checked = data ? Boolean(data.is_active) : true;

    if (data?.id) {
      scheduleForm.action = `${scheduleBaseUrl}/${data.id}`;
      document.getElementById('schedule-method').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    }
    scheduleModal.classList.add('open');
  }

  function closeScheduleModal(){ scheduleModal.classList.remove('open'); }
  scheduleModal?.addEventListener('click', e => { if(e.target === scheduleModal) closeScheduleModal(); });
  document.addEventListener('keydown', e => { if(e.key === 'Escape') closeScheduleModal(); });
</script>
@endpush
