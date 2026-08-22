@extends('layouts.main')

@section('title', 'Nhóm dịch: ' . $team->name . ' - WebComics')

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <a href="{{ route('teams.index') }}">Nhóm Dịch</a> &rsaquo; <span>{{ $team->name }}</span>
      </div>
    </div>

    <!-- Team Spotlight Header Card -->
    <div style="
      background: rgba(19, 22, 30, 0.85);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 28px;
      margin-bottom: 35px;
      display: flex;
      gap: 24px;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    ">
      <div style="
        width: 100px;
        height: 100px;
        border-radius: 16px;
        background: linear-gradient(135deg, #0ea5e9, #3b82f6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        font-weight: 900;
        color: #fff;
        box-shadow: 0 8px 24px rgba(14, 165, 233, 0.35);
        flex-shrink: 0;
      ">
        @if($team->avatar)
          <img src="{{ $team->avatar }}" alt="{{ $team->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px;">
        @else
          {{ mb_strtoupper(mb_substr($team->name, 0, 1)) }}
        @endif
      </div>

      <div style="flex: 1; min-width: 250px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 8px;">
          <h1 style="font-size: 24px; font-weight: 900; color: #fff; margin: 0;">{{ $team->name }}</h1>
          
          @auth
            <button type="button" id="btn-follow-team" data-team-id="{{ $team->id }}" class="btn-spotlight-sub" style="
              cursor: pointer;
              padding: 8px 18px;
              border-radius: 20px;
              font-weight: 700;
              transition: all 0.2s;
              {{ $isFollowed ? 'background: #0ea5e9; color: #fff; border-color: #0ea5e9;' : 'background: rgba(255,255,255,0.08);' }}
            ">
              <span id="follow-text">{{ $isFollowed ? '✓ Đang Theo Dõi' : '🔔 Theo Dõi Nhóm Dịch' }}</span>
            </button>
          @else
            <a href="{{ route('login') }}" class="btn-spotlight-sub" style="text-decoration: none; padding: 8px 18px; border-radius: 20px; font-weight: 700;">
              🔔 Theo Dõi Nhóm Dịch
            </a>
          @endauth
        </div>

        <p style="color: var(--text-sub); font-size: 13.5px; line-height: 1.6; margin: 0 0 12px 0;">
          {{ $team->description ?: 'Nhóm dịch truyện tranh uy tín trên WebComics.' }}
        </p>

        <div style="display: flex; gap: 16px; font-size: 13px; color: var(--text-sub); flex-wrap: wrap;">
          <span>📚 <strong>{{ $team->comics->count() }}</strong> bộ truyện</span>
          <span>👥 <strong id="followers-count">{{ number_format($team->followers_count) }}</strong> người theo dõi</span>
          <span>👤 <strong>{{ $team->members_count }}</strong> thành viên</span>
          @if($team->website)
            <a href="{{ $team->website }}" target="_blank" rel="noopener" style="color: #38bdf8; text-decoration: underline;">🌐 Website</a>
          @endif
          @if($team->facebook)
            <a href="{{ $team->facebook }}" target="_blank" rel="noopener" style="color: #38bdf8; text-decoration: underline;">📘 Facebook</a>
          @endif
          @if($team->discord)
            <a href="{{ $team->discord }}" target="_blank" rel="noopener" style="color: #38bdf8; text-decoration: underline;">💬 Discord</a>
          @endif
        </div>
      </div>
    </div>

    <!-- Danh sách truyện của Nhóm dịch -->
    <div class="section-header" style="margin-bottom: 20px;">
      <h2 class="section-title">✨ Truyện Do {{ $team->name }} Đăng Tải / Dịch</h2>
    </div>

    @if($team->comics->isNotEmpty())
      <div class="comics-grid">
        @foreach($team->comics as $comic)
          <a href="{{ route('comics.show', $comic->slug) }}" class="comic-card-sm">
            <div class="sm-cover">
              <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" loading="lazy">
              <span class="sm-badge">★ {{ number_format($comic->avg_rating, 1) }}</span>
            </div>
            <p class="sm-title">{{ $comic->title }}</p>
          </a>
        @endforeach
      </div>
    @else
      <div style="padding: 40px; text-align: center; border: 1px dashed var(--border); border-radius: 12px; color: var(--text-sub);">
        Nhóm dịch này chưa có bộ truyện nào trên hệ thống.
      </div>
    @endif

  </div>
</main>

@push('scripts')
<script>
  const followBtn = document.getElementById('btn-follow-team');
  if (followBtn) {
    followBtn.addEventListener('click', function() {
      const teamId = this.getAttribute('data-team-id');
      const textEl = document.getElementById('follow-text');
      const countEl = document.getElementById('followers-count');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      followBtn.disabled = true;

      fetch(`/api/teams/${teamId}/follow`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        }
      })
      .then(res => res.json())
      .then(data => {
        followBtn.disabled = false;
        if (data.status === 'success') {
          if (data.is_followed) {
            followBtn.style.background = '#0ea5e9';
            followBtn.style.borderColor = '#0ea5e9';
            followBtn.style.color = '#fff';
            if (textEl) textEl.textContent = '✓ Đang Theo Dõi';
          } else {
            followBtn.style.background = 'rgba(255,255,255,0.08)';
            followBtn.style.borderColor = 'var(--border)';
            followBtn.style.color = 'var(--text-sub)';
            if (textEl) textEl.textContent = '🔔 Theo Dõi Nhóm Dịch';
          }
          if (countEl) countEl.textContent = Number(data.followers_count).toLocaleString();
        }
      })
      .catch(err => {
        followBtn.disabled = false;
        console.debug("Follow team error:", err);
      });
    });
  }
</script>
@endpush
@endsection
