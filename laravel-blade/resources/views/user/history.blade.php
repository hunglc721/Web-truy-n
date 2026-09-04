@extends('layouts.main')
@section('title','Lịch sử đọc - WebComics')

@push('styles')
<style>
  @media (max-width: 600px) {
    .user-history-card { align-items:flex-start !important; flex-wrap:wrap; }
    .user-history-card > img { width:54px !important; height:74px !important; }
    .user-history-card .history-read-button { width:100%; justify-content:center; }
  }
</style>
@endpush

@section('content')
<main class="page-container"><div class="container" style="padding-top:32px;padding-bottom:56px;">
    <h1 style="margin-bottom:8px;">🕘 Lịch sử đọc</h1><p style="color:var(--text-sub);margin-top:0;">Tiếp tục đúng nơi m đã dừng, thay vì mở nhầm chapter rồi tự spoil mình.</p>
    @include('user._nav')
    <div style="display:flex;justify-content:flex-end;margin-bottom:14px;"><form method="POST" action="{{ route('history.clear') }}" onsubmit="return confirm('Xóa toàn bộ lịch sử đọc?')">@csrf @method('DELETE')<button class="btn-spotlight-sub" type="submit" style="color:#ef4444;">🗑️ Xóa lịch sử</button></form></div>
    <div style="display:grid;gap:12px;">
        @forelse($histories as $item)
            @if($item->comic && $item->chapter)
            <article class="user-history-card" style="display:flex;gap:14px;background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:14px;align-items:center;">
                <img src="{{ $item->comic->cover_image }}" alt="{{ $item->comic->title }}" style="width:62px;height:84px;object-fit:cover;border-radius:9px;">
                <div style="min-width:0;flex:1;"><a href="{{ route('comics.show',$item->comic->slug) }}" style="font-size:16px;font-weight:800;text-decoration:none;color:inherit;">{{ $item->comic->title }}</a><div style="font-size:12px;color:var(--text-sub);margin-top:5px;">Chương {{ $item->chapter->chapter_number }} · {{ $item->last_read_at?->diffForHumans() }}</div><div style="height:7px;background:rgba(255,255,255,.07);border-radius:99px;overflow:hidden;margin-top:10px;"><div style="height:100%;width:{{ min(100,max(0,$item->scroll_percent ?? 0)) }}%;background:var(--primary);"></div></div></div>
                <a href="{{ route('chapters.show',[$item->comic->slug,$item->chapter->slug ?: ('chapter-' . $item->chapter->chapter_number)]) }}" class="btn-spotlight-read history-read-button" style="text-decoration:none;white-space:nowrap;">Đọc tiếp</a>
            </article>
            @endif
        @empty
            <div style="padding:42px;text-align:center;border:1px dashed var(--border);border-radius:14px;color:var(--text-sub);">Chưa có lịch sử đọc.</div>
        @endforelse
    </div>
    <div style="margin-top:22px;">{{ $histories->links() }}</div>
</div></main>
@endsection
