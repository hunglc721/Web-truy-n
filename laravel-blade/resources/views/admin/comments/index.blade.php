@extends('layouts.admin')
@section('title','Quản lý Bình luận')
@section('breadcrumb','Bình luận')

@push('styles')
<style>
  .moderation-filters{display:flex;gap:7px;flex-wrap:wrap}.moderation-filter{padding:7px 11px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:800;border:1px solid var(--admin-border);color:var(--admin-text-muted);background:rgba(255,255,255,.04)}.moderation-filter.active{border-color:var(--admin-primary);color:var(--admin-primary);background:rgba(108,99,255,.15)}
  .moderation-user{display:flex;align-items:center;gap:9px}.moderation-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6c63ff,#ff2a6d);font-weight:800;color:#fff}.moderation-content{max-width:420px;white-space:normal;line-height:1.55}.moderation-actions{display:flex;gap:5px;justify-content:flex-end;flex-wrap:wrap}.bulk-bar{display:none;align-items:center;gap:8px;flex-wrap:wrap;padding:12px 14px;background:rgba(108,99,255,.09);border:1px solid rgba(108,99,255,.25);border-radius:10px;margin-bottom:14px}.bulk-bar.show{display:flex}.bulk-action-btn{padding:7px 11px;border-radius:7px;border:1px solid var(--admin-border);background:rgba(255,255,255,.06);color:var(--admin-text);font-weight:700;cursor:pointer}.bulk-action-btn.approve{color:#4ade80;border-color:rgba(34,197,94,.3)}.bulk-action-btn.hide{color:#fbbf24;border-color:rgba(245,158,11,.3)}.bulk-action-btn.delete{color:#f87171;border-color:rgba(239,68,68,.3)}
</style>
@endpush

@section('content')
<div class="admin-page-header"><h1 class="admin-page-title">💬 Quản lý & Kiểm duyệt Bình luận</h1><p class="admin-page-sub">Duyệt, ẩn, xóa mềm, khóa nhanh tài khoản vi phạm và xử lý hàng loạt.</p></div>

<div class="admin-stats-row">
  @foreach([
    ['all','Tổng bình luận',$stats['total'],'primary'],['approved','Đã duyệt',$stats['approved'],''],['pending','Chờ duyệt',$stats['pending'],''],['hidden','Đã ẩn',$stats['hidden'],''],['reported','Bị báo cáo',$stats['reported'],''],['trashed','Thùng rác',$stats['trashed'],'']
  ] as [$key,$label,$value,$class])
    <a href="{{ route('admin.comments.index',['status'=>$key]) }}" class="admin-stat-card" style="text-decoration:none;color:inherit;{{ $statusFilter===$key?'outline:2px solid var(--admin-primary);':'' }}"><div class="admin-stat-label">{{ $label }}</div><div class="admin-stat-value {{ $class }}">{{ number_format($value) }}</div></a>
  @endforeach
</div>

<div class="admin-card" style="margin-bottom:18px;padding:16px 18px;">
  <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <div class="moderation-filters">
      @foreach(['all'=>'Tất cả','approved'=>'🟢 Đã duyệt','pending'=>'🟡 Chờ duyệt','hidden'=>'🔒 Đã ẩn','reported'=>'🚨 Bị báo cáo','trashed'=>'🗑️ Thùng rác'] as $key=>$label)
        <a class="moderation-filter {{ $statusFilter===$key?'active':'' }}" href="{{ route('admin.comments.index',['status'=>$key,'search'=>request('search')]) }}">{{ $label }}</a>
      @endforeach
    </div>
    <form method="GET" action="{{ route('admin.comments.index') }}" style="display:flex;gap:7px;align-items:center;">
      <input type="hidden" name="status" value="{{ $statusFilter }}"><input class="form-control" style="width:270px" name="search" value="{{ request('search') }}" placeholder="Tìm user, nội dung, truyện..."><button class="btn-admin btn-admin-primary btn-sm" type="submit">🔍 Tìm</button>@if(request('search'))<a class="btn-admin btn-admin-ghost btn-sm" href="{{ route('admin.comments.index',['status'=>$statusFilter]) }}">✕</a>@endif
    </form>
  </div>
</div>

