@extends('layouts.main')

@section('title', 'Giới Thiệu - WebComics')

@section('meta')
<meta name="description" content="Giới thiệu nền tảng đọc truyện WebComics." />
@endsection

@section('content')
<main class="page-container">
  <div class="container roadmap-static-page">
    <div class="page-header">
      <div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Giới Thiệu</span></div>
      <h1 class="page-title">Giới Thiệu WebComics</h1>
      <p class="page-subtitle">Một nền tảng đọc Manga, Manhwa, Manhua và Webtoon tập trung vào trải nghiệm đọc miễn phí, nhanh và dễ dùng trên cả PC lẫn mobile.</p>
    </div>

    <section class="roadmap-info-card">
      <h2>Mục tiêu</h2>
      <p>WebComics giúp người đọc khám phá truyện mới, theo dõi bộ truyện yêu thích, tiếp tục từ vị trí đang đọc, xem lịch cập nhật và tương tác qua bình luận, đánh giá.</p>
    </section>

    <section class="roadmap-info-card">
      <h2>Nguyên tắc nội dung</h2>
      <p>Nội dung công khai được hiển thị theo trạng thái phát hành. Truyện và chapter chưa tới thời điểm phát hành không được mở cho người đọc thông thường.</p>
    </section>

    <section class="roadmap-info-card">
      <h2>Đăng truyện</h2>
      <p>Tác giả và nhóm dịch có thể gửi yêu cầu đăng truyện để ban quản trị thẩm định trước khi nội dung xuất hiện trên hệ thống.</p>
      <a class="btn-spotlight-read roadmap-inline-action" href="{{ route('publish.create') }}">Gửi yêu cầu đăng truyện</a>
    </section>
  </div>
</main>
@endsection
