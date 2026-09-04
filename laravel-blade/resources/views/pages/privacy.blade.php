@extends('layouts.main')

@section('title', 'Chính Sách Riêng Tư - WebComics')

@section('meta')
<meta name="description" content="Chính sách riêng tư và dữ liệu người dùng trên WebComics." />
@endsection

@section('content')
<main class="page-container">
  <div class="container roadmap-static-page">
    <div class="page-header">
      <div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Riêng Tư</span></div>
      <h1 class="page-title">Chính Sách Riêng Tư</h1>
      <p class="page-subtitle">Tóm tắt cách WebComics sử dụng dữ liệu cần thiết để vận hành tài khoản và trải nghiệm đọc.</p>
    </div>

    <section class="roadmap-info-card"><h2>Dữ liệu tài khoản</h2><p>Hệ thống có thể lưu thông tin đăng ký, lịch sử đọc, tủ truyện, lượt thích, đánh giá, bình luận, thông báo và các thiết lập bảo mật gắn với tài khoản.</p></section>
    <section class="roadmap-info-card"><h2>Dữ liệu trình duyệt</h2><p>Một số thiết lập đọc, lịch sử tìm kiếm và lịch sử đọc của khách có thể được lưu cục bộ trên thiết bị để cải thiện trải nghiệm. Người dùng có thể xóa dữ liệu trình duyệt bất kỳ lúc nào.</p></section>
    <section class="roadmap-info-card"><h2>Bảo mật</h2><p>WebComics sử dụng các cơ chế như xác thực, xác minh email, 2FA, giới hạn tần suất và quản lý phiên để giảm rủi ro truy cập trái phép.</p></section>
    <section class="roadmap-info-card"><h2>Liên hệ</h2><p>Nếu cần yêu cầu hỗ trợ về dữ liệu hoặc quyền riêng tư, sử dụng trang liên hệ của hệ thống.</p><a href="{{ route('pages.contact') }}">Mở trang liên hệ →</a></section>
  </div>
</main>
@endsection
