@extends('layouts.main')
@section('title','Truyện yêu thích - WebComics')
@section('content')
<main class="page-container member-page"><div class="container member-shell">
    <h1 class="member-title">Truyện yêu thích</h1><p class="member-subtitle">Những bộ truyện bạn đã lưu bằng nút yêu thích.</p>
    @include('user._nav')
    <div class="comics-grid">
        @forelse($likes as $item)
            @if($item->comic)
            @include('partials.comic-card', ['comic' => $item->comic])
            @endif
        @empty
            <div class="empty-state"><span aria-hidden="true">♡</span><strong>Chưa có truyện yêu thích</strong><p>Thả tim cho một bộ truyện để lưu vào đây.</p><a href="{{ route('genres') }}" class="btn btn-download">Khám phá truyện</a></div>
        @endforelse
    </div>
    <div style="margin-top:22px;">{{ $likes->links() }}</div>
</div></main>
@endsection
