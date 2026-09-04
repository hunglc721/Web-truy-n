@extends('layouts.main')

@section('title', 'Liên Hệ - WebComics')

@section('meta')
<meta name="description" content="Liên hệ ban quản trị WebComics." />
@endsection

@section('content')
<main class="page-container">
  <div class="container roadmap-static-page">
    <div class="page-header">
      <div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Liên Hệ</span></div>
      <h1 class="page-title">Liên Hệ WebComics</h1>
      <p class="page-subtitle">Dùng form này cho hỗ trợ tài khoản, lỗi chức năng và phản hồi chung. Khiếu nại bản quyền nên gửi qua trang DMCA để có đủ thông tin xử lý.</p>
    </div>

    <form action="{{ route('pages.contact.submit') }}" method="POST" class="roadmap-contact-form roadmap-info-card">
      @csrf
      <div class="roadmap-form-grid">
        <label>Họ tên
          <input name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="120">
        </label>
        <label>Email
          <input name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="160">
        </label>
      </div>
      <label>Chủ đề
        <input name="subject" value="{{ old('subject') }}" required maxlength="180" placeholder="Ví dụ: Lỗi trang đọc truyện">
      </label>
      <label>Nội dung
        <textarea name="message" rows="7" minlength="10" maxlength="5000" required placeholder="Mô tả rõ vấn đề, URL truyện/chapter nếu có...">{{ old('message') }}</textarea>
      </label>

      @if($errors->any())
        <div class="roadmap-form-errors">
          @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
        </div>
      @endif

      <button type="submit" class="btn-spotlight-read">Gửi liên hệ</button>
      <a href="{{ route('dmca.show') }}" class="btn-spotlight-sub roadmap-inline-action">Bản quyền & DMCA</a>
    </form>
  </div>
</main>
@endsection
