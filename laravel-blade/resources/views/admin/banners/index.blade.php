@extends('layouts.admin')
@section('title','Quản lý Banner')
@section('breadcrumb','Banner')

@push('styles')
<style>
.banner-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}.banner-card{background:var(--admin-card);border:1px solid var(--admin-border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column}.banner-img-wrap{height:155px;background:#0f1118;position:relative}.banner-img{width:100%;height:100%;object-fit:cover}.banner-body{padding:16px;display:flex;flex-direction:column;flex:1}.banner-title{font-size:15px;font-weight:800}.banner-link{font-size:11px;color:var(--admin-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:5px 0 12px}.banner-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:13px}.banner-meta>div{padding:9px;border-radius:8px;background:rgba(255,255,255,.035);border:1px solid var(--admin-border);font-size:11px;color:var(--admin-text-muted)}.banner-actions{display:flex;gap:6px;justify-content:space-between;align-items:center;margin-top:auto;padding-top:12px;border-top:1px solid var(--admin-border)}.banner-preview{display:none;width:100%;height:130px;object-fit:cover;border-radius:9px;margin-top:8px;border:1px solid var(--admin-border)}
</style>
@endpush

@section('content')
<div class="admin-page-header" style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap"><div><h1 class="admin-page-title">🖼️ Banner Hero Trang Chủ</h1><p class="admin-page-sub">Upload/URL ảnh, thời gian hiệu lực, thứ tự, bật tắt và theo dõi lượt click thật.</p></div><button type="button" class="btn-admin btn-admin-primary" onclick="openBannerModal()">+ Thêm Banner</button></div>

<div class="admin-stats-row">@foreach([['Tổng banner',$stats['total'],'primary'],['Đang bật',$stats['active'],''],['Đã tắt',$stats['inactive'],''],['Hết hạn',$stats['expired'],''],['Chờ lịch',$stats['scheduled'],'']] as [$label,$value,$class])<div class="admin-stat-card"><div class="admin-stat-label">{{ $label }}</div><div class="admin-stat-value {{ $class }}">{{ number_format($value) }}</div></div>@endforeach</div>

<div class="banner-grid">
@forelse($banners as $banner)
<article class="banner-card">
  <div class="banner-img-wrap"><img src="{{ $banner->display_image }}" alt="{{ $banner->title }}" class="banner-img"><span class="badge badge-primary" style="position:absolute;top:8px;left:8px">#{{ $banner->order }}</span><span style="position:absolute;top:8px;right:8px">@if(!$banner->is_active)<span class="badge badge-muted">⭕ Tắt</span>@elseif($banner->is_expired)<span class="badge badge-danger">⌛ Hết hạn</span>@elseif($banner->is_scheduled)<span class="badge badge-warning">⏰ Chờ lịch</span>@else<span class="badge badge-success">🟢 Hiển thị</span>@endif</span></div>
  <div class="banner-body"><div class="banner-title">{{ $banner->title }}</div><div class="banner-link">🔗 {{ $banner->link_url ?: 'Không gắn liên kết' }}</div>
    <div class="banner-meta"><div><strong style="display:block;color:var(--admin-text);font-size:15px">👆 {{ number_format($banner->clicks_count ?? 0) }}</strong>Lượt click</div><div><strong style="display:block;color:var(--admin-text);font-size:12px">{{ $banner->start_at?->format('d/m H:i') ?? 'Ngay' }}</strong>Bắt đầu</div><div><strong style="display:block;color:var(--admin-text);font-size:12px">{{ $banner->end_at?->format('d/m H:i') ?? 'Không hạn' }}</strong>Kết thúc</div><div><strong style="display:block;color:var(--admin-text);font-size:12px">{{ $banner->is_active?'Bật':'Tắt' }}</strong>Trạng thái</div></div>
    <div class="banner-actions"><form method="POST" action="{{ route('admin.banners.toggleActive',$banner) }}">@csrf @method('PATCH')<button class="btn-admin btn-sm {{ $banner->is_active?'btn-admin-success':'btn-admin-ghost' }}">{{ $banner->is_active?'🟢 BẬT':'⭕ TẮT' }}</button></form><div style="display:flex;gap:6px"><button type="button" class="btn-admin btn-admin-ghost btn-sm" data-edit-banner='@json(["id"=>$banner->id,"title"=>$banner->title,"image_url"=>$banner->image_url,"link_url"=>$banner->link_url,"order"=>$banner->order,"is_active"=>$banner->is_active,"start_at"=>$banner->start_at?->format("Y-m-d\\TH:i"),"end_at"=>$banner->end_at?->format("Y-m-d\\TH:i")])'>✏️ Sửa</button><form method="POST" action="{{ route('admin.banners.destroy',$banner) }}" onsubmit="return confirm('Xóa banner này?')">@csrf @method('DELETE')<button class="btn-admin btn-admin-danger btn-sm">🗑️</button></form></div></div>
  </div>
</article>
@empty<div style="grid-column:1/-1;text-align:center;padding:55px;border:1px dashed var(--admin-border);border-radius:14px;color:var(--admin-text-muted)">🖼️ Chưa có banner.</div>@endforelse
</div>

<div class="modal-overlay" id="banner-modal"><div class="modal-box" style="max-width:560px;text-align:left;max-height:90vh;overflow:auto"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px"><h3 class="modal-title" id="banner-modal-title" style="margin:0">Thêm Banner</h3><button type="button" class="btn-admin btn-admin-ghost btn-sm" onclick="closeBannerModal()">✕</button></div><form id="banner-form" method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">@csrf<div id="banner-method"></div>
<div class="form-group"><label class="form-label">Tiêu đề <span>*</span></label><input class="form-control" name="title" id="banner-title" required maxlength="255"></div>
<div class="form-group"><label class="form-label">Upload ảnh</label><input class="form-control" type="file" name="image" id="banner-image-file" accept="image/jpeg,image/png,image/webp,image/gif"><div class="form-hint">Hoặc dùng URL bên dưới. Tối đa 5MB.</div></div>
<div class="form-group"><label class="form-label">URL ảnh</label><input class="form-control" type="url" name="image_url" id="banner-image-url" placeholder="https://..."><img id="banner-preview" class="banner-preview" alt="Preview"></div>
<div class="form-group"><label class="form-label">Link khi click</label><input class="form-control" name="link_url" id="banner-link-url" placeholder="/truyen/... hoặc https://..."></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label class="form-label">Thứ tự</label><input class="form-control" type="number" min="0" name="order" id="banner-order" value="0"></div><div class="form-group"><label class="form-label">Hiển thị</label><select class="form-control" name="is_active" id="banner-active"><option value="1">Bật</option><option value="0">Tắt</option></select></div></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="form-group"><label class="form-label">Bắt đầu</label><input class="form-control" type="datetime-local" name="start_at" id="banner-start"></div><div class="form-group"><label class="form-label">Kết thúc</label><input class="form-control" type="datetime-local" name="end_at" id="banner-end"></div></div>
<div class="modal-actions"><button type="button" class="btn-admin btn-admin-ghost" onclick="closeBannerModal()">Hủy</button><button class="btn-admin btn-admin-primary">💾 Lưu Banner</button></div></form></div></div>
@endsection

@push('scripts')
<script>
(() => {
 const modal=document.getElementById('banner-modal'),form=document.getElementById('banner-form'),method=document.getElementById('banner-method'),preview=document.getElementById('banner-preview'),urlInput=document.getElementById('banner-image-url');
 window.openBannerModal=(data=null)=>{form.reset();form.action='{{ route('admin.banners.store') }}';method.innerHTML='';document.getElementById('banner-modal-title').textContent='🖼️ Thêm Banner';preview.style.display='none';if(data){document.getElementById('banner-modal-title').textContent='✏️ Sửa Banner';form.action=`{{ url('/admin/banners') }}/${data.id}`;method.innerHTML='<input type="hidden" name="_method" value="PUT">';document.getElementById('banner-title').value=data.title||'';urlInput.value=data.image_url||'';document.getElementById('banner-link-url').value=data.link_url||'';document.getElementById('banner-order').value=data.order||0;document.getElementById('banner-active').value=data.is_active?'1':'0';document.getElementById('banner-start').value=data.start_at||'';document.getElementById('banner-end').value=data.end_at||'';showPreview(data.image_url);}modal.style.display='flex';modal.classList.add('show');};
 window.closeBannerModal=()=>{modal.classList.remove('show');modal.style.display='none';};
 const showPreview=(u)=>{if(!u){preview.style.display='none';return;}preview.src=u.startsWith('http')?u:`{{ asset('storage') }}/${u.replace(/^\//,'')}`;preview.style.display='block';};
 urlInput.addEventListener('input',()=>showPreview(urlInput.value.trim()));document.getElementById('banner-image-file').addEventListener('change',function(){if(this.files?.[0]){preview.src=URL.createObjectURL(this.files[0]);preview.style.display='block';}});
 document.querySelectorAll('[data-edit-banner]').forEach(btn=>btn.addEventListener('click',()=>openBannerModal(JSON.parse(btn.dataset.editBanner))));modal.addEventListener('click',e=>{if(e.target===modal)closeBannerModal();});
})();
</script>
@endpush
