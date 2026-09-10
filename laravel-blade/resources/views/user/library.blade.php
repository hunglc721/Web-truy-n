@extends('layouts.main')
@section('title','Tủ truyện - WebComics')

@push('styles')
<style>
  .library-shell{padding-top:34px;padding-bottom:60px}
  .library-hero{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;margin-bottom:18px}
  .library-kicker{display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border-radius:999px;background:rgba(255,94,54,.1);border:1px solid rgba(255,94,54,.2);color:var(--primary);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px}
  .library-title{margin:0;font-size:clamp(28px,4vw,40px);letter-spacing:-.04em}
  .library-subtitle{margin:8px 0 0;color:var(--text-sub);max-width:720px;line-height:1.65;font-size:14px}
  .library-total-pill{display:inline-flex;align-items:center;min-height:38px;padding:8px 13px;border-radius:999px;background:var(--bg-surface-1);border:1px solid var(--border-color);color:var(--text-sub);font-size:12px;font-weight:800;white-space:nowrap}

  .library-stat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:22px 0}
  .library-stat{position:relative;overflow:hidden;padding:17px 18px;border-radius:16px;background:linear-gradient(145deg,rgba(255,255,255,.045),rgba(255,255,255,.02));border:1px solid var(--border-color)}
  .library-stat::after{content:'';position:absolute;width:80px;height:80px;border-radius:50%;right:-30px;top:-32px;background:rgba(255,94,54,.08);filter:blur(2px)}
  .library-stat-value{display:block;font-size:25px;font-weight:900;color:#fff;letter-spacing:-.03em;position:relative;z-index:1}
  .library-stat-label{display:block;margin-top:4px;color:var(--text-sub);font-size:11px;position:relative;z-index:1}

  .library-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px;margin:18px 0 22px;border-radius:16px;background:var(--bg-surface-1);border:1px solid var(--border-color)}
  .library-filters{display:flex;gap:7px;flex-wrap:wrap}
  .library-filter{min-height:38px;padding:8px 13px;border-radius:999px;border:1px solid var(--border-color);background:transparent;color:var(--text-sub);font-size:12px;font-weight:800;cursor:pointer;transition:.2s}
  .library-filter:hover,.library-filter.active{color:#fff;border-color:rgba(255,94,54,.45);background:rgba(255,94,54,.14)}
  .library-search-wrap{position:relative;min-width:240px;flex:0 1 330px}
  .library-search{width:100%;height:40px;border-radius:999px;border:1px solid var(--border-color);background:var(--bg-surface-2);color:#fff;padding:0 38px 0 14px;outline:none;font:inherit;font-size:12px}
  .library-search:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(255,94,54,.1)}
  .library-search-icon{position:absolute;right:13px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none}

  .library-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
  .library-card{position:relative;overflow:hidden;border-radius:16px;background:var(--bg-surface-1);border:1px solid var(--border-color);transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
  .library-card:hover{transform:translateY(-4px);border-color:rgba(255,94,54,.38);box-shadow:0 14px 32px rgba(0,0,0,.28)}
  .library-card.is-hidden{display:none}
  .library-card-cover-link{display:block;text-decoration:none;color:inherit}
  .library-cover{position:relative;aspect-ratio:3/4;overflow:hidden;background:#0d1016}
  .library-cover img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease}
  .library-card:hover .library-cover img{transform:scale(1.035)}
  .library-cover::after{content:'';position:absolute;inset:auto 0 0;height:42%;background:linear-gradient(to top,rgba(7,9,13,.88),transparent);pointer-events:none}
  .library-rating{position:absolute;left:9px;top:9px;z-index:2;padding:5px 8px;border-radius:999px;background:rgba(8,10,15,.76);backdrop-filter:blur(8px);color:#fbbf24;font-size:10px;font-weight:900}
  .library-unread-badge{position:absolute;left:9px;bottom:9px;z-index:3;padding:6px 9px;border-radius:999px;color:#fff;font-size:10px;font-weight:900;box-shadow:0 4px 14px rgba(0,0,0,.35)}
  .library-unread-badge.unread{background:linear-gradient(135deg,#ff5e36,#ff2a6d)}
  .library-unread-badge.caught-up{background:rgba(34,197,94,.92)}
  .library-remove{position:absolute;top:9px;right:9px;z-index:4;width:36px;height:36px;border-radius:50%;border:1px solid rgba(239,68,68,.4);background:rgba(11,14,20,.82);backdrop-filter:blur(8px);color:#f87171;cursor:pointer;transition:.2s}
  .library-remove:hover{background:#ef4444;color:#fff;border-color:#ef4444;transform:scale(1.05)}

  .library-card-body{padding:13px 13px 14px}
  .library-card-title{margin:0;color:#fff;font-size:14px;line-height:1.45;font-weight:850;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:40px}
  .library-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px;color:var(--text-sub);font-size:10.5px}
  .library-progress{margin-top:12px;padding-top:11px;border-top:1px solid rgba(255,255,255,.065)}
  .library-progress-row{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;color:var(--text-sub);font-size:10.5px}
  .library-progress-row strong{color:#fff;font-size:11px}
  .library-action{display:flex;align-items:center;justify-content:center;min-height:38px;width:100%;border-radius:10px;text-decoration:none;font-size:11.5px;font-weight:900;transition:.2s}
  .library-action.primary{background:linear-gradient(135deg,#ff5e36,#ff2a6d);color:#fff;box-shadow:0 7px 18px rgba(255,94,54,.18)}
  .library-action.primary:hover{filter:brightness(1.06);transform:translateY(-1px)}
  .library-action.secondary{background:var(--bg-surface-2);border:1px solid var(--border-color);color:var(--text-main)}
  .library-action.secondary:hover{border-color:var(--primary);color:var(--primary)}

  .library-empty-filter{display:none;text-align:center;padding:48px 18px;border:1px dashed var(--border-color);border-radius:16px;background:var(--bg-surface-1);color:var(--text-sub)}
  .library-empty-filter.visible{display:block}

  @media(max-width:1100px){.library-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
  @media(max-width:760px){
    .library-shell{padding-top:22px;padding-bottom:48px}
    .library-hero{align-items:flex-start}
    .library-title{font-size:28px}
    .library-subtitle{font-size:13px}
    .library-stat-grid{grid-template-columns:1fr 1fr;gap:9px}
    .library-stat:last-child{grid-column:1/-1}
    .library-toolbar{align-items:stretch}
    .library-filters{width:100%;overflow-x:auto;flex-wrap:nowrap;padding-bottom:2px;scrollbar-width:none}
    .library-filters::-webkit-scrollbar{display:none}
    .library-filter{flex:0 0 auto}
    .library-search-wrap{min-width:100%;flex-basis:100%}
    .library-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .library-card-body{padding:10px}
    .library-card-title{font-size:12.5px;min-height:36px}
    .library-meta{font-size:9.5px}
  }
  @media(max-width:390px){.library-grid{gap:8px}.library-unread-badge{font-size:9px;padding:5px 7px}.library-rating{font-size:9px}.library-remove{width:32px;height:32px}}
</style>
@endpush

@section('content')
<main class="page-container">
  <div class="container library-shell">
    <section class="library-hero">
      <div>
        <span class="library-kicker">📚 Không gian đọc cá nhân</span>
        <h1 class="library-title">Tủ Truyện</h1>
        <p class="library-subtitle">Theo dõi truyện, nhớ chương đã đọc và ưu tiên những bộ đang có nội dung mới để m không phải tự nhớ bằng sức mạnh tinh thần.</p>
      </div>
      <span id="library-total-label" class="library-total-pill" data-total="{{ $libraries->total() }}">{{ $libraries->total() }} bộ truyện</span>
    </section>

    @include('user._nav')

    <section class="library-stat-grid" aria-label="Thống kê tủ truyện">
      <article class="library-stat"><strong id="library-total-stat" class="library-stat-value">{{ number_format($stats['total_bookmarks'] ?? $libraries->total()) }}</strong><span class="library-stat-label">Truyện đang theo dõi</span></article>
      <article class="library-stat"><strong class="library-stat-value">{{ number_format($stats['total_read_comics'] ?? 0) }}</strong><span class="library-stat-label">Truyện từng đọc</span></article>
      <article class="library-stat"><strong class="library-stat-value" style="font-size:17px;line-height:1.5">{{ collect($stats['top_genres'] ?? [])->join(' · ') ?: 'Chưa đủ dữ liệu' }}</strong><span class="library-stat-label">Thể loại hay đọc</span></article>
    </section>

    @if($libraries->isEmpty())
      <section style="text-align:center;padding:64px 20px;background:var(--bg-surface-1);border:1px dashed var(--border-color);border-radius:18px">
        <div style="font-size:56px">📚</div>
        <h2 style="font-size:20px;margin:12px 0 6px">Tủ truyện đang trống</h2>
        <p style="color:var(--text-sub);margin:0 0 20px">Mở một bộ truyện rồi bấm “Theo Dõi Truyện”.</p>
        <a href="{{ route('genres') }}" class="btn-spotlight-read" style="text-decoration:none">Khám phá truyện</a>
      </section>
    @else
      <section class="library-toolbar" aria-label="Bộ lọc tủ truyện">
        <div class="library-filters" role="group" aria-label="Lọc trạng thái đọc">
          <button type="button" class="library-filter active" data-filter="all">Tất cả</button>
          <button type="button" class="library-filter" data-filter="unread">Có chương mới</button>
          <button type="button" class="library-filter" data-filter="caught-up">Đã đọc hết</button>
        </div>
        <label class="library-search-wrap">
          <span class="sr-only">Tìm truyện trong tủ</span>
          <input id="library-search" class="library-search" type="search" placeholder="Tìm trong tủ truyện..." autocomplete="off">
          <span class="library-search-icon" aria-hidden="true">⌕</span>
        </label>
      </section>

      <div class="library-grid" id="library-grid">
        @foreach($libraries as $item)
          @php
            $comic = $item->comic;
            $unreadCount = (int) ($item->unread_chapters_count ?? 0);
            $nextUnread = $item->nextUnreadChapter;
          @endphp
          @if($comic)
            <article class="library-card" id="library-item-{{ $comic->id }}" data-state="{{ $unreadCount > 0 ? 'unread' : 'caught-up' }}" data-title="{{ Str::lower($comic->title) }}">
              <a href="{{ route('comics.show',$comic->slug) }}" class="library-card-cover-link">
                <div class="library-cover">
                  <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" loading="lazy">
                  <span class="library-rating">★ {{ number_format($comic->avg_rating,1) }}</span>
                  @if($unreadCount > 0)
                    <span class="library-unread-badge unread">+{{ $unreadCount }} chưa đọc</span>
                  @else
                    <span class="library-unread-badge caught-up">✓ Đã đọc hết</span>
                  @endif
                </div>
              </a>

              <button type="button" class="library-remove" data-comic="{{ $comic->id }}" data-title="{{ $comic->title }}" aria-label="Bỏ theo dõi {{ $comic->title }}">✕</button>

              <div class="library-card-body">
                <a href="{{ route('comics.show',$comic->slug) }}" style="text-decoration:none;color:inherit"><h3 class="library-card-title">{{ $comic->title }}</h3></a>
                <div class="library-meta"><span>{{ ucfirst($comic->status) }}</span><span>{{ $comic->latestChapter?->label ?? 'Đang cập nhật' }}</span></div>

                <div class="library-progress">
                  @if($nextUnread)
                    <div class="library-progress-row"><span>Tiến độ</span><strong>{{ $item->lastReadChapter ? 'Đã đọc tới Ch.'.$item->lastReadChapter->chapter_number : 'Chưa bắt đầu' }}</strong></div>
                    <a href="{{ route('chapters.show',[$comic->slug,$nextUnread->slug ?: ('chapter-' . $nextUnread->chapter_number)]) }}" class="library-action primary">▶ Đọc tiếp Ch.{{ $nextUnread->chapter_number }}</a>
                  @elseif($item->lastReadChapter)
                    <div class="library-progress-row"><span>Trạng thái</span><strong>Đã theo kịp</strong></div>
                    <a href="{{ route('chapters.show',[$comic->slug,$item->lastReadChapter->slug ?: ('chapter-' . $item->lastReadChapter->chapter_number)]) }}" class="library-action secondary">↩ Đọc lại Ch.{{ $item->lastReadChapter->chapter_number }}</a>
                  @else
                    <div class="library-progress-row"><span>Tiến độ</span><strong>Chưa bắt đầu</strong></div>
                    <a href="{{ route('comics.show',$comic->slug) }}" class="library-action secondary">Xem chi tiết</a>
                  @endif
                </div>
              </div>
            </article>
          @endif
        @endforeach
      </div>

      <div id="library-empty-filter" class="library-empty-filter">
        <div style="font-size:34px;margin-bottom:8px">🔎</div>
        <strong style="display:block;color:#fff;margin-bottom:5px">Không có truyện phù hợp</strong>
        <span>Thử đổi bộ lọc hoặc từ khóa tìm kiếm.</span>
      </div>

      <div style="margin-top:26px">{{ $libraries->links() }}</div>
    @endif
  </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const grid = document.getElementById('library-grid');
  if (!grid) return;

  const cards = [...grid.querySelectorAll('.library-card')];
  const filters = [...document.querySelectorAll('.library-filter')];
  const search = document.getElementById('library-search');
  const empty = document.getElementById('library-empty-filter');
  const totalLabel = document.getElementById('library-total-label');
  const totalStat = document.getElementById('library-total-stat');
  let activeFilter = 'all';

  const normalize = (value) => (value || '').toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');

  const applyFilters = () => {
    const keyword = normalize(search?.value || '');
    let visible = 0;
    cards.forEach(card => {
      if (!card.isConnected) return;
      const stateMatches = activeFilter === 'all' || card.dataset.state === activeFilter;
      const titleMatches = !keyword || normalize(card.dataset.title).includes(keyword);
      const show = stateMatches && titleMatches;
      card.classList.toggle('is-hidden', !show);
      if (show) visible++;
    });
    empty?.classList.toggle('visible', visible === 0);
  };

  filters.forEach(button => button.addEventListener('click', () => {
    activeFilter = button.dataset.filter || 'all';
    filters.forEach(item => item.classList.toggle('active', item === button));
    applyFilters();
  }));

  search?.addEventListener('input', applyFilters);

  const updateTotal = () => {
    if (!totalLabel) return;
    const current = Math.max(0, Number(totalLabel.dataset.total || 0) - 1);
    totalLabel.dataset.total = String(current);
    totalLabel.textContent = `${current} bộ truyện`;
    if (totalStat) totalStat.textContent = new Intl.NumberFormat('vi-VN').format(current);
  };

  grid.addEventListener('click', async (event) => {
    const btn = event.target.closest('.library-remove');
    if (!btn) return;
    event.preventDefault();
    event.stopPropagation();
    if (!confirm(`Bỏ theo dõi "${btn.dataset.title}"?`)) return;

    btn.disabled = true;
    try {
      const response = await fetch(`/user/library/toggle/${encodeURIComponent(btn.dataset.comic)}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Không thể cập nhật Tủ Truyện.');

      const card = document.getElementById(`library-item-${btn.dataset.comic}`);
      if (card) {
        card.style.opacity = '0';
        card.style.transform = 'scale(.94)';
        setTimeout(() => {
          card.remove();
          updateTotal();
          applyFilters();
        }, 220);
      }
    } catch (error) {
      alert(error.message);
      btn.disabled = false;
    }
  });
})();
</script>
@endpush