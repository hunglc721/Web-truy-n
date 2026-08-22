@extends('layouts.main')

@section('title', 'Danh Sách Tuyển Tập Truyện - WebComics')

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Tuyển Tập Truyện</span>
      </div>
      <h1 class="page-title" style="font-size: 26px; font-weight: 900; color: #fff; margin-top: 10px;">📚 Tuyển Tập Truyện Do Cộng Đồng Tuyển Chọn</h1>
      <p style="color: var(--text-sub); font-size: 14px; margin-top: 4px;">Khám phá các danh sách truyện theo chủ đề do độc giả tâm huyết chia sẻ.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 25px;">
      @forelse($lists as $item)
        <a href="{{ route('lists.show', $item->slug) }}" style="
          text-decoration: none;
          background: rgba(19, 22, 30, 0.85);
          border: 1px solid var(--border);
          border-radius: 14px;
          padding: 22px;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          transition: all 0.25s ease;
        " onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='none'">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
              <span style="font-size: 11px; background: rgba(255,94,54,0.15); color: var(--primary); padding: 3px 8px; border-radius: 4px; font-weight: 800;">
                📖 {{ $item->comics_count }} TRUYỆN
              </span>
              <span style="font-size: 12px; color: var(--text-muted);">
                👁 {{ number_format($item->views_count) }}
              </span>
            </div>

            <h3 style="font-size: 16.5px; font-weight: 800; color: #fff; margin: 0 0 6px 0; line-height: 1.4;">
              {{ $item->title }}
            </h3>

            <p style="font-size: 13px; color: var(--text-sub); margin: 0 0 14px 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
              {{ $item->description ?: 'Danh sách truyện tuyển chọn hay nhất.' }}
            </p>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px; font-size: 12px; color: var(--text-sub);">
            <span>Tạo bởi: <strong style="color: #fff;">{{ $item->user->name ?? 'Thành viên' }}</strong></span>
            <span style="color: #ef4444; font-weight: 700;">❤️ {{ number_format($item->likes_count) }}</span>
          </div>
        </a>
      @empty
        <div style="grid-column: 1 / -1; padding: 50px; text-align: center; border: 1px dashed var(--border); border-radius: 12px; color: var(--text-sub);">
          Chưa có danh sách tuyển tập nào được chia sẻ.
        </div>
      @endforelse
    </div>

    @if($lists->hasPages())
      <div style="margin-top: 30px;">
        {{ $lists->links() }}
      </div>
    @endif

  </div>
</main>
@endsection
