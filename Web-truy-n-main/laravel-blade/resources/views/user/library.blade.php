{{-- resources/views/user/library.blade.php --}}
@extends('layouts.main')

@section('title', 'Tủ sách cá nhân & Lịch sử đọc — WebComics')

@section('content')
<main class="page-container" style="padding: 40px 0; min-height: 80vh;">
  <div class="container">

    {{-- User Header Banner --}}
    <div style="
      background: linear-gradient(135deg, rgba(108,99,255,0.2), rgba(255,42,109,0.15));
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 28px 32px;
      margin-bottom: 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 20px;
    ">
      <div style="display: flex; align-items: center; gap: 18px;">
        <div style="
          width: 64px; height: 64px; border-radius: 50%;
          background: linear-gradient(135deg, #6c63ff, #ff2a6d);
          display: flex; align-items: center; justify-content: center;
          font-size: 26px; font-weight: 900; color: #fff;
          box-shadow: 0 4px 15px rgba(108,99,255,0.4);
        ">
          {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div>
          <h1 style="font-size: 24px; font-weight: 900; color: var(--text-main); margin-bottom: 4px;">
            Xin chào, {{ auth()->user()->name }}! 👋
          </h1>
          <p style="font-size: 13.5px; color: var(--text-muted); margin: 0;">
            Quản lý tủ sách truyện tranh yêu thích và lịch sử đọc cá nhân của bạn.
          </p>
        </div>
      </div>

      <div style="display: flex; gap: 10px;">
        @if(auth()->user()->is_admin)
          <a href="{{ route('admin.dashboard') }}" class="btn btn-login" style="background: var(--primary); text-decoration: none;">
            🛡️ Trang Quản Trị Admin
          </a>
        @endif
        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
          @csrf
          <button type="submit" class="btn btn-read-secondary" style="font-size: 13px; padding: 10px 18px;">
            ✕ Đăng xuất
          </button>
        </form>
      </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
      <div style="background: rgba(46, 204, 113, 0.15); border: 1px solid #2ecc71; color: #2ecc71; padding: 14px 20px; border-radius: var(--radius-md); margin-bottom: 24px; font-weight: 600;">
        ✅ {{ session('success') }}
      </div>
    @endif

    {{-- TAB BUTTONS --}}
    <div style="display: flex; gap: 12px; border-bottom: 1px solid var(--border-color); margin-bottom: 28px;">
      <button type="button" class="lib-tab-btn active" id="tab-btn-library" onclick="switchTab('library')" style="
        padding: 12px 24px; font-size: 15px; font-weight: 800; cursor: pointer;
        background: transparent; border: none; border-bottom: 3px solid var(--primary);
        color: var(--primary); transition: all 0.2s;
      ">
        📚 Truyện Đã Theo Dõi ({{ $libraries->total() }})
      </button>

      <button type="button" class="lib-tab-btn" id="tab-btn-history" onclick="switchTab('history')" style="
        padding: 12px 24px; font-size: 15px; font-weight: 700; cursor: pointer;
        background: transparent; border: none; border-bottom: 3px solid transparent;
        color: var(--text-muted); transition: all 0.2s;
      ">
        📖 Lịch Sử Đọc Gần Đây ({{ $readingHistories->count() }})
      </button>
    </div>

    {{-- ── TAB 1: TỦ SÁCH THEO DÕI ── --}}
    <div id="tab-content-library" class="tab-panel">
      @if($libraries->isEmpty())
        <div style="text-align: center; padding: 60px 20px; background: var(--bg-surface-1); border: 1px solid var(--border-color); border-radius: var(--radius-lg);">
          <div style="font-size: 54px; margin-bottom: 16px;">📚</div>
          <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
            Tủ sách của bạn hiện đang trống
          </h3>
          <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
            Hãy bấm nút "Theo dõi" ở các bộ truyện yêu thích để lưu vào đây nhé!
          </p>
          <a href="{{ route('home') }}" class="btn btn-login" style="text-decoration: none; padding: 12px 24px;">
            🔍 Khám Phá Truyện Hay Ngay
          </a>
        </div>
      @else
        <div style="
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
          gap: 20px;
        ">
          @foreach($libraries as $item)
            @php $comic = $item->comic; @endphp
            @if($comic)
              <div class="library-card" id="library-item-{{ $comic->id }}" style="
                background: var(--bg-surface-1);
                border: 1px solid var(--border-color);
                border-radius: var(--radius-md);
                overflow: hidden;
                display: flex;
                flex-direction: column;
                transition: transform 0.2s, border-color 0.2s;
                position: relative;
              ">
                {{-- Bìa truyện --}}
                <a href="{{ route('comics.show', $comic->slug) }}" style="display: block; position: relative; height: 260px; overflow: hidden;">
                  <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" style="width: 100%; height: 100%; object-fit: cover;" />
                  @if($comic->status === 'ONGOING')
                    <span style="position: absolute; top: 10px; left: 10px; background: rgba(34, 197, 94, 0.9); color: #fff; font-size: 10.5px; font-weight: 800; padding: 3px 8px; border-radius: 12px;">
                      ONGOING
                    </span>
                  @endif
                </a>

                {{-- Nút Hủy Theo dõi nhanh --}}
                <button type="button" onclick="unfollowComic({{ $comic->id }}, '{{ $comic->title }}')" title="Bỏ theo dõi" style="
                  position: absolute; top: 10px; right: 10px;
                  background: rgba(0,0,0,0.65); color: #ef4444; border: 1px solid rgba(239,68,68,0.4);
                  border-radius: 50%; width: 32px; height: 32px;
                  display: flex; align-items: center; justify-content: center;
                  cursor: pointer; transition: background 0.2s; z-index: 5;
                ">
                  ❤️
                </button>

                {{-- Thông tin truyện --}}
                <div style="padding: 14px; display: flex; flex-direction: column; flex: 1;">
                  <h3 style="font-size: 14.5px; font-weight: 800; color: var(--text-main); margin-bottom: 6px; line-height: 1.3;">
                    <a href="{{ route('comics.show', $comic->slug) }}" style="color: var(--text-main); text-decoration: none;">
                      {{ Str::limit($comic->title, 40) }}
                    </a>
                  </h3>

                  <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px; margin-top: auto;">
                    @if($comic->latestChapter)
                      <span>Mới nhất: <strong style="color: var(--primary)">Ch.{{ $comic->latestChapter->chapter_number }}</strong></span>
                    @else
                      <span>Đang cập nhật</span>
                    @endif
                  </div>

                  @if($item->lastReadChapter)
                    <a href="{{ route('chapters.show', [$comic->slug, $item->lastReadChapter->slug]) }}" class="btn btn-login" style="
                      font-size: 12px; padding: 8px 12px; text-align: center; text-decoration: none; font-weight: 700; width: 100%;
                    ">
                      📖 Đọc tiếp Ch.{{ $item->lastReadChapter->chapter_number }}
                    </a>
                  @else
                    <a href="{{ route('comics.show', $comic->slug) }}" class="btn btn-read-secondary" style="
                      font-size: 12px; padding: 8px 12px; text-align: center; text-decoration: none; font-weight: 700; width: 100%;
                    ">
                      👁️ Xem Chi Tiết
                    </a>
                  @endif
                </div>
              </div>
            @endif
          @endforeach
        </div>

        <div style="margin-top: 28px; display: flex; justify-content: center;">
          {{ $libraries->links() }}
        </div>
      @endif
    </div>

    {{-- ── TAB 2: LỊCH SỬ ĐỌC ── --}}
    <div id="tab-content-history" class="tab-panel" style="display: none;">
      @if($readingHistories->isEmpty())
        <div style="text-align: center; padding: 60px 20px; background: var(--bg-surface-1); border: 1px solid var(--border-color); border-radius: var(--radius-lg);">
          <div style="font-size: 54px; margin-bottom: 16px;">📖</div>
          <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
            Chưa có lịch sử đọc truyện
          </h3>
          <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
            Khi bạn đọc các chương truyện, lịch sử sẽ tự động được lưu tại đây.
          </p>
        </div>
      @else
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <span style="font-size: 14px; color: var(--text-muted);">Hiển thị 20 chương đọc gần đây nhất</span>
          <form action="{{ route('history.clear') }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa toàn bộ lịch sử đọc?');">
            @csrf
            @method('DELETE')
            <button type="submit" style="background: transparent; border: none; color: #ef4444; font-size: 13px; font-weight: 600; cursor: pointer;">
              🗑️ Xóa Lịch Sử Đọc
            </button>
          </form>
        </div>

        <div style="background: var(--bg-surface-1); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden;">
          <table style="width: 100%; border-collapse: collapse; text-align: left; color: var(--text-main);">
            <thead>
              <tr style="background: var(--bg-surface-2); border-bottom: 1px solid var(--border-color); font-size: 12.5px; color: var(--text-muted);">
                <th style="padding: 14px 18px;">Bộ Truyện</th>
                <th style="padding: 14px 18px;">Chương Đã Đọc</th>
                <th style="padding: 14px 18px;">Thời Gian Đọc</th>
                <th style="padding: 14px 18px; text-align: right;">Hành Động</th>
              </tr>
            </thead>
            <tbody>
              @foreach($readingHistories as $history)
                @if($history->comic && $history->chapter)
                  <tr style="border-bottom: 1px solid var(--border-color); font-size: 13.5px;">
                    <td style="padding: 14px 18px;">
                      <div style="display: flex; align-items: center; gap: 12px;">
                        <img src="{{ $history->comic->cover_image }}" alt="{{ $history->comic->title }}" style="width: 38px; height: 50px; object-fit: cover; border-radius: 4px;" />
                        <a href="{{ route('comics.show', $history->comic->slug) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
                          {{ $history->comic->title }}
                        </a>
                      </div>
                    </td>
                    <td style="padding: 14px 18px;">
                      <span style="background: rgba(108,99,255,0.15); color: var(--primary); font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 12px;">
                        Ch.{{ $history->chapter->chapter_number }} - {{ $history->chapter->title }}
                      </span>
                    </td>
                    <td style="padding: 14px 18px; color: var(--text-muted); font-size: 12.5px;">
                      🕒 {{ $history->last_read_at ? $history->last_read_at->diffForHumans() : 'Vừa xong' }}
                    </td>
                    <td style="padding: 14px 18px; text-align: right;">
                      <a href="{{ route('chapters.show', [$history->comic->slug, $history->chapter->slug]) }}" class="btn btn-login" style="
                        font-size: 12px; padding: 6px 14px; text-decoration: none; font-weight: 700;
                      ">
                        📖 Đọc Tiếp
                      </a>
                    </td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

  </div>
</main>
@endsection

@push('scripts')
<script>
  function switchTab(tabName) {
    const btnLib = document.getElementById('tab-btn-library');
    const btnHis = document.getElementById('tab-btn-history');
    const panelLib = document.getElementById('tab-content-library');
    const panelHis = document.getElementById('tab-content-history');

    if (tabName === 'library') {
      btnLib.classList.add('active');
      btnLib.style.borderColor = 'var(--primary)';
      btnLib.style.color = 'var(--primary)';

      btnHis.classList.remove('active');
      btnHis.style.borderColor = 'transparent';
      btnHis.style.color = 'var(--text-muted)';

      panelLib.style.display = 'block';
      panelHis.style.display = 'none';
    } else {
      btnHis.classList.add('active');
      btnHis.style.borderColor = 'var(--primary)';
      btnHis.style.color = 'var(--primary)';

      btnLib.classList.remove('active');
      btnLib.style.borderColor = 'transparent';
      btnLib.style.color = 'var(--text-muted)';

      panelHis.style.display = 'block';
      panelLib.style.display = 'none';
    }
  }

  // AJAX Bỏ theo dõi trực tiếp không cần reload
  function unfollowComic(comicId, title) {
    if (!confirm(`Bạn có chắc muốn bỏ theo dõi bộ truyện "${title}"?`)) return;

    fetch(`/user/library/toggle/${comicId}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        const itemCard = document.getElementById(`library-item-${comicId}`);
        if (itemCard) {
          itemCard.style.opacity = '0';
          itemCard.style.transform = 'scale(0.9)';
          setTimeout(() => itemCard.remove(), 250);
        }
      }
    })
    .catch(err => console.error('Lỗi khi bỏ theo dõi:', err));
  }
</script>
@endpush
