@extends('layouts.main')

@section('title', 'Tủ Truyện & Lịch Sử Đọc - WebComics')

@section('content')
<main class="page-container" style="padding:40px 0;min-height:80vh;">
  <div class="container">
    <div style="background:linear-gradient(135deg,rgba(108,99,255,.2),rgba(255,42,109,.15));border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:28px 32px;margin-bottom:32px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">
      <div style="display:flex;align-items:center;gap:18px;">
        <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#6c63ff,#ff2a6d);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:900;color:#fff;box-shadow:0 4px 15px rgba(108,99,255,.4);">
          {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div>
          <h1 style="font-size:24px;font-weight:900;color:var(--text-main);margin-bottom:4px;">Xin chào, {{ auth()->user()->name }}! 👋</h1>
          <p style="font-size:13.5px;color:var(--text-muted);margin:0;">Quản lý tủ truyện yêu thích và tiếp tục đúng nơi bạn đã đọc lần trước.</p>
        </div>
      </div>

      @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="btn btn-login" style="background:var(--primary);text-decoration:none;">🛡️ Trang Quản Trị</a>
      @endif
    </div>

    <div style="display:flex;gap:12px;border-bottom:1px solid var(--border-color);margin-bottom:28px;overflow-x:auto;">
      <button type="button" class="lib-tab-btn active" id="tab-btn-library" onclick="switchTab('library')" style="padding:12px 24px;font-size:15px;font-weight:800;cursor:pointer;background:transparent;border:none;border-bottom:3px solid var(--primary);color:var(--primary);transition:all .2s;white-space:nowrap;">📚 Truyện Đã Theo Dõi ({{ $libraries->total() }})</button>
      <button type="button" class="lib-tab-btn" id="tab-btn-history" onclick="switchTab('history')" style="padding:12px 24px;font-size:15px;font-weight:700;cursor:pointer;background:transparent;border:none;border-bottom:3px solid transparent;color:var(--text-muted);transition:all .2s;white-space:nowrap;">📖 Lịch Sử Đọc ({{ $readingHistories->count() }})</button>
    </div>

    <div id="tab-content-library" class="tab-panel">
      @if($libraries->isEmpty())
        <div style="text-align:center;padding:60px 20px;background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:var(--radius-lg);">
          <div style="font-size:54px;margin-bottom:16px;">📚</div>
          <h3 style="font-size:18px;font-weight:800;color:var(--text-main);margin-bottom:8px;">Tủ truyện của bạn đang trống</h3>
          <p style="font-size:14px;color:var(--text-muted);margin-bottom:20px;">Thêm những bộ bạn muốn theo dõi để quay lại đọc nhanh hơn.</p>
          <a href="{{ route('home') }}" class="btn btn-login" style="text-decoration:none;padding:12px 24px;">🔍 Khám Phá Truyện</a>
        </div>
      @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;">
          @foreach($libraries as $item)
            @php $comic = $item->comic; @endphp
            @if($comic)
              <article class="library-card" id="library-item-{{ $comic->id }}" style="background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:var(--radius-md);overflow:hidden;display:flex;flex-direction:column;transition:transform .2s,border-color .2s,opacity .25s;position:relative;">
                <a href="{{ route('comics.show', $comic->slug) }}" style="display:block;position:relative;height:260px;overflow:hidden;">
                  <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" style="width:100%;height:100%;object-fit:cover;" loading="lazy" />
                  @if($comic->status === 'ongoing')
                    <span style="position:absolute;top:10px;left:10px;background:rgba(34,197,94,.9);color:#fff;font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:12px;">ĐANG RA</span>
                  @elseif($comic->status === 'completed')
                    <span style="position:absolute;top:10px;left:10px;background:rgba(59,130,246,.9);color:#fff;font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:12px;">HOÀN THÀNH</span>
                  @endif
                </a>

                <button type="button"
                        data-title="{{ $comic->title }}"
                        onclick="unfollowComic({{ $comic->id }}, this.dataset.title)"
                        title="Bỏ theo dõi"
                        style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,.72);color:#ef4444;border:1px solid rgba(239,68,68,.45);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:5;">♥</button>

                <div style="padding:14px;display:flex;flex-direction:column;flex:1;">
                  <h3 style="font-size:14.5px;font-weight:800;color:var(--text-main);margin-bottom:6px;line-height:1.3;">
                    <a href="{{ route('comics.show', $comic->slug) }}" style="color:var(--text-main);text-decoration:none;">{{ Str::limit($comic->title, 40) }}</a>
                  </h3>

                  <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;margin-top:auto;">
                    @if($comic->latestChapter)
                      <span>Mới nhất: <strong style="color:var(--primary);">{{ $comic->latestChapter->label }}</strong></span>
                    @else
                      <span>Đang cập nhật</span>
                    @endif
                  </div>

                  @if($item->lastReadChapter)
                    @php
                      $lastChapSlug = $item->lastReadChapter->slug ?: ('chapter-' . ($item->lastReadChapter->chapter_number ?? 1));
                      $comicSlug = $comic->slug ?: ('comic-' . $comic->id);
                    @endphp
                    <a href="{{ route('chapters.show', [$comicSlug, $lastChapSlug]) }}" class="btn btn-login" style="font-size:12px;padding:8px 12px;text-align:center;text-decoration:none;font-weight:700;width:100%;">📖 Đọc tiếp Ch.{{ $item->lastReadChapter->chapter_number }}</a>
                  @else
                    <a href="{{ route('comics.show', $comic->slug) }}" class="btn btn-read-secondary" style="font-size:12px;padding:8px 12px;text-align:center;text-decoration:none;font-weight:700;width:100%;">👁️ Xem Chi Tiết</a>
                  @endif
                </div>
              </article>
            @endif
          @endforeach
        </div>

        <div style="margin-top:28px;display:flex;justify-content:center;">{{ $libraries->links() }}</div>
      @endif
    </div>

    <div id="tab-content-history" class="tab-panel" style="display:none;">
      @if($readingHistories->isEmpty())
        <div style="text-align:center;padding:60px 20px;background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:var(--radius-lg);">
          <div style="font-size:54px;margin-bottom:16px;">📖</div>
          <h3 style="font-size:18px;font-weight:800;color:var(--text-main);margin-bottom:8px;">Chưa có lịch sử đọc</h3>
          <p style="font-size:14px;color:var(--text-muted);">Khi bạn đọc chapter, tiến độ sẽ tự động xuất hiện tại đây.</p>
        </div>
      @else
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap;">
          <span style="font-size:14px;color:var(--text-muted);">20 lần đọc gần đây nhất</span>
          <form action="{{ route('history.clear') }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa toàn bộ lịch sử đọc?');">
            @csrf
            @method('DELETE')
            <button type="submit" style="background:transparent;border:none;color:#ef4444;font-size:13px;font-weight:600;cursor:pointer;">🗑️ Xóa Lịch Sử Đọc</button>
          </form>
        </div>

        <div style="background:var(--bg-surface-1);border:1px solid var(--border-color);border-radius:var(--radius-lg);overflow-x:auto;">
          <table style="width:100%;min-width:720px;border-collapse:collapse;text-align:left;color:var(--text-main);">
            <thead>
              <tr style="background:var(--bg-surface-2);border-bottom:1px solid var(--border-color);font-size:12.5px;color:var(--text-muted);">
                <th style="padding:14px 18px;">Bộ Truyện</th><th style="padding:14px 18px;">Chương Đã Đọc</th><th style="padding:14px 18px;">Tiến Độ / Thời Gian</th><th style="padding:14px 18px;text-align:right;">Hành Động</th>
              </tr>
            </thead>
            <tbody>
              @foreach($readingHistories as $history)
                @if($history->comic && $history->chapter)
                  @php
                    $histChapSlug = $history->chapter->slug ?: ('chapter-' . ($history->chapter->chapter_number ?? 1));
                    $histComicSlug = $history->comic->slug ?: ('comic-' . $history->comic->id);
                  @endphp
                  <tr style="border-bottom:1px solid var(--border-color);font-size:13.5px;">
                    <td style="padding:14px 18px;">
                      <div style="display:flex;align-items:center;gap:12px;">
                        <img src="{{ $history->comic->cover_image }}" alt="{{ $history->comic->title }}" style="width:38px;height:50px;object-fit:cover;border-radius:4px;" loading="lazy" />
                        <a href="{{ route('comics.show', $history->comic->slug) }}" style="font-weight:700;color:var(--text-main);text-decoration:none;">{{ $history->comic->title }}</a>
                      </div>
                    </td>
                    <td style="padding:14px 18px;"><span style="background:rgba(108,99,255,.15);color:var(--primary);font-size:12px;font-weight:700;padding:4px 10px;border-radius:12px;">{{ $history->chapter->label }}{{ $history->chapter->title ? ' - '.$history->chapter->title : '' }}</span></td>
                    <td style="padding:14px 18px;color:var(--text-muted);font-size:12.5px;">
                      @if(($history->scroll_percent ?? 0) > 0)<strong style="color:var(--primary);">{{ round($history->scroll_percent) }}%</strong> &middot; @endif
                      🕒 {{ $history->last_read_at ? $history->last_read_at->diffForHumans() : 'Vừa xong' }}
                    </td>
                    <td style="padding:14px 18px;text-align:right;"><a href="{{ route('chapters.show', [$histComicSlug, $histChapSlug]) }}" class="btn btn-login" style="font-size:12px;padding:6px 14px;text-decoration:none;font-weight:700;">📖 Đọc Tiếp</a></td>
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
    const showLibrary = tabName === 'library';

    btnLib.classList.toggle('active', showLibrary);
    btnHis.classList.toggle('active', !showLibrary);
    btnLib.style.borderColor = showLibrary ? 'var(--primary)' : 'transparent';
    btnLib.style.color = showLibrary ? 'var(--primary)' : 'var(--text-muted)';
    btnHis.style.borderColor = showLibrary ? 'transparent' : 'var(--primary)';
    btnHis.style.color = showLibrary ? 'var(--text-muted)' : 'var(--primary)';
    panelLib.style.display = showLibrary ? 'block' : 'none';
    panelHis.style.display = showLibrary ? 'none' : 'block';
  }

  function unfollowComic(comicId, title) {
    if (!confirm(`Bạn có chắc muốn bỏ theo dõi bộ truyện "${title}"?`)) return;

    fetch(`/user/library/toggle/${encodeURIComponent(comicId)}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
    .then(async res => {
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Không thể cập nhật tủ truyện.');
      return data;
    })
    .then(data => {
      if (data.status === 'success') {
        const itemCard = document.getElementById(`library-item-${comicId}`);
        if (itemCard) {
          itemCard.style.opacity = '0';
          itemCard.style.transform = 'scale(.94)';
          setTimeout(() => itemCard.remove(), 250);
        }
      }
    })
    .catch(err => alert(err.message || 'Không thể bỏ theo dõi truyện.'));
  }
</script>
@endpush