<div class="admin-card">
  <form id="bulk-comment-form" method="POST" action="{{ route('admin.comments.bulk') }}">@csrf
    <input type="hidden" name="action" id="bulk-comment-action">
    <div id="bulk-bar" class="bulk-bar"><strong id="bulk-count">0 đã chọn</strong><span style="color:var(--admin-text-muted);font-size:12px;">Thao tác:</span><button type="button" class="bulk-action-btn approve" data-bulk="approve">✓ Duyệt</button><button type="button" class="bulk-action-btn hide" data-bulk="hide">🙈 Ẩn</button><button type="button" class="bulk-action-btn delete" data-bulk="delete">🗑️ Xóa mềm</button><button type="button" class="bulk-action-btn" id="bulk-clear">Bỏ chọn</button></div>

    <div style="overflow-x:auto;"><table class="admin-table"><thead><tr><th style="width:38px"><input type="checkbox" id="select-all-comments"></th><th>Độc giả</th><th>Nội dung</th><th>Vị trí</th><th style="text-align:center">Trạng thái</th><th style="text-align:center">Thời gian</th><th style="text-align:right">Thao tác</th></tr></thead><tbody>
      @forelse($comments as $cmt)
      <tr>
        <td>@unless($cmt->trashed())<input type="checkbox" class="comment-check" name="ids[]" value="{{ $cmt->id }}">@endunless</td>
        <td><div class="moderation-user"><div class="moderation-avatar">{{ mb_strtoupper(mb_substr($cmt->user->name ?? 'G',0,1)) }}</div><div><strong>{{ $cmt->user->name ?? 'User đã xóa' }}</strong><div style="font-size:11px;color:var(--admin-text-muted)">{{ $cmt->user->email ?? '—' }}</div>@if($cmt->user?->isBanned())<span class="badge badge-danger">🚫 Bị khóa</span>@elseif($cmt->user?->isAdmin())<span class="badge badge-primary">⭐ Admin</span>@endif</div></div></td>
        <td><div class="moderation-content">@if($cmt->parent)<div style="font-size:11px;color:var(--admin-primary);margin-bottom:4px;">↳ Trả lời {{ $cmt->parent->user->name ?? 'thành viên' }}</div>@endif{{ $cmt->content }}@if($cmt->reports?->isNotEmpty())<div style="font-size:11px;color:var(--admin-danger);margin-top:5px;">⚠️ {{ $cmt->reports->count() }} báo cáo</div>@endif<div style="font-size:11px;color:var(--admin-text-muted);margin-top:5px;">❤️ {{ number_format($cmt->likes_count ?? 0) }}</div></div></td>
        <td>@if($cmt->comic)<a href="{{ route('comics.show',$cmt->comic->slug) }}" target="_blank" style="color:var(--admin-primary);font-weight:700;text-decoration:none;">{{ Str::limit($cmt->comic->title,28) }}</a><div style="font-size:11px;color:var(--admin-text-muted);margin-top:3px;">{{ $cmt->chapter?'Ch.'.$cmt->chapter->chapter_number:'Trang chi tiết' }}</div>@else<span style="color:var(--admin-text-muted)">Truyện đã xóa</span>@endif</td>
        <td style="text-align:center">@if($cmt->trashed())<span class="badge badge-muted">🗑️ Đã xóa</span>@elseif($cmt->status==='approved')<span class="badge badge-success">🟢 Approved</span>@elseif($cmt->status==='pending')<span class="badge badge-warning">🟡 Pending</span>@elseif($cmt->status==='spam')<span class="badge badge-danger">🚨 Spam</span>@else<span class="badge badge-muted">🔒 Hidden</span>@endif</td>
        <td style="text-align:center;color:var(--admin-text-muted);font-size:12px;">{{ $cmt->time_ago }}</td>
        <td><div class="moderation-actions">@if($cmt->trashed())<form method="POST" action="{{ route('admin.comments.restore',$cmt->id) }}">@csrf<button class="btn-admin btn-admin-primary btn-sm">♻️ Khôi phục</button></form>@else @if($cmt->status!=='approved')<form method="POST" action="{{ route('admin.comments.approve',$cmt) }}">@csrf @method('PATCH')<button class="btn-admin btn-admin-success btn-sm">✓ Duyệt</button></form>@endif @if($cmt->status!=='hidden')<form method="POST" action="{{ route('admin.comments.hide',$cmt) }}">@csrf @method('PATCH')<button class="btn-admin btn-admin-ghost btn-sm">🙈 Ẩn</button></form>@endif @if($cmt->user && !$cmt->user->isAdmin() && !$cmt->user->isBanned())<form method="POST" action="{{ route('admin.comments.banUser',$cmt) }}" onsubmit="return confirm('Khóa tài khoản {{ addslashes($cmt->user->name) }}?')">@csrf<button class="btn-admin btn-admin-ghost btn-sm" style="color:#f59e0b">🔒 Khóa user</button></form>@endif<form method="POST" action="{{ route('admin.comments.destroy',$cmt) }}" onsubmit="return confirm('Xóa mềm bình luận này?')">@csrf @method('DELETE')<button class="btn-admin btn-admin-danger btn-sm">🗑️</button></form>@endif</div></td>
      </tr>
      @empty<tr><td colspan="7"><div style="text-align:center;padding:48px;color:var(--admin-text-muted)">💬 Không tìm thấy bình luận phù hợp.</div></td></tr>@endforelse
    </tbody></table></div>
  </form>
  <div class="pagination-wrap">{{ $comments->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const checks=[...document.querySelectorAll('.comment-check')], all=document.getElementById('select-all-comments'), bar=document.getElementById('bulk-bar'), count=document.getElementById('bulk-count'), form=document.getElementById('bulk-comment-form'), action=document.getElementById('bulk-comment-action');
  const sync=()=>{const n=checks.filter(c=>c.checked).length;count.textContent=`${n} đã chọn`;bar.classList.toggle('show',n>0);if(all)all.checked=n>0&&n===checks.length;};
  checks.forEach(c=>c.addEventListener('change',sync)); all?.addEventListener('change',()=>{checks.forEach(c=>c.checked=all.checked);sync();}); document.getElementById('bulk-clear')?.addEventListener('click',()=>{checks.forEach(c=>c.checked=false);if(all)all.checked=false;sync();});
  document.querySelectorAll('[data-bulk]').forEach(btn=>btn.addEventListener('click',()=>{const selected=checks.filter(c=>c.checked).length;if(!selected)return;const verb={approve:'duyệt',hide:'ẩn',delete:'xóa mềm'}[btn.dataset.bulk];if(!confirm(`${verb} ${selected} bình luận đã chọn?`))return;action.value=btn.dataset.bulk;form.submit();}));
})();
</script>
@endpush
