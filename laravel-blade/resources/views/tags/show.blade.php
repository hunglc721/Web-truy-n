@extends('layouts.main')

@section('title', '#' . $tag->name . ' - WebComics')

@section('meta')
<meta name="description" content="Khám phá các bộ truyện thuộc tag {{ $tag->name }} trên WebComics." />
@endsection

@section('content')
<main class="page-container discovery-page">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Tag</span> &rsaquo; <strong>#{{ $tag->name }}</strong></div>
      <h1 class="page-title">#{{ $tag->name }}</h1>
      <p class="page-subtitle">{{ number_format($comics->total()) }} truyện đang có tag này.</p>
    </div>

    @if($comics->count())
      <div class="comics-grid discovery-results-grid">
        @foreach($comics as $comic)
          @include('partials.comic-card', ['comic' => $comic])
        @endforeach
      </div>

      <div style="margin-top:28px">{{ $comics->links() }}</div>
    @else
      <div class="roadmap-empty-state">
        <strong>Chưa có truyện nào cho tag #{{ $tag->name }}.</strong>
      </div>
    @endif
  </div>
</main>
@endsection
