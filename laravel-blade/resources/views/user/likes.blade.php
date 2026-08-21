@extends('layouts.main')
@section('title','Truyện yêu thích - WebComics')
@section('content')
<main class="page-container"><div class="container" style="padding-top:32px;padding-bottom:56px;">
    <h1 style="margin-bottom:8px;">❤️ Truyện yêu thích</h1><p style="color:var(--text-sub);margin-top:0;">Danh sách những bộ m đã bấm tim, vì bộ não con người apparently cần một nút để nhớ mình thích gì.</p>
    @include('user._nav')
    <div class="comics-grid">
        @forelse($likes as $item)
            @if($item->comic)
            <a href="{{ route('comics.show',$item->comic->slug) }}" class="comic-card-sm" style="position:relative;">
                <div class="sm-cover"><img src="{{ $item->comic->cover_image }}" alt="{{ $item->comic->title }}" class="cover-img"><span class="sm-badge">★ {{ number_format($item->comic->avg_rating,1) }}</span></div>
                <p class="sm-title">{{ $item->comic->title }}</p><p class="sm-meta">{{ ucfirst($item->comic->status) }} · {{ $item->liked_at?->diffForHumans() }}</p>
            </a>
            @endif
        @empty
            <div style="grid-column:1/-1;padding:42px;text-align:center;border:1px dashed var(--border);border-radius:14px;color:var(--text-sub);">Chưa có truyện yêu thích.</div>
        @endforelse
    </div>
    <div style="margin-top:22px;">{{ $likes->links() }}</div>
</div></main>
@endsection
