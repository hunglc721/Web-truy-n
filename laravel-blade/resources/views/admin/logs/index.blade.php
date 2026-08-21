@extends('layouts.admin')

@section('title', 'Nhật ký Hoạt động')
@section('breadcrumb', 'Nhật ký Hoạt động')

@push('styles')
<style>
  .audit-filter-grid{display:grid;grid-template-columns:2fr 1.2fr 1.2fr 1fr 1fr auto;gap:10px;align-items:end}.audit-badge{display:inline-flex;padding:4px 8px;border-radius:7px;font:700 11px/1.2 monospace;background:rgba(108,99,255,.12);color:#a5b4fc;border:1px solid rgba(108,99,255,.25)}.audit-payload pre{max-width:360px;max-height:180px;overflow:auto;background:rgba(0,0,0,.35);border:1px solid var(--admin-border);border-radius:7px;padding:8px;color:#a5f3fc;font-size:10.5px}.retention-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.72);z-index:9999;align-items:center;justify-content:center;padding:16px}.retention-modal.open{display:flex}.retention-box{width:min(440px,100%);background:var(--admin-card);border:1px solid var(--admin-border);border-radius:14px;padding:24px}@media(max-width:1100px){.audit-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.audit-filter-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="admin-page-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
  <div><h1 class="admin-page-title">📋 Nhật ký Hoạt động Hệ Thống</h1><p class="admin-page-sub">Theo dõi action, actor, subject, IP và payload thật từ ActivityLog.</p></div>
  @if(auth()->user()->hasPermission('permissions.manage'))<button type="button" class="btn-admin btn-admin-ghost" onclick="openRetentionModal()">🧹 Dọn log cũ</button>@endif
</div>

<div class="admin-card" style="margin-bottom:18px">
  <form method="GET" action="{{ route('admin.logs.index') }}" class="audit-filter-grid">
    <div><label class="form-label">Tìm kiếm</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Action, user, email, IP, subject..." /></div>
    <div><label class="form-label">Nhóm action</label><select class="form-control" name="action_group"><option value="">Tất cả</option>@foreach($actionGroups as $prefix=>$label)<option value="{{ $prefix }}" {{ request('action_group')===$prefix?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
    <div><label class="form-label">Người thực hiện</label><select class="form-control" name="user_id"><option value="">Tất cả</option>@foreach($users as $u)<option value="{{ $u->id }}" {{ (string)request('user_id')===(string)$u->id?'selected':'' }}>{{ $u->name }}</option>@endforeach</select></div>
    <div><label class="form-label">Từ ngày</label><input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}" /></div>
    <div><label class="form-label">Đến ngày</label><input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}" /></div>
    <div style="display:flex;gap:6px"><button class="btn-admin btn-admin-primary">🔍 Lọc</button>@if(request()->hasAny(['q','action_group','user_id','date_from','date_to']))<a class="btn-admin btn-admin-ghost" href="{{ route('admin.logs.index') }}">✕</a>@endif</div>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-header"><span class="admin-card-title">Nhật ký hệ thống</span><span style="font-size:12px;color:var(--admin-text-muted)">{{ $logs->total() }} bản ghi</span></div>
  <div style="overflow-x:auto">
    <table class="admin-table" style="min-width:980px">
      <thead><tr><th>Thời gian</th><th>Tác nhân</th><th>Action</th><th>Đối tượng</th><th>Payload</th><th>IP</th></tr></thead>
      <tbody>
        @forelse($logs as $log)
          <tr>
            <td style="white-space:nowrap;font-size:12px"><strong>{{ $log->created_at?->format('d/m/Y H:i:s') ?? '—' }}</strong><div style="color:var(--admin-text-muted)">{{ $log->created_at?->diffForHumans() }}</div></td>
            <td>@if($log->user)<strong>{{ $log->user->name }}</strong><div style="font-size:11px;color:var(--admin-text-muted)">{{ $log->user->email }}</div>@else<span style="color:var(--admin-text-muted)">⚙️ System</span>@endif</td>
            <td><span class="audit-badge">{{ $log->action }}</span></td>
            <td style="font-size:12px">@if($log->subject_type)<code>{{ class_basename($log->subject_type) }}</code> #{{ $log->subject_id }}@else—@endif</td>
            <td class="audit-payload">@if(is_array($log->payload) && count($log->payload))<details><summary style="cursor:pointer;color:var(--admin-primary);font-size:12px">Xem {{ count($log->payload) }} trường</summary><pre>{{ json_encode($log->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>@else<span style="color:var(--admin-text-muted)">—</span>@endif</td>
            <td style="font:12px monospace;color:var(--admin-text-muted)">{{ $log->ip_address ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center;padding:48px;color:var(--admin-text-muted)">📭 Không có audit log phù hợp.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination-wrap">{{ $logs->links() }}</div>
</div>

@if(auth()->user()->hasPermission('permissions.manage'))
<div class="retention-modal" id="retention-modal">
  <div class="retention-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px"><h3>🧹 Dọn log theo retention</h3><button type="button" style="border:0;background:transparent;color:var(--admin-text-muted);font-size:20px;cursor:pointer" onclick="closeRetentionModal()">✕</button></div>
    <p style="font-size:13px;color:var(--admin-text-muted);line-height:1.6;margin-bottom:14px">Không hỗ trợ xóa sạch toàn bộ audit trail. Chỉ xóa các bản ghi cũ hơn mốc được chọn.</p>
    <form method="POST" action="{{ route('admin.logs.clear') }}" onsubmit="return confirm('Dọn các audit log cũ theo mốc này?')">@csrf @method('DELETE')
      <select name="days" class="form-control" required><option value="30">Giữ 30 ngày gần nhất</option><option value="60">Giữ 60 ngày gần nhất</option><option value="90">Giữ 90 ngày gần nhất</option><option value="180">Giữ 180 ngày gần nhất</option><option value="365">Giữ 365 ngày gần nhất</option></select>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px"><button type="button" class="btn-admin btn-admin-ghost" onclick="closeRetentionModal()">Hủy</button><button class="btn-admin btn-admin-danger">Dọn log cũ</button></div>
    </form>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
  const retentionModal=document.getElementById('retention-modal');
  function openRetentionModal(){retentionModal?.classList.add('open')}
  function closeRetentionModal(){retentionModal?.classList.remove('open')}
  retentionModal?.addEventListener('click',e=>{if(e.target===retentionModal)closeRetentionModal()});
</script>
@endpush
