@extends('layouts.main')

@section('title', 'Khu vực thành viên - WebComics')

@push('styles')
<style>
  @media (max-width: 768px) {
    .user-dashboard-two-col { grid-template-columns: 1fr !important; }
    .user-dashboard-stats { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    .user-dashboard-weekly { gap: 4px !important; }
    .user-dashboard-weekly strong { font-size: 10px !important; }
  }
</style>
@endpush

@section('content')
<main class="page-container">
    <div class="container" style="padding-top:32px;padding-bottom:56px;">
        <div class="page-header" style="margin-bottom:18px;">
            <div>
                <h1 style="font-size:28px;margin:0 0 6px;">👋 Xin chào, {{ $user->name }}</h1>
                <p style="margin:0;color:var(--text-sub);">Tất cả hoạt động đọc truyện của m được gom về một chỗ, cuối cùng cũng đỡ phải đi săn từng nút như chơi trốn tìm.</p>
            </div>
        </div>

        @include('user._nav')

        <section class="user-dashboard-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:12px;margin-bottom:24px;">
            @foreach([
                ['📚','Tủ truyện',$overview['total_library_comics']],
                ['📖','Chương đã đọc',$overview['total_chapters_read']],
                ['❤️','Truyện đã thích',$overview['total_likes']],
                ['💬','Bình luận',$overview['total_comments']],
                ['⭐','Đánh giá',$overview['total_ratings']],
                ['🔥','Chuỗi ngày đọc',$overview['reading_streak_days']],
            ] as [$icon,$label,$value])
                <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:16px;">
                    <div style="font-size:22px;">{{ $icon }}</div>
                    <div style="font-size:25px;font-weight:900;margin:8px 0 2px;">{{ number_format($value) }}</div>
                    <div style="font-size:12px;color:var(--text-sub);font-weight:600;">{{ $label }}</div>
                </div>
            @endforeach
        </section>

        <section class="user-dashboard-two-col" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(280px,.7fr);gap:18px;margin-bottom:24px;">
            <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;">
                    <div>
                        <div style="font-size:12px;color:var(--text-sub);font-weight:700;">CẤP BẬC ĐỘC GIẢ</div>
                        <h2 style="margin:4px 0 0;font-size:19px;">{{ $overview['reader_tier']['icon'] }} {{ $overview['reader_tier']['name'] }}</h2>
                    </div>
                    <strong>Lv.{{ $overview['reader_tier']['level'] }}</strong>
                </div>
                <div style="height:10px;background:rgba(255,255,255,.06);border-radius:999px;overflow:hidden;">
                    <div style="height:100%;width:{{ $overview['reader_tier']['progress_percent'] }}%;background:linear-gradient(90deg,#6c63ff,#ff2a6d);"></div>
                </div>
                <p style="font-size:12px;color:var(--text-sub);margin:8px 0 0;">Mốc tiếp theo: {{ $overview['reader_tier']['next_level_chapters'] }} chương.</p>
            </div>

            <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:18px;">
                <div style="font-size:12px;color:var(--text-sub);font-weight:700;margin-bottom:10px;">THỂ LOẠI HAY ĐỌC</div>
                @forelse($favoriteGenres as $genre)
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;"><span>{{ $genre['genre'] }}</span><strong>{{ $genre['percentage'] }}%</strong></div>
                        <div style="height:6px;background:rgba(255,255,255,.06);border-radius:99px;overflow:hidden;"><div style="height:100%;width:{{ $genre['percentage'] }}%;background:var(--primary);"></div></div>
                    </div>
                @empty
                    <p style="color:var(--text-sub);font-size:13px;">Đọc thêm vài bộ rồi hệ thống mới có thứ để thống kê.</p>
                @endforelse
            </div>
        </section>

        <section class="user-dashboard-two-col" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px;margin-bottom:24px;">
            <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;"><h2 style="font-size:17px;margin:0;">🕘 Đọc gần đây</h2><a href="{{ route('user.history') }}">Xem tất cả</a></div>
                @forelse($recentHistory as $item)
                    @if($item->comic && $item->chapter)
                        <a href="{{ route('chapters.show', [$item->comic->slug, $item->chapter->slug ?: ('chapter-' . $item->chapter->chapter_number)]) }}" style="display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;">
                            <img src="{{ $item->comic->cover_image }}" alt="" style="width:42px;height:56px;object-fit:cover;border-radius:7px;">
                            <div style="min-width:0;flex:1;"><strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $item->comic->title }}</strong><span style="font-size:12px;color:var(--text-sub);">Ch.{{ $item->chapter->chapter_number }} · {{ round($item->scroll_percent ?? 0) }}%</span></div>
                        </a>
                    @endif
                @empty
                    <p style="color:var(--text-sub);font-size:13px;">Chưa có lịch sử đọc.</p>
                @endforelse
            </div>

            <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;"><h2 style="font-size:17px;margin:0;">❤️ Yêu thích gần đây</h2><a href="{{ route('user.likes') }}">Xem tất cả</a></div>
                @forelse($recentLikes as $item)
                    @if($item->comic)
                        <a href="{{ route('comics.show', $item->comic->slug) }}" style="display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;">
                            <img src="{{ $item->comic->cover_image }}" alt="" style="width:42px;height:56px;object-fit:cover;border-radius:7px;">
                            <div style="min-width:0;flex:1;"><strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $item->comic->title }}</strong><span style="font-size:12px;color:var(--text-sub);">★ {{ number_format($item->comic->avg_rating,1) }} · {{ ucfirst($item->comic->status) }}</span></div>
                        </a>
                    @endif
                @empty
                    <p style="color:var(--text-sub);font-size:13px;">Chưa thích bộ nào.</p>
                @endforelse
            </div>
        </section>

        <section style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:18px;margin-bottom:24px;">
            <h2 style="font-size:17px;margin:0 0 14px;">📈 Hoạt động 7 ngày</h2>
            <div class="user-dashboard-weekly" style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;align-items:end;min-height:150px;">
                @php($maxWeekly = max(1, collect($weekly)->max('count')))
                @foreach($weekly as $day)
                    <div style="text-align:center;min-width:0;">
                        <div title="{{ $day['count'] }} chương" style="height:{{ max(8, round(($day['count'] / $maxWeekly) * 105)) }}px;background:linear-gradient(180deg,#8b5cf6,#6c63ff);border-radius:8px 8px 3px 3px;margin-bottom:7px;"></div>
                        <strong style="font-size:11px;">{{ $day['day_name'] }}</strong>
                        <div style="font-size:10px;color:var(--text-sub);">{{ $day['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:18px;">
            <h2 style="font-size:17px;margin:0 0 14px;">🏅 Huy hiệu</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
                @foreach($badges as $badge)
                    <div style="padding:14px;border:1px solid var(--border);border-radius:12px;opacity:{{ $badge['is_unlocked'] ? '1' : '.45' }};background:rgba(255,255,255,.025);">
                        <div style="font-size:25px;">{{ $badge['icon'] }}</div>
                        <strong style="display:block;margin:6px 0 4px;">{{ $badge['name'] }}</strong>
                        <span style="font-size:11px;color:var(--text-sub);">{{ $badge['description'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</main>
@endsection