@extends('layouts.main')

@section('title', 'Danh Sách Nhóm Dịch - WebComics')

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header">
      <div class="breadcrumb">
        <a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <span>Nhóm Dịch</span>
      </div>
      <h1 class="page-title" style="font-size: 26px; font-weight: 900; color: #fff; margin-top: 10px;">👥 Danh Sách Nhóm Dịch & Scanlation</h1>
      <p style="color: var(--text-sub); font-size: 14px; margin-top: 4px;">Khám phá và theo dõi các nhóm dịch truyện tranh chất lượng cao trên hệ thống WebComics.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 25px;">
      @forelse($teams as $team)
        <a href="{{ route('teams.show', $team->slug) }}" style="
          text-decoration: none;
          background: rgba(19, 22, 30, 0.85);
          border: 1px solid var(--border);
          border-radius: 14px;
          padding: 20px;
          display: flex;
          gap: 16px;
          align-items: center;
          transition: all 0.25s ease;
        " onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='none'">
          <div style="
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0ea5e9, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 900;
            color: #fff;
            flex-shrink: 0;
          ">
            @if($team->avatar)
              <img src="{{ $team->avatar }}" alt="{{ $team->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
            @else
              {{ mb_strtoupper(mb_substr($team->name, 0, 1)) }}
            @endif
          </div>

          <div style="min-width: 0; flex: 1;">
            <h3 style="font-size: 16px; font-weight: 800; color: #fff; margin: 0 0 4px 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ $team->name }}
            </h3>
            <p style="font-size: 12.5px; color: var(--text-sub); margin: 0 0 6px 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ $team->description ?: 'Nhóm dịch truyện tranh uy tín.' }}
            </p>
            <div style="display: flex; gap: 12px; font-size: 12px; color: var(--text-muted);">
              <span>📚 <strong>{{ $team->comics_count }}</strong> bộ</span>
              <span>👥 <strong>{{ number_format($team->followers_count) }}</strong> followers</span>
            </div>
          </div>
        </a>
      @empty
        <div style="grid-column: 1 / -1; padding: 50px; text-align: center; border: 1px dashed var(--border); border-radius: 12px; color: var(--text-sub);">
          Chưa có thông tin nhóm dịch nào.
        </div>
      @endforelse
    </div>

    @if($teams->hasPages())
      <div style="margin-top: 30px;">
        {{ $teams->links() }}
      </div>
    @endif

  </div>
</main>
@endsection
