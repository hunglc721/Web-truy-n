@extends('layouts.main')
@section('title','Đánh giá của tôi - WebComics')
@section('content')
<main class="page-container member-page"><div class="container member-shell">
    <h1 class="member-title">Đánh giá của tôi</h1><p class="member-subtitle">Toàn bộ số sao và nhận xét bạn đã gửi.</p>
    @include('user._nav')
    <div class="member-list">
        @forelse($ratings as $rating)
            @if($rating->comic)
            <article class="member-list-card member-rating-card">
                <a href="{{ route('comics.show',$rating->comic->slug) }}"><img src="{{ $rating->comic->cover_url }}" alt="{{ $rating->comic->title }}" style="width:62px;height:84px;object-fit:cover;border-radius:9px;"></a>
                <div style="flex:1;min-width:0;"><div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;"><a href="{{ route('comics.show',$rating->comic->slug) }}" style="font-weight:800;color:inherit;text-decoration:none;">{{ $rating->comic->title }}</a><span style="color:#f59e0b;font-weight:900;">{{ str_repeat('★',(int)round($rating->score)) }}{{ str_repeat('☆',5-(int)round($rating->score)) }}</span></div>@if($rating->review)<p style="line-height:1.6;margin:10px 0;color:var(--text-main);">{{ $rating->review }}</p>@endif<div style="font-size:11px;color:var(--text-sub);">{{ $rating->updated_at?->diffForHumans() }}</div></div>
            </article>
            @endif
        @empty
            <div class="empty-state"><span aria-hidden="true">☆</span><strong>Chưa có đánh giá</strong><p>Điểm số và nhận xét của bạn sẽ xuất hiện ở đây.</p></div>
        @endforelse
    </div>
    <div style="margin-top:22px;">{{ $ratings->links() }}</div>
</div></main>
@endsection
