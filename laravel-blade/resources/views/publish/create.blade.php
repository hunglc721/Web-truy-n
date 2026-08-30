@extends('layouts.main')

@section('title', 'Đăng Ký Đăng Truyện - Trở Thành Tác Giả / Đối Tác Xuất Bản')

@push('styles')
<style>
  .publish-hero {
    background: linear-gradient(135deg, rgba(255,94,54,0.12) 0%, rgba(255,42,109,0.08) 50%, rgba(13,15,20,0) 100%), var(--bg-surface-1);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 36px 32px;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
  }
  .publish-hero::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(255,94,54,0.25) 0%, transparent 70%);
    pointer-events: none;
  }
  .publish-hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,94,54,0.15);
    color: var(--primary);
    border: 1px solid rgba(255,94,54,0.3);
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
  }
  .publish-hero-title {
    font-size: 28px;
    font-weight: 900;
    color: var(--text-main);
    margin-bottom: 10px;
    line-height: 1.3;
  }
  .publish-hero-desc {
    color: var(--text-sub);
    font-size: 15px;
    max-width: 760px;
    line-height: 1.6;
    margin-bottom: 22px;
  }
  .publish-perks {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
  }
  .publish-perk-item {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
  }
  .publish-perk-icon {
    font-size: 22px;
    flex-shrink: 0;
  }
  .publish-perk-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 2px;
  }
  .publish-perk-text {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.4;
  }

  .publish-form-card {
    background: var(--bg-surface-1);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 28px 30px;
    margin-bottom: 24px;
  }
  .form-section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-color);
  }
  .form-section-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255,94,54,0.14);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 800;
  }
  .form-section-title {
    font-size: 17px;
    font-weight: 700;
    color: var(--text-main);
  }
  .form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
  }
  .form-group {
    margin-bottom: 18px;
  }
  .form-label {
    display: block;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-main);
    margin-bottom: 7px;
  }
  .form-label .req {
    color: #ef4444;
    margin-left: 3px;
  }
  .form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 11px 14px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    color: var(--text-main);
    font-family: inherit;
    font-size: 14px;
    outline: none;
    transition: all 0.2s;
  }
  .form-input:focus, .form-select:focus, .form-textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(255,94,54,0.2);
    background: rgba(255,255,255,0.07);
  }
  .form-select option {
    background: #171a22;
    color: #fff;
  }
  .form-textarea {
    resize: vertical;
    min-height: 120px;
    line-height: 1.5;
  }
  .form-help {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 5px;
  }
  .genre-tags-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 8px;
    max-height: 180px;
    overflow-y: auto;
    padding: 8px;
    background: rgba(0,0,0,0.2);
    border: 1px solid var(--border-color);
    border-radius: 10px;
  }
  .genre-tag-item {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12.5px;
    color: var(--text-sub);
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 6px;
    transition: background 0.15s;
  }
  .genre-tag-item:hover {
    background: rgba(255,255,255,0.05);
    color: var(--text-main);
  }
  .genre-tag-item input[type="checkbox"] {
    accent-color: var(--primary);
    width: 15px;
    height: 15px;
  }
  .upload-dropzone {
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    background: rgba(255,255,255,0.02);
    transition: all 0.2s;
    cursor: pointer;
  }
  .upload-dropzone:hover {
    border-color: var(--primary);
    background: rgba(255,94,54,0.04);
  }
  .cover-preview-img {
    max-width: 140px;
    max-height: 190px;
    border-radius: 8px;
    object-fit: cover;
    margin-top: 12px;
    border: 1px solid var(--border-color);
    display: none;
  }
  .btn-submit-publish {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 15px 28px;
    background: linear-gradient(135deg, #FF5E36 0%, #FF2A6D 100%);
    color: #fff;
    font-size: 16px;
    font-weight: 800;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 18px rgba(255,94,54,0.35);
  }
  .btn-submit-publish:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255,94,54,0.5);
  }

  @media(max-width: 768px) {
    .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
    .publish-hero { padding: 24px 18px; }
    .publish-hero-title { font-size: 22px; }
    .publish-form-card { padding: 20px 16px; }
  }
</style>
@endpush

