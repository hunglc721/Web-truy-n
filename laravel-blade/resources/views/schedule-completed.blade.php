@extends('layouts.main')

@section('title', 'Truyện Đã Hoàn Thành - WebComics')

@section('meta')
<meta name="description" content="Danh sách truyện đã hoàn thành trên WebComics." />
@endsection

@section('content')
<main class="page-container discovery-page schedule-page">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <a href="{{ route('schedule') }}">Lịch Ra Truyện</a> &rsaquo; <span>Hoàn Thành</span></div>
      <h1 class="page-title">Truyện đã hoàn thành</h1>
      <p class="page-subtitle">Đọc liền mạch các bộ truyện đã phát hành đầy đủ.</p>
    </div>

    <div class="schedule-completed-nav">
      <a href="{{ route('schedule') }}" class="chip">← Lịch theo ngày</a>
      <span class="chip active">✅ Hoàn Thành</span>
    </div>

    @if($comics->count())
      <div class="comics-grid discovery-results-grid">
        @foreach($comics as $comic)
          @include('partials.comic-card', ['comic' => $comic])
        @endforeach
      </div>
      <div style="margin-top:28px">{{ $comics->links() }}</div>
    @else
      <div class="empty-state"><span aria-hidden="true">📚</span><strong>Chưa có truyện hoàn thành</strong><p>Các bộ đã hoàn tất sẽ xuất hiện tại đây.</p></div>
    @endif
  </div>
</main>
@endsection
