@extends('layouts.admin')

@section('title', 'Thẩm Định Đơn Đăng Truyện #' . $storyRequest->id)
@section('breadcrumb', 'Thẩm Định Đơn #' . $storyRequest->id)

@push('styles')
<style>
  .show-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 22px;
  }
  .detail-block {
    margin-bottom: 20px;
  }
  .detail-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--admin-text-muted);
    margin-bottom: 6px;
  }
  .detail-val {
    font-size: 14px;
    color: var(--admin-text);
    line-height: 1.6;
  }
  .genre-pill {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11.5px;
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--admin-border);
    margin-right: 5px;
    margin-bottom: 5px;
  }
  @media(max-width: 992px) {
    .show-grid { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('topbar-actions')
  <a href="{{ route('admin.storyRequests.index') }}" class="topbar-btn topbar-btn-ghost">← Quay lại danh sách</a>
@endsection

@section('content')
<div class="ph" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1>Thẩm Định Đơn Đăng Truyện #{{ $storyRequest->id }}</h1>
    <p>Tác phẩm: <strong>{{ $storyRequest->story_title }}</strong> · Gửi lúc {{ $storyRequest->created_at->format('H:i - d/m/Y') }}</p>
  </div>
  <div>
    <span class="badge {{ $storyRequest->status_badge_class }}" style="font-size: 13px; padding: 6px 14px;">
      @if($storyRequest->status === 'pending') ⏳ @elseif($storyRequest->status === 'reviewing') 🔍 @elseif($storyRequest->status === 'approved') ✅ @else ❌ @endif
      {{ $storyRequest->status_label }}
    </span>
  </div>
</div>

<div class="show-grid">
  {{-- Cột Trái: Chi tiết tác phẩm & Bản thảo --}}
  <div style="display: flex; flex-direction: column; gap: 20px;">
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">📖 Thông Tin Tác Phẩm</div>
      </div>

      <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
        @if($storyRequest->cover_image_path)
          <div style="flex-shrink: 0;">
            <img src="{{ asset('storage/' . $storyRequest->cover_image_path) }}" alt="Bìa tác phẩm" style="width: 140px; height: 195px; object-fit: cover; border-radius: 10px; border: 1px solid var(--admin-border); box-shadow: 0 4px 14px rgba(0,0,0,0.4);" />
          </div>
        @endif

        <div style="flex: 1; min-width: 240px;">
          <div class="detail-block">
            <div class="detail-label">Tên truyện</div>
            <div style="font-size: 20px; font-weight: 800; color: #fff;">{{ $storyRequest->story_title }}</div>
            @if($storyRequest->story_original_title)
              <div style="font-size: 13px; color: var(--admin-text-muted); margin-top: 2px;">Tên gốc: {{ $storyRequest->story_original_title }}</div>
            @endif
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="detail-block">
              <div class="detail-label">Loại hình tác phẩm</div>
              <div class="detail-val">
                <span class="badge badge-primary">{{ $storyRequest->story_type_label }}</span>
              </div>
            </div>

            <div class="detail-block">
              <div class="detail-label">Tình trạng bản thảo</div>
              <div class="detail-val">
                <span class="badge badge-info">{{ $storyRequest->story_status_label }}</span>
              </div>
            </div>
          </div>

          @if($storyRequest->genres && count($storyRequest->genres) > 0)
            <div class="detail-block">
              <div class="detail-label">Thể loại</div>
              <div>
                @foreach($storyRequest->genres as $genreName)
                  <span class="genre-pill">{{ $genreName }}</span>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>

      <div class="detail-block">
        <div class="detail-label">Tóm tắt nội dung cốt truyện</div>
        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--admin-border); border-radius: 9px; padding: 14px 16px; font-size: 14px; line-height: 1.65; white-space: pre-line;">
          {{ $storyRequest->summary }}
        </div>
      </div>

      @if($storyRequest->sample_link)
        <div class="detail-block">
          <div class="detail-label">🔗 Liên kết đọc thử / Google Drive bản thảo</div>
          <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ $storyRequest->sample_link }}" target="_blank" rel="noopener" class="btn-admin btn-admin-primary btn-sm" style="font-weight: 600;">
              Mở link bản thảo ↗
            </a>
            <span style="font-size: 12.5px; color: var(--admin-text-muted); word-break: break-all;">{{ $storyRequest->sample_link }}</span>
          </div>
        </div>
      @endif

      @if($storyRequest->sample_file_path)
        <div class="detail-block">
          <div class="detail-label">📁 Tệp bản thảo đính kèm</div>
          <div>
            <a href="{{ asset('storage/' . $storyRequest->sample_file_path) }}" download class="btn-admin btn-admin-ghost btn-sm">
              📥 Tải file bản thảo về máy (.{{ pathinfo($storyRequest->sample_file_path, PATHINFO_EXTENSION) }})
            </a>
          </div>
        </div>
      @endif

      @if($storyRequest->note)
        <div class="detail-block" style="margin-bottom: 0;">
          <div class="detail-label">💬 Lời nhắn của tác giả gửi BQT</div>
          <div style="background: rgba(108,99,255,0.06); border: 1px solid rgba(108,99,255,0.2); border-radius: 8px; padding: 12px 14px; font-size: 13.5px; color: var(--admin-text);">
            {{ $storyRequest->note }}
          </div>
        </div>
      @endif
    </div>
  </div>

  {{-- Cột Phải: Thông tin Người gửi & Thao tác Thẩm định --}}
  <div style="display: flex; flex-direction: column; gap: 20px;">
    {{-- Card Người gửi --}}
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">👤 Thông Tin Người Gửi</div>
      </div>

      <div class="detail-block">
        <div class="detail-label">Họ tên / Bút danh</div>
        <div style="font-size: 16px; font-weight: 700; color: #fff;">{{ $storyRequest->creator_name }}</div>
      </div>

      <div class="detail-block">
        <div class="detail-label">Email liên hệ</div>
        <div class="detail-val">
          <a href="mailto:{{ $storyRequest->email }}" style="color: var(--admin-primary); text-decoration: none;">
            {{ $storyRequest->email }}
          </a>
        </div>
      </div>

      <div class="detail-block">
        <div class="detail-label">SĐT / Zalo / Telegram / MXH</div>
        <div class="detail-val" style="font-weight: 600;">
          {{ $storyRequest->phone_or_social ?: 'Không cung cấp' }}
        </div>
      </div>

      @if($storyRequest->team_name)
        <div class="detail-block">
          <div class="detail-label">Tên Nhóm dịch / Studio</div>
          <div class="detail-val">{{ $storyRequest->team_name }}</div>
        </div>
      @endif

      <div class="detail-block">
        <div class="detail-label">Kinh nghiệm sáng tác / dịch thuật</div>
        <div class="detail-val">{{ $storyRequest->experience_label }}</div>
      </div>

      <div class="detail-block" style="border-top: 1px solid var(--admin-border); padding-top: 12px; margin-bottom: 0;">
        <div class="detail-label">Tài khoản User trên hệ thống</div>
        <div class="detail-val">
          @if($storyRequest->user)
            <a href="{{ route('admin.users.show', $storyRequest->user_id) }}" style="color: var(--admin-primary); font-weight: 600; text-decoration: none;">
              👤 {{ $storyRequest->user->name }} (ID: #{{ $storyRequest->user_id }})
            </a>
          @else
            <span style="color: var(--admin-text-muted); font-size: 12.5px;">Khách vãng lai (Chưa đăng nhập)</span>
          @endif
        </div>
      </div>
    </div>

    {{-- Card Thẩm định & Cập nhật trạng thái --}}
    <div class="admin-card" style="border-color: rgba(108,99,255,0.3);">
      <div class="admin-card-header">
        <div class="admin-card-title">⚡ Xử Lý & Phê Duyệt Đơn</div>
      </div>

      <form action="{{ route('admin.storyRequests.updateStatus', $storyRequest->id) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="form-group">
          <label class="form-label" for="status">Chọn trạng thái xử lý <span>*</span></label>
          <select id="status" name="status" class="form-control" required>
            <option value="pending" {{ $storyRequest->status === 'pending' ? 'selected' : '' }}>⏳ Chờ duyệt (Pending)</option>
            <option value="reviewing" {{ $storyRequest->status === 'reviewing' ? 'selected' : '' }}>🔍 Đang thẩm định (Reviewing)</option>
            <option value="approved" {{ $storyRequest->status === 'approved' ? 'selected' : '' }}>✅ Phê duyệt xuất bản (Approved)</option>
            <option value="rejected" {{ $storyRequest->status === 'rejected' ? 'selected' : '' }}>❌ Từ chối xuất bản (Rejected)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="admin_note">Ghi chú phản hồi cho tác giả</label>
          <textarea id="admin_note" name="admin_note" class="form-control" placeholder="Nhập lý do phê duyệt, hướng dẫn tạo truyện hoặc lý do từ chối gửi về cho tác giả..." style="min-height: 110px;">{{ old('admin_note', $storyRequest->admin_note) }}</textarea>
          <div class="form-hint">Nội dung này sẽ được gửi dưới dạng thông báo trong hệ thống đến tác giả.</div>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; justify-content: center;">
          💾 Cập Nhật Trạng Thái & Gửi Phản Hồi
        </button>
      </form>

      @if($storyRequest->reviewer)
        <div style="font-size: 11.5px; color: var(--admin-text-muted); margin-top: 14px; text-align: center; border-top: 1px solid var(--admin-border); padding-top: 10px;">
          Xử lý bởi: <strong>{{ $storyRequest->reviewer->name }}</strong> lúc {{ $storyRequest->reviewed_at?->format('H:i - d/m/Y') }}
        </div>
      @endif
    </div>

    {{-- Xóa đơn --}}
    <div style="display: flex; justify-content: flex-end;">
      <button type="button" class="btn-admin btn-admin-danger btn-sm" onclick="confirmDelete('{{ route('admin.storyRequests.destroy', $storyRequest->id) }}', 'Đơn đăng ký #{{ $storyRequest->id }}')">
        🗑️ Xóa đơn này
      </button>
    </div>
  </div>
</div>
@endsection
