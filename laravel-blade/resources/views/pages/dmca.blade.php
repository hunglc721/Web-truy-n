@extends('layouts.main')

@section('title', 'Chính Sách Bản Quyền DMCA - WebComics')

@section('content')
<main class="page-container">
  <div class="container" style="max-width: 900px;">
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Bản Quyền DMCA</span>
      </div>
      <h1 class="page-title" style="font-size: 28px; font-weight: 900; color: #fff; margin-top: 10px;">⚖️ Chính Sách Bản Quyền & Khiếu Nại DMCA</h1>
      <p style="color: var(--text-sub); font-size: 14px; margin-top: 6px;">WebComics luôn tôn trọng quyền sở hữu trí tuệ của các tác giả, họa sĩ và nhà xuất bản.</p>
    </div>

    @if(session('success'))
      <div style="background: rgba(22, 163, 74, 0.15); border: 1px solid #16a34a; border-radius: 12px; padding: 16px 20px; color: #4ade80; font-weight: 700; margin-bottom: 25px;">
        ✓ {{ session('success') }}
      </div>
    @endif

    <!-- Policy Introduction -->
    <div style="background: rgba(19, 22, 30, 0.85); border: 1px solid var(--border); border-radius: 14px; padding: 24px; margin-bottom: 30px; line-height: 1.7; color: var(--text-main); font-size: 14px;">
      <h2 style="font-size: 18px; font-weight: 800; color: #fff; margin-top: 0;">1. Tuyên Bố Về Bản Quyền Kỹ Thuật Số (DMCA Notice)</h2>
      <p>
        Toàn bộ nội dung truyện tranh trên nền tảng WebComics được sưu tầm, chia sẻ bởi cộng đồng và các nhóm dịch độc lập nhằm mục đích phi thương mại và học tập ngôn ngữ. Chúng tôi cam kết tuân thủ nghiêm ngặt theo Đạo luật Bản quyền Kỹ thuật số Thiên niên kỷ (<strong>Digital Millennium Copyright Act — DMCA</strong>).
      </p>
      <p>
        Nếu bạn là chủ sở hữu hợp pháp của bất kỳ tác phẩm nào xuất hiện trên trang web và không muốn tác phẩm được hiển thị, hoặc phát hiện nội dung vi phạm bản quyền của bạn, vui lòng điền vào biểu mẫu thông báo gỡ bỏ dưới đây. Ban quản trị sẽ tiến hành xác minh và gỡ bỏ tác phẩm trong vòng <strong>24 – 48 giờ làm việc</strong>.
      </p>
    </div>

    <!-- DMCA Takedown Notice Form -->
    <div style="background: rgba(19, 22, 30, 0.95); border: 1px solid var(--border); border-radius: 16px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.6);">
      <h2 style="font-size: 20px; font-weight: 800; color: #fff; margin-top: 0; margin-bottom: 8px;">📝 Biểu Mẫu Yêu Cầu Gỡ Bỏ Bản Quyền</h2>
      <p style="color: var(--text-sub); font-size: 13px; margin-bottom: 24px;">Các trường đánh dấu (<span style="color: #ef4444;">*</span>) là bắt buộc.</p>

      <form action="{{ route('dmca.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div>
            <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
              Họ và tên của bạn <span style="color: #ef4444;">*</span>
            </label>
            <input type="text" name="full_name" value="{{ old('full_name') }}" required placeholder="VD: Nguyễn Văn A" style="
              width: 100%;
              background: rgba(255,255,255,0.06);
              border: 1px solid var(--border);
              border-radius: 8px;
              color: #fff;
              padding: 10px 14px;
              font-size: 14px;
              outline: none;
            ">
            @error('full_name') <span style="color: #ef4444; font-size: 12px;">{{ $message }}</span> @enderror
          </div>

          <div>
            <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
              Email liên hệ nhận phản hồi <span style="color: #ef4444;">*</span>
            </label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="VD: contact@author.com" style="
              width: 100%;
              background: rgba(255,255,255,0.06);
              border: 1px solid var(--border);
              border-radius: 8px;
              color: #fff;
              padding: 10px 14px;
              font-size: 14px;
              outline: none;
            ">
            @error('email') <span style="color: #ef4444; font-size: 12px;">{{ $message }}</span> @enderror
          </div>
        </div>

        <div style="margin-bottom: 16px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Đơn vị / Tổ chức sở hữu (Tùy chọn)
          </label>
          <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="VD: NXB Kim Đồng / KakaoPage Corp" style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            outline: none;
          ">
        </div>

        <div style="margin-bottom: 16px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Tên tác phẩm gốc bị vi phạm <span style="color: #ef4444;">*</span>
          </label>
          <input type="text" name="work_title" value="{{ old('work_title') }}" required placeholder="VD: Solo Leveling / Thám Tử Lừng Danh Conan" style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            outline: none;
          ">
          @error('work_title') <span style="color: #ef4444; font-size: 12px;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-bottom: 16px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Đường dẫn (URL) chứa nội dung vi phạm trên WebComics <span style="color: #ef4444;">*</span>
          </label>
          <input type="url" name="infringing_url" value="{{ old('infringing_url') }}" required placeholder="VD: https://webcomics.vn/truyen/solo-leveling" style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            outline: none;
          ">
          @error('infringing_url') <span style="color: #ef4444; font-size: 12px;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-bottom: 16px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Liên kết / Bằng chứng chứng minh quyền sở hữu hợp pháp <span style="color: #ef4444;">*</span>
          </label>
          <input type="text" name="original_work_proof" value="{{ old('original_work_proof') }}" required placeholder="VD: Link đăng ký bản quyền, trang xuất bản chính thức, hợp đồng phân phối..." style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            outline: none;
          ">
          @error('original_work_proof') <span style="color: #ef4444; font-size: 12px;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-bottom: 20px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 6px;">
            Thông tin chi tiết bổ sung (Tùy chọn)
          </label>
          <textarea name="details" rows="3" placeholder="Ghi chú thêm về phạm vi vi phạm (toàn bộ truyện hoặc các chương cụ thể)..." style="
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            outline: none;
            resize: vertical;
          ">{{ old('details') }}</textarea>
        </div>

        <div style="margin-bottom: 24px;">
          <label style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer; font-size: 13px; color: var(--text-sub); line-height: 1.5;">
            <input type="checkbox" name="good_faith_statement" value="1" required style="margin-top: 3px; accent-color: var(--primary);">
            <span>
              Tôi xin cam đoan rằng tôi là chủ sở hữu hợp pháp hoặc được ủy quyền hợp pháp bởi chủ sở hữu tác phẩm để thực hiện khiếu nại này, và mọi thông tin khai báo là hoàn toàn trung thực.
            </span>
          </label>
          @error('good_faith_statement') <span style="color: #ef4444; font-size: 12px;">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="btn-spotlight-read" style="
          width: 100%;
          padding: 12px 24px;
          font-size: 15px;
          font-weight: 800;
          border-radius: 8px;
          cursor: pointer;
        ">
          ⚖️ Gửi Yêu Cầu Khiếu Nại Bản Quyền
        </button>
      </form>
    </div>

  </div>
</main>
@endsection
