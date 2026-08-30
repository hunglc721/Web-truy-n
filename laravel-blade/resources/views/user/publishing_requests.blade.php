@extends('layouts.main')
@section('title', 'Đơn Đăng Ký Đăng Truyện Của Tôi - WebComics')

@section('content')
<main class="page-container">
  <div class="container" style="padding-top: 32px; padding-bottom: 56px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
      <div>
        <h1 style="margin-bottom: 6px;">📝 Đơn Đăng Ký Đăng Truyện Của Tôi</h1>
        <p style="color: var(--text-sub); margin-top: 0;">Theo dõi tiến độ thẩm định và nhận phản hồi từ Ban Quản Trị về các tác phẩm bạn đã gửi.</p>
      </div>
      <a href="{{ route('publish.create') }}" class="btn-spotlight-read" style="text-decoration:none; padding:10px 18px; font-size:13.5px; display:inline-flex; align-items:center; gap:6px;">
        <span>+ Gửi Đơn Đăng Truyện Mới</span>
      </a>
    </div>

    @include('user._nav')

    <div style="display: grid; gap: 16px;">
      @forelse($requests as $item)
        <article style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 20px; position: relative;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap; margin-bottom: 12px;">
            <div>
              <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px;">
                <span class="badge {{ $item->status_badge_class }}" style="font-size: 12px; padding: 4px 10px;">
                  @if($item->status === 'pending') ⏳ @elseif($item->status === 'reviewing') 🔍 @elseif($item->status === 'approved') ✅ @else ❌ @endif
                  {{ $item->status_label }}
                </span>
                <span style="font-size: 12px; color: var(--text-muted);">
                  Gửi lúc {{ $item->created_at->format('H:i - d/m/Y') }} ({{ $item->created_at->diffForHumans() }})
                </span>
              </div>
              <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); margin: 0 0 4px;">
                {{ $item->story_title }}
                @if($item->story_original_title)
                  <span style="font-size: 14px; font-weight: 500; color: var(--text-sub);">({{ $item->story_original_title }})</span>
                @endif
              </h3>
              <div style="font-size: 13px; color: var(--text-sub);">
                Loại: <strong style="color:var(--text-main);">{{ $item->story_type_label }}</strong> 
                · Tình trạng: <strong style="color:var(--text-main);">{{ $item->story_status_label }}</strong>
                @if($item->team_name)
                  · Nhóm: <strong style="color:var(--text-main);">{{ $item->team_name }}</strong>
                @endif
              </div>
            </div>

            @if($item->cover_image_path)
              <img src="{{ asset('storage/' . $item->cover_image_path) }}" alt="Bìa truyện" style="width: 60px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);" />
            @endif
          </div>

          <div style="background: rgba(255,255,255,0.02); border-radius: 8px; padding: 12px 14px; font-size: 13.5px; color: var(--text-sub); line-height: 1.5; margin-bottom: 12px;">
            <strong style="color: var(--text-main);">Tóm tắt:</strong> {{ \Illuminate\Support\Str::limit($item->summary, 220) }}
          </div>

          @if($item->sample_link)
            <div style="font-size: 13px; margin-bottom: 8px;">
              🔗 <strong>Link đọc thử / bản thảo:</strong> 
              <a href="{{ $item->sample_link }}" target="_blank" rel="noopener" style="color: var(--primary); word-break: break-all;">
                {{ $item->sample_link }} ↗
              </a>
            </div>
          @endif

          {{-- Phản hồi từ Admin --}}
          @if($item->admin_note)
            <div style="margin-top: 14px; padding: 12px 16px; border-radius: 10px; background: {{ $item->status === 'approved' ? 'rgba(34,197,94,0.1)' : ($item->status === 'rejected' ? 'rgba(239,68,68,0.1)' : 'rgba(108,99,255,0.1)') }}; border: 1px solid {{ $item->status === 'approved' ? 'rgba(34,197,94,0.25)' : ($item->status === 'rejected' ? 'rgba(239,68,68,0.25)' : 'rgba(108,99,255,0.25)') }};">
              <div style="font-size: 12.5px; font-weight: 700; color: {{ $item->status === 'approved' ? '#4ade80' : ($item->status === 'rejected' ? '#f87171' : '#9d98ff') }}; margin-bottom: 4px;">
                💬 Phản hồi từ Ban Quản Trị ({{ $item->reviewed_at?->format('d/m/Y') }}):
              </div>
              <div style="font-size: 13.5px; color: var(--text-main); line-height: 1.5;">
                {{ $item->admin_note }}
              </div>
            </div>
          @endif
        </article>
      @empty
        <div style="padding: 48px 20px; text-align: center; border: 1px dashed var(--border); border-radius: 14px; color: var(--text-sub);">
          <div style="font-size: 36px; margin-bottom: 12px;">📑</div>
          <div style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Bạn chưa gửi đơn đăng ký truyện nào</div>
          <p style="font-size: 13.5px; max-width: 480px; margin: 0 auto 18px;">
            Hãy gửi tác phẩm tự sáng tác hoặc bản dịch của bạn để hợp tác xuất bản cùng WebComics và tiếp cận hàng triệu độc giả.
          </p>
          <a href="{{ route('publish.create') }}" class="btn-spotlight-read" style="text-decoration: none; padding: 10px 22px; font-size: 14px;">
            Đăng Ký Gửi Truyện Ngay
          </a>
        </div>
      @endforelse
    </div>

    <div style="margin-top: 22px;">
      {{ $requests->links() }}
    </div>
  </div>
</main>
@endsection
