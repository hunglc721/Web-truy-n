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
<main class="page-container member-page"><div class="container member-shell">
    <h1 class="member-title">Lịch sử đọc</h1><p class="member-subtitle">Tiếp tục đúng chương và vị trí bạn đã dừng.</p>
    @include('user._nav')
    <div style="display:flex;justify-content:flex-end;margin-bottom:14px;"><form method="POST" action="{{ route('history.clear') }}" onsubmit="return confirm('Xóa toàn bộ lịch sử đọc?')">@csrf @method('DELETE')<button class="btn-spotlight-sub" type="submit" style="color:#ef4444;">🗑️ Xóa lịch sử</button></form></div>
    <div class="member-list">
        @forelse($histories as $item)
            @if($item->comic && $item->chapter)
            <article class="user-history-card member-list-card">
                <img src="{{ $item->comic->cover_url }}" alt="{{ $item->comic->title }}" style="width:62px;height:84px;object-fit:cover;border-radius:9px;">
                <div style="min-width:0;flex:1;"><a href="{{ route('comics.show',$item->comic->slug) }}" style="font-size:16px;font-weight:800;text-decoration:none;color:inherit;">{{ $item->comic->title }}</a><div style="font-size:12px;color:var(--text-sub);margin-top:5px;">Chương {{ $item->chapter->chapter_number }} · {{ $item->last_read_at?->diffForHumans() }}</div><div style="height:7px;background:rgba(255,255,255,.07);border-radius:99px;overflow:hidden;margin-top:10px;"><div style="height:100%;width:{{ min(100,max(0,$item->scroll_percent ?? 0)) }}%;background:var(--primary);"></div></div></div>
                <a href="{{ route('chapters.show',[$item->comic->slug,$item->chapter->slug ?: ('chapter-' . $item->chapter->chapter_number)]) }}" class="btn-spotlight-read history-read-button" style="text-decoration:none;white-space:nowrap;">Đọc tiếp</a>
            </article>
            @endif
        @empty
            <div class="empty-state"><span aria-hidden="true">📖</span><strong>Lịch sử đọc đang trống</strong><p>Truyện bạn mở sẽ xuất hiện ở đây để đọc tiếp.</p><a href="{{ route('genres') }}" class="btn btn-download">Khám phá truyện</a></div>
        @endforelse
    </div>
    <div style="margin-top:22px;">{{ $histories->links() }}</div>
</div></main>
@endsection
