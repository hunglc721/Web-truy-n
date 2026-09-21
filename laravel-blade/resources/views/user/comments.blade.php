@extends('layouts.main')
@section('title','Bình luận của tôi - WebComics')
@section('content')
<main class="page-container member-page"><div class="container member-shell">
    <h1 class="member-title">Bình luận của tôi</h1><p class="member-subtitle">Theo dõi nội dung đã viết và trạng thái kiểm duyệt.</p>
    @include('user._nav')
    <div class="member-list">
        @forelse($comments as $comment)
            <article class="member-list-card member-comment-card">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                    <div>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            @if($comment->comic)<a href="{{ route('comics.show',$comment->comic->slug) }}" style="font-weight:800;color:inherit;text-decoration:none;">{{ $comment->comic->title }}</a>@else<span>Truyện đã bị xóa</span>@endif
                            @if($comment->chapter)<span style="font-size:11px;color:var(--text-sub);">Ch.{{ $comment->chapter->chapter_number }}</span>@endif
                        </div>
                        @if($comment->parent)<div style="font-size:11px;color:var(--text-sub);margin-top:5px;">↳ Trả lời {{ $comment->parent->user?->name ?? 'độc giả' }}</div>@endif
                    </div>
                    @php($statusStyle = match($comment->status){'approved'=>'#22c55e','pending'=>'#f59e0b','spam'=>'#ef4444','hidden'=>'#94a3b8',default=>'#94a3b8'})
                    <span style="font-size:11px;font-weight:800;color:{{ $statusStyle }};border:1px solid {{ $statusStyle }}55;border-radius:999px;padding:4px 9px;">{{ strtoupper($comment->status) }}</span>
                </div>
                <p style="line-height:1.65;margin:12px 0 10px;">{{ $comment->content }}</p>
                <div style="font-size:11px;color:var(--text-sub);">❤️ {{ number_format($comment->likes_count ?? 0) }} · {{ $comment->created_at?->diffForHumans() }}</div>
            </article>
        @empty
            <div class="empty-state"><span aria-hidden="true">💬</span><strong>Chưa có bình luận</strong><p>Bình luận của bạn tại trang truyện sẽ xuất hiện ở đây.</p></div>
        @endforelse
    </div>
    <div style="margin-top:22px;">{{ $comments->links() }}</div>
</div></main>
@endsection
