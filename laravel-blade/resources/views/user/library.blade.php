@extends('layouts.main')
@section('title','Tủ truyện - WebComics')

@section('content')
<main class="page-container"><div class="container" style="padding-top:32px;padding-bottom:56px;">
  <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:18px"><div><h1 style="margin:0 0 6px">📚 Tủ Truyện</h1><p style="margin:0;color:var(--text-sub)">Những bộ m đang theo dõi, lưu bằng tài khoản thật chứ không còn nằm trong localStorage của một chiếc trình duyệt cô đơn.</p></div><span id="library-total-label" data-total="{{ $libraries->total() }}" style="font-size:13px;color:var(--text-sub)">{{ $libraries->total() }} bộ truyện</span></div>
  @include('user._nav')

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:22px"><div style="padding:14px;background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:12px"><strong id="library-total-stat" style="font-size:22px;display:block">{{ number_format($stats['total_bookmarks'] ?? $libraries->total()) }}</strong><span style="font-size:11px;color:var(--text-sub)">Truyện đang theo dõi</span></div><div style="padding:14px;background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:12px"><strong style="font-size:22px;display:block">{{ number_format($stats['total_read_comics'] ?? 0) }}</strong><span style="font-size:11px;color:var(--text-sub)">Truyện từng đọc</span></div><div style="padding:14px;background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:12px"><strong style="font-size:15px;display:block;line-height:1.5">{{ collect($stats['top_genres'] ?? [])->join(' · ') ?: 'Chưa đủ dữ liệu' }}</strong><span style="font-size:11px;color:var(--text-sub)">Thể loại hay đọc</span></div></div>

  @if($libraries->isEmpty())
    <div style="text-align:center;padding:60px 20px;background:var(--bg-surface-1);border:1px dashed var(--border-color);border-radius:16px"><div style="font-size:52px">📚</div><h2 style="font-size:18px;margin:10px 0 6px">Tủ truyện đang trống</h2><p style="color:var(--text-sub);margin:0 0 18px">Mở một bộ truyện rồi bấm “Theo Dõi Truyện”.</p><a href="{{ route('genres') }}" class="btn-spotlight-read" style="text-decoration:none">Khám phá truyện</a></div>
  @else
    <div class="comics-grid" id="library-grid">
      @foreach($libraries as $item)
        @php($comic=$item->comic)
        @if($comic)
        <article class="comic-card-sm" id="library-item-{{ $comic->id }}" style="position:relative;transition:.25s">
          <a href="{{ route('comics.show',$comic->slug) }}" style="display:block;text-decoration:none;color:inherit"><div class="sm-cover"><img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy"><span class="sm-badge">★ {{ number_format($comic->avg_rating,1) }}</span></div><div class="sm-info"><h3 class="sm-title">{{ $comic->title }}</h3><div class="sm-meta"><span>{{ ucfirst($comic->status) }}</span><span>{{ $comic->latestChapter?->label ?? 'Đang cập nhật' }}</span></div></div></a>
          <button type="button" class="library-remove" data-comic="{{ $comic->id }}" data-title="{{ $comic->title }}" style="position:absolute;top:8px;right:8px;z-index:4;width:36px;height:36px;border-radius:50%;border:1px solid rgba(239,68,68,.4);background:rgba(11,14,20,.82);color:#f87171;cursor:pointer" aria-label="Bỏ theo dõi {{ $comic->title }}">✕</button>
          <div style="padding:0 12px 12px">@if($item->lastReadChapter)<a href="{{ route('chapters.show',[$comic->slug,$item->lastReadChapter->slug ?: ('chapter-' . $item->lastReadChapter->chapter_number)]) }}" class="btn-spotlight-read" style="display:block;text-align:center;text-decoration:none;padding:8px 10px;font-size:12px">📖 Đọc tiếp Ch.{{ $item->lastReadChapter->chapter_number }}</a>@else<a href="{{ route('comics.show',$comic->slug) }}" class="btn-spotlight-sub" style="display:block;text-align:center;text-decoration:none;padding:8px 10px;font-size:12px">Xem chi tiết</a>@endif</div>
        </article>
        @endif
      @endforeach
    </div>
    <div style="margin-top:24px">{{ $libraries->links() }}</div>
  @endif
</div></main>
@endsection

@push('scripts')
<script>
(() => {
  const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
  const totalLabel=document.getElementById('library-total-label');
  const totalStat=document.getElementById('library-total-stat');
  const updateTotal=()=>{
    if(!totalLabel)return;
    const current=Math.max(0,Number(totalLabel.dataset.total||0)-1);
    totalLabel.dataset.total=String(current);
    totalLabel.textContent=`${current} bộ truyện`;
    if(totalStat)totalStat.textContent=new Intl.NumberFormat('vi-VN').format(current);
  };

  document.getElementById('library-grid')?.addEventListener('click',async e=>{
    const btn=e.target.closest('.library-remove');if(!btn)return;e.preventDefault();e.stopPropagation();if(!confirm(`Bỏ theo dõi "${btn.dataset.title}"?`))return;btn.disabled=true;
    try{const res=await fetch(`/user/library/toggle/${encodeURIComponent(btn.dataset.comic)}`,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf}});const data=await res.json();if(!res.ok)throw new Error(data.message||'Không thể cập nhật Tủ Truyện.');const card=document.getElementById(`library-item-${btn.dataset.comic}`);if(card){card.style.opacity='0';card.style.transform='scale(.94)';setTimeout(()=>{card.remove();updateTotal()},220)}}catch(err){alert(err.message);btn.disabled=false}
  });
})();
</script>
@endpush
