@extends('layouts.main')
@section('title','Đánh giá của tôi - WebComics')
@section('content')
<main class="page-container"><div class="container" style="padding-top:32px;padding-bottom:56px;">
    <h1 style="margin-bottom:8px;">⭐ Đánh giá của tôi</h1><p style="color:var(--text-sub);margin-top:0;">Toàn bộ số sao và nhận xét m đã gửi.</p>
    @include('user._nav')
    <div style="display:grid;gap:12px;">
        @forelse($ratings as $rating)
            @if($rating->comic)
            <article style="display:flex;gap:14px;background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:14px;align-items:flex-start;">
                <a href="{{ route('comics.show',$rating->comic->slug) }}"><img src="{{ $rating->comic->cover_image }}" alt="{{ $rating->comic->title }}" style="width:62px;height:84px;object-fit:cover;border-radius:9px;"></a>
                <div style="flex:1;min-width:0;"><div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;"><a href="{{ route('comics.show',$rating->comic->slug) }}" style="font-weight:800;color:inherit;text-decoration:none;">{{ $rating->comic->title }}</a><span style="color:#f59e0b;font-weight:900;">{{ str_repeat('★',(int)round($rating->score)) }}{{ str_repeat('☆',5-(int)round($rating->score)) }}</span></div>@if($rating->review)<p style="line-height:1.6;margin:10px 0;color:var(--text-main);">{{ $rating->review }}</p>@endif<div style="font-size:11px;color:var(--text-sub);">{{ $rating->updated_at?->diffForHumans() }}</div></div>
            </article>
            @endif
        @empty
            <div style="padding:42px;text-align:center;border:1px dashed var(--border);border-radius:14px;color:var(--text-sub);">Chưa có đánh giá nào.</div>
        @endforelse
    </div>
    <div style="margin-top:22px;">{{ $ratings->links() }}</div>
</div></main>
@endsection