@section('content')
<div class="container" style="padding-top: 24px; padding-bottom: 60px;">
  
  {{-- Hero Intro Card --}}
  <div class="publish-hero">
    <div class="publish-hero-tag">✨ CỔNG ĐỐI TÁC & TÁC GIẢ</div>
    <h1 class="publish-hero-title">Đăng Ký Đăng Truyện & Hợp Tác Sáng Tác</h1>
    <p class="publish-hero-desc">
      Chào mừng bạn đến với mạng lưới tác giả và nhóm dịch của WebComics! Hãy điền thông tin tác phẩm của bạn vào biểu mẫu bên dưới. Tín hiệu và bản thảo sẽ được gửi trực tiếp đến Ban Quản Trị để thẩm định và hỗ trợ xuất bản trong thời gian sớm nhất.
    </p>

    <div class="publish-perks">
      <div class="publish-perk-item">
        <div class="publish-perk-icon">💎</div>
        <div>
          <div class="publish-perk-title">Chia Sẻ Doanh Thu</div>
          <div class="publish-perk-text">Nhận chia sẻ từ lượt đọc, donate và ủng hộ từ độc giả.</div>
        </div>
      </div>
      <div class="publish-perk-item">
        <div class="publish-perk-icon">🚀</div>
        <div>
          <div class="publish-perk-title">Tiếp Cận Hàng Triệu Độc Giả</div>
          <div class="publish-perk-text">Truyện được quảng bá trên trang chủ, banner thịnh hành và hệ thống gợi ý.</div>
        </div>
      </div>
      <div class="publish-perk-item">
        <div class="publish-perk-icon">🛡️</div>
        <div>
          <div class="publish-perk-title">Bảo Vệ Bản Quyền Tác Phẩm</div>
          <div class="publish-perk-text">Hệ thống bảo vệ chống hotlink, chống reup trái phép và hỗ trợ DMCA.</div>
        </div>
      </div>
    </div>
  </div>

  @if($errors->any())
    <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; color: #f87171;">
      <div style="font-weight: 700; margin-bottom: 6px;">⚠️ Vui lòng kiểm tra lại thông tin:</div>
      <ul style="margin: 0; padding-left: 20px; font-size: 13.5px;">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(auth()->check())
    <div style="display:flex; justify-content:flex-end; margin-bottom: 16px;">
      <a href="{{ route('user.publishingRequests') }}" style="display:inline-flex; align-items:center; gap:6px; font-size:13.5px; font-weight:600; color:var(--primary); text-decoration:none;">
        <span>📋 Xem danh sách đơn đã gửi của bạn</span> →
      </a>
    </div>
  @endif

  {{-- Submission Form --}}
  <form action="{{ route('publish.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- Section 1: Thông tin tác giả / Nhóm dịch --}}
    <div class="publish-form-card">
      <div class="form-section-head">
        <div class="form-section-icon">👤</div>
        <div class="form-section-title">1. Thông Tin Người Đăng / Nhóm Dịch</div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="creator_name">Họ và tên / Bút danh <span class="req">*</span></label>
          <input type="text" id="creator_name" name="creator_name" class="form-input" value="{{ old('creator_name', $user->name ?? '') }}" placeholder="VD: Nguyễn Văn A hoặc Bút danh Sáng tác" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email nhận phản hồi <span class="req">*</span></label>
          <input type="email" id="email" name="email" class="form-input" value="{{ old('email', $user->email ?? '') }}" placeholder="VD: contact@author.com" required />
          <div class="form-help">BQT sẽ gửi email thông báo kết quả thẩm định và hợp tác qua địa chỉ này.</div>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="phone_or_social">Số điện thoại / Zalo / Telegram / Discord <span class="req">*</span></label>
          <input type="text" id="phone_or_social" name="phone_or_social" class="form-input" value="{{ old('phone_or_social') }}" placeholder="VD: 0912345678 hoặc Zalo: 0912... hoặc @tele_user" required />
          <div class="form-help">Phương thức liên hệ nhanh khi cần trao đổi về bản thảo.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="team_name">Tên Nhóm dịch / Studio (nếu có)</label>
          <input type="text" id="team_name" name="team_name" class="form-input" value="{{ old('team_name') }}" placeholder="VD: Manga Translate Team / Studio Art" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="experience_level">Kinh nghiệm sáng tác / dịch thuật <span class="req">*</span></label>
        <select id="experience_level" name="experience_level" class="form-select" required>
          <option value="beginner" {{ old('experience_level') === 'beginner' ? 'selected' : '' }}>Mới bắt đầu / Tác phẩm đầu tay</option>
          <option value="experienced" {{ old('experience_level') === 'experienced' ? 'selected' : '' }}>Đã có kinh nghiệm / Đã có tác phẩm tự do trước đây</option>
          <option value="professional" {{ old('experience_level') === 'professional' ? 'selected' : '' }}>Tác giả / Họa sĩ chuyên nghiệp</option>
          <option value="group" {{ old('experience_level') === 'group' ? 'selected' : '' }}>Đại diện Nhóm dịch / Studio xuất bản</option>
        </select>
      </div>
    </div>

    {{-- Section 2: Thông tin tác phẩm --}}
    <div class="publish-form-card">
      <div class="form-section-head">
        <div class="form-section-icon">📖</div>
        <div class="form-section-title">2. Thông Tin Tác Phẩm Muốn Đăng</div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="story_title">Tên truyện dự kiến đăng <span class="req">*</span></label>
          <input type="text" id="story_title" name="story_title" class="form-input" value="{{ old('story_title') }}" placeholder="VD: Chúa Tể Thức Tỉnh, Trọng Sinh..." required />
        </div>

        <div class="form-group">
          <label class="form-label" for="story_original_title">Tên gốc (nếu là truyện dịch hoặc chuyển thể)</label>
          <input type="text" id="story_original_title" name="story_original_title" class="form-input" value="{{ old('story_original_title') }}" placeholder="VD: Solo Leveling, Omniscient Reader..." />
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="story_type">Loại hình tác phẩm <span class="req">*</span></label>
          <select id="story_type" name="story_type" class="form-select" required>
            <option value="original" {{ old('story_type') === 'original' ? 'selected' : '' }}>🎨 Truyện Sáng Tác / Original (Truyện tranh Việt Nam)</option>
            <option value="translation" {{ old('story_type') === 'translation' ? 'selected' : '' }}>🌐 Bản Dịch (Manga / Manhwa / Manhua)</option>
            <option value="novel" {{ old('story_type') === 'novel' ? 'selected' : '' }}>✍️ Tiểu Thuyết / Truyện Chữ</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="story_status">Tình trạng bản thảo <span class="req">*</span></label>
          <select id="story_status" name="story_status" class="form-select" required>
            <option value="ongoing" {{ old('story_status') === 'ongoing' ? 'selected' : '' }}>⏳ Đang sáng tác / Đang tiến hành ra đều</option>
            <option value="completed" {{ old('story_status') === 'completed' ? 'selected' : '' }}>✅ Đã hoàn thành toàn bộ tác phẩm</option>
            <option value="translating" {{ old('story_status') === 'translating' ? 'selected' : '' }}>🔄 Đang tiến hành dịch song song</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Thể loại truyện (chọn các thể loại phù hợp)</label>
        <div class="genre-tags-grid">
          @foreach($genres as $genre)
            <label class="genre-tag-item">
              <input type="checkbox" name="genres[]" value="{{ $genre->name }}" {{ in_array($genre->name, old('genres', [])) ? 'checked' : '' }} />
              <span>{{ $genre->name }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="summary">Tóm tắt nội dung cốt truyện <span class="req">*</span></label>
        <textarea id="summary" name="summary" class="form-textarea" placeholder="Giới thiệu khái quát bối cảnh, nhân vật chính và điểm hấp dẫn của bộ truyện (tối thiểu 20 ký tự)..." required>{{ old('summary') }}</textarea>
      </div>
    </div>

    {{-- Section 3: Bản thảo mẫu & Ảnh bìa --}}
    <div class="publish-form-card">
      <div class="form-section-head">
        <div class="form-section-icon">📁</div>
        <div class="form-section-title">3. Bản Thảo Đọc Thử & Hình Ảnh Minh Họa</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="sample_link">Liên kết bản thảo / Link đọc thử (Google Drive / Doc / Website)</label>
        <input type="url" id="sample_link" name="sample_link" class="form-input" value="{{ old('sample_link') }}" placeholder="https://drive.google.com/drive/folders/... hoặc liên kết đọc thử" />
        <div class="form-help">Hãy đảm bảo link Google Drive được mở quyền "Bất kỳ ai có đường liên kết đều có thể xem".</div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="cover_image">Ảnh bìa tác phẩm (JPG, PNG, WEBP — Tối đa 5MB)</label>
          <input type="file" id="cover_image" name="cover_image" class="form-input" accept="image/jpeg,image/png,image/webp" onchange="previewCover(this)" />
          <img id="cover_preview" class="cover-preview-img" alt="Xem trước ảnh bìa" />
        </div>

        <div class="form-group">
          <label class="form-label" for="sample_file">Tệp bản thảo đính kèm (ZIP, PDF, DOCX — Tối đa 20MB)</label>
          <input type="file" id="sample_file" name="sample_file" class="form-input" accept=".zip,.rar,.pdf,.doc,.docx,.txt" />
          <div class="form-help">Đính kèm 1-3 chương đầu hoặc kịch bản mẫu để BQT thẩm định nhanh chóng.</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="note">Lời nhắn gửi tới Ban Quản Trị (tùy chọn)</label>
        <textarea id="note" name="note" class="form-textarea" style="min-height: 80px;" placeholder="Yêu cầu hỗ trợ thêm về kỹ thuật, lịch đăng hoặc mong muốn hợp tác đặc biệt...">{{ old('note') }}</textarea>
      </div>
    </div>

    {{-- Section 4: Cam kết & Gửi đơn --}}
    <div class="publish-form-card" style="background: rgba(255,94,54,0.03); border-color: rgba(255,94,54,0.25);">
      <div style="margin-bottom: 20px;">
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
          <input type="checkbox" name="terms_agreed" value="1" style="accent-color:var(--primary); width:18px; height:18px; margin-top:2px;" required {{ old('terms_agreed') ? 'checked' : '' }} />
          <span style="font-size: 13.5px; color: var(--text-main); line-height: 1.5;">
            Tôi cam kết thông tin cung cấp là chính xác, tác phẩm tuân thủ thuần phong mỹ tục, không vi phạm pháp luật và tôi có quyền sở hữu hợp pháp hoặc quyền dịch thuật tác phẩm này.
          </span>
        </label>
      </div>

      <button type="submit" class="btn-submit-publish">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"></line>
          <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
        </svg>
        <span>Gửi Đơn Đăng Ký Đăng Truyện Ngay</span>
      </button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  function previewCover(input) {
    const preview = document.getElementById('cover_preview');
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      preview.style.display = 'none';
    }
  }
</script>
@endpush
