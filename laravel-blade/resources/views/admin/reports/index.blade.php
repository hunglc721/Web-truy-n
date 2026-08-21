@extends('layouts.admin')
@section('title','Báo cáo lỗi')
@section('breadcrumb','Báo cáo lỗi')

@push('styles')
<style>
  .report-flow{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:13px 15px;border:1px solid var(--admin-border);background:rgba(255,255,255,.025);border-radius:10px;margin-bottom:18px;font-size:12px;color:var(--admin-text-muted)}
  .report-filter{display:flex;gap:7px;flex-wrap:wrap}.report-filter a{padding:7px 11px;border:1px solid var(--admin-border);border-radius:8px;text-decoration:none;color:var(--admin-text-muted);font-size:12px;font-weight:800;background:rgba(255,255,255,.04)}.report-filter a.active{border-color:var(--admin-primary);background:rgba(108,99,255,.15);color:var(--admin-primary)}
  .report-location{max-width:260px}.report-desc{max-width:360px;white-space:normal;line-height:1.5}.report-actions{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}.report-note{width:190px;min-height:62px}.report-type{font-size:11px;font-weight:800;padding:4px 8px;border-radius:999px;border:1px solid rgba(245,158,11,.3);color:#fbbf24;background:rgba(245,158,11,.12)}
</style>
@endpush

@section('content')
<div class="admin-page-header"><h1 class="admin-page-title">⚠️ Trung Tâm Xử Lý Báo Cáo</h1><p class="admin-page-sub">Theo dõi lỗi chapter, mở thẳng vị trí lỗi, ghi chú xử lý và chuyển trạng thái theo workflow thực.</p></div>

<div class="admin-stats-row">
  @foreach([['all','Tổng báo cáo',$stats['total'],'primary'],['pending','Chưa xử lý',$stats['pending'],''],['processing','Đang xử lý',$stats['processing'],''],['resolved','Đã khắc phục',$stats['resolved'],''],['dismissed','Đã bác bỏ',$stats['dismissed'],'']] as [$key,$label,$value,$class])
    <a class="admin-stat-card" href="{{ route('admin.reports.index',['status'=>$key]) }}" style="text-decoration:none;color:inherit;{{ $statusFilter===$key?'outline:2px solid var(--admin-primary);':'' }}"><div class="admin-stat-label">{{ $label }}</div><div class="admin-stat-value {{ $class }}">{{ number_format($value) }}</div></a>
  @endforeach
</div>

<div class="report-flow"><strong style="color:var(--admin-text)">Luồng xử lý:</strong><span class="badge badge-warning">1. Pending</span><span>→</span><span class="badge badge-info">2. Processing</span><span>→</span><span class="badge badge-success">3. Resolved</span><span>hoặc</span><span class="badge badge-muted">Dismissed</span></div>

<div class="admin-card" style="margin-bottom:18px;padding:16px 18px;">
  <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;">
    <div class="report-filter">@foreach(['all'=>'Tất cả','pending'=>'⏳ Chưa xử lý','processing'=>'🔄 Đang xử lý','resolved'=>'✅ Đã khắc phục','dismissed'=>'⚪ Bác bỏ'] as $key=>$label)<a class="{{ $statusFilter===$key?'active':'' }}" href="{{ route('admin.reports.index',['status'=>$key,'type'=>request('type'),'search'=>request('search')]) }}">{{ $label }}</a>@endforeach</div>
    <form method="GET" action="{{ route('admin.reports.index') }}" style="display:flex;gap:7px;align-items:center;flex-wrap:wrap;"><input type="hidden" name="status" value="{{ $statusFilter }}"><select name="type" class="form-control" style="width:175px"><option value="all">Tất cả loại lỗi</option><option value="broken_image" {{ $typeFilter==='broken_image'?'selected':'' }}>Ảnh hỏng</option><option value="wrong_order" {{ $typeFilter==='wrong_order'?'selected':'' }}>Sai thứ tự</option><option value="missing_page" {{ $typeFilter==='missing_page'?'selected':'' }}>Thiếu trang</option><option value="content_error" {{ $typeFilter==='content_error'?'selected':'' }}>Sai nội dung/dịch</option></select><input class="form-control" style="width:230px" name="search" value="{{ $search }}" placeholder="Truyện, mô tả, IP..."><button class="btn-admin btn-admin-primary btn-sm">🔍 Lọc</button>@if($search || ($typeFilter && $typeFilter!=='all'))<a class="btn-admin btn-admin-ghost btn-sm" href="{{ route('admin.reports.index',['status'=>$statusFilter]) }}">✕</a>@endif</form>
  </div>
</div>

<div class="admin-card"><div style="overflow-x:auto"><table class="admin-table"><thead><tr><th># / Thời gian</th><th>Vị trí lỗi</th><th>Loại & mô tả</th><th>Người báo</th><th style="text-align:center">Trạng thái</th><th>Ghi chú Admin</th><th style="text-align:right">Xử lý</th></tr></thead><tbody>
@forelse($reports as $rpt)
<tr>
  <td><strong style="color:var(--admin-primary);font-family:monospace">#RP-{{ str_pad($rpt->id,4,'0',STR_PAD_LEFT) }}</strong><div style="font-size:11px;color:var(--admin-text-muted);margin-top:4px">{{ $rpt->time_ago }}</div></td>
  <td class="report-location">@if($rpt->comic)<strong>{{ $rpt->comic->title }}</strong>@if($rpt->chapter)<div style="font-size:11px;color:var(--admin-text-muted);margin:4px 0">Ch.{{ $rpt->chapter->chapter_number }}{{ $rpt->page_number?' · Trang '.$rpt->page_number:'' }}</div><a href="{{ route('chapters.show',[$rpt->comic->slug,$rpt->chapter->slug]).($rpt->page_number?'#page-'.$rpt->page_number:'') }}" target="_blank" class="btn-admin btn-admin-ghost btn-sm">🎯 Mở vị trí lỗi ↗</a>@else<div style="font-size:11px;color:var(--admin-text-muted)">Trang chi tiết truyện</div>@endif @else<span style="color:var(--admin-text-muted)">Dữ liệu đã xóa</span>@endif</td>
  <td class="report-desc"><span class="report-type">{{ $rpt->type_label }}</span>@if($rpt->description)<div style="margin-top:7px">{{ $rpt->description }}</div>@endif @if($rpt->image_url)<a href="{{ $rpt->image_url }}" target="_blank" style="font-size:11px;color:var(--admin-primary)">Ảnh lỗi gốc ↗</a>@endif</td>
  <td>@if($rpt->user)<strong>{{ $rpt->user->name }}</strong><div style="font-size:11px;color:var(--admin-text-muted)">{{ $rpt->user->email }}</div>@else<span style="color:var(--admin-text-muted)">Khách</span>@endif @if($rpt->ip_address)<div style="font-size:10px;color:var(--admin-text-muted);font-family:monospace;margin-top:3px">{{ $rpt->ip_address }}</div>@endif</td>
  <td style="text-align:center">@if($rpt->status==='pending')<span class="badge badge-warning">⏳ Pending</span>@elseif($rpt->status==='processing')<span class="badge badge-info">🔄 Processing</span>@elseif($rpt->status==='resolved')<span class="badge badge-success">✅ Resolved</span>@else<span class="badge badge-muted">⚪ Dismissed</span>@endif</td>
  <td><form id="report-status-{{ $rpt->id }}" method="POST" action="{{ route('admin.reports.updateStatus',$rpt) }}">@csrf @method('PATCH')<textarea name="admin_note" class="form-control report-note" placeholder="Ghi chú xử lý...">{{ $rpt->admin_note }}</textarea></form></td>
  <td><div class="report-actions">@if($rpt->status!=='processing')<button form="report-status-{{ $rpt->id }}" name="status" value="processing" class="btn-admin btn-admin-ghost btn-sm" style="color:#60a5fa">🔄 Xử lý</button>@endif @if($rpt->status!=='resolved')<button form="report-status-{{ $rpt->id }}" name="status" value="resolved" class="btn-admin btn-admin-success btn-sm">✅ Xong</button>@endif @if($rpt->status!=='pending')<button form="report-status-{{ $rpt->id }}" name="status" value="pending" class="btn-admin btn-admin-ghost btn-sm">↩ Mở lại</button>@endif @if($rpt->status!=='dismissed')<button form="report-status-{{ $rpt->id }}" name="status" value="dismissed" class="btn-admin btn-admin-ghost btn-sm">Bác bỏ</button>@endif<form method="POST" action="{{ route('admin.reports.destroy',$rpt) }}" onsubmit="return confirm('Xóa báo cáo này?')">@csrf @method('DELETE')<button class="btn-admin btn-admin-danger btn-sm">🗑️</button></form></div></td>
</tr>
@empty<tr><td colspan="7"><div style="padding:48px;text-align:center;color:var(--admin-text-muted)">📭 Không có báo cáo phù hợp.</div></td></tr>@endforelse
</tbody></table></div><div class="pagination-wrap">{{ $reports->links() }}</div></div>
@endsection
