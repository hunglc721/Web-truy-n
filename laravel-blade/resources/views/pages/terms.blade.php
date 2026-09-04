@extends('layouts.main')

@section('title', 'Điều Khoản Sử Dụng - WebComics')

@section('meta')
<meta name="description" content="Điều khoản sử dụng nền tảng WebComics." />
@endsection

@section('content')
<main class="page-container">
  <div class="container roadmap-static-page">
    <div class="page-header">
      <div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Điều Khoản</span></div>
      <h1 class="page-title">Điều Khoản Sử Dụng</h1>
      <p class="page-subtitle">Các nguyên tắc cơ bản khi sử dụng WebComics.</p>
    </div>

    <section class="roadmap-info-card"><h2>1. Tài khoản</h2><p>Người dùng chịu trách nhiệm bảo vệ thông tin đăng nhập và không sử dụng tài khoản để spam, quấy rối hoặc gây ảnh hưởng đến hoạt động của hệ thống.</p></section>
    <section class="roadmap-info-card"><h2>2. Nội dung và bình luận</h2><p>Không đăng nội dung trái pháp luật, xâm phạm quyền của người khác, chứa mã độc, spam liên kết hoặc thông tin cá nhân nhạy cảm của bên thứ ba.</p></section>
    <section class="roadmap-info-card"><h2>3. Bản quyền</h2><p>Tác giả, nhóm dịch và người gửi nội dung phải có quyền phù hợp đối với nội dung họ cung cấp. Khi có khiếu nại bản quyền, WebComics có thể tạm ẩn nội dung trong thời gian xác minh.</p><a href="{{ route('dmca.show') }}">Xem quy trình Bản quyền & DMCA →</a></section>
    <section class="roadmap-info-card"><h2>4. Thay đổi dịch vụ</h2><p>Chức năng có thể được bổ sung, điều chỉnh hoặc tạm dừng để bảo trì, bảo mật và cải thiện trải nghiệm người dùng.</p></section>
  </div>
</main>
@endsection
