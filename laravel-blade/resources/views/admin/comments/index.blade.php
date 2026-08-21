@extends('layouts.admin')

@section('title', 'Quản lý Bình luận')

@section('content')
<div class="ph">
  <h1>💬 Quản lý & Kiểm duyệt Bình luận</h1>
  <p>Duyệt, ẩn, xóa mềm các bình luận từ độc giả và khóa nhanh tài khoản vi phạm.</p>
</div>

{{-- Thống kê nhanh --}}
<div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
  <a href="{{ route('admin.comments.index', ['status' => 'all']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'all' ? 'ring-2 ring-indigo-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tổng BL</div>
    <div class="text-2xl font-black text-indigo-400 mt-1">{{ number_format($stats['total']) }}</div>
  </a>
  <a href="{{ route('admin.comments.index', ['status' => 'approved']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'approved' ? 'ring-2 ring-emerald-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Đã Duyệt</div>
    <div class="text-2xl font-black text-emerald-400 mt-1">{{ number_format($stats['approved']) }}</div>
  </a>
  <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'pending' ? 'ring-2 ring-amber-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Chờ Duyệt</div>
    <div class="text-2xl font-black text-amber-400 mt-1">{{ number_format($stats['pending']) }}</div>
  </a>
  <a href="{{ route('admin.comments.index', ['status' => 'hidden']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'hidden' ? 'ring-2 ring-rose-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Đã Ẩn</div>
    <div class="text-2xl font-black text-rose-400 mt-1">{{ number_format($stats['hidden']) }}</div>
  </a>
  <a href="{{ route('admin.comments.index', ['status' => 'reported']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'reported' ? 'ring-2 ring-purple-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Bị Báo Cáo</div>
    <div class="text-2xl font-black text-purple-400 mt-1">{{ number_format($stats['reported']) }}</div>
  </a>
  <a href="{{ route('admin.comments.index', ['status' => 'trashed']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'trashed' ? 'ring-2 ring-slate-400' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Thùng Rác</div>
    <div class="text-2xl font-black text-slate-400 mt-1">{{ number_format($stats['trashed']) }}</div>
  </a>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
  {{-- Header & Bộ lọc Tabs + Tìm kiếm --}}
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 mb-4 border-b border-slate-800">
    <div class="flex items-center gap-1.5 flex-wrap">
      <a href="{{ route('admin.comments.index', ['status' => 'all', 'search' => request('search')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-300' }}">
        Tất cả ({{ $stats['total'] }})
      </a>
      <a href="{{ route('admin.comments.index', ['status' => 'approved', 'search' => request('search')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'approved' ? 'bg-emerald-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30' }}">
        🟢 Đã duyệt ({{ $stats['approved'] }})
      </a>
      <a href="{{ route('admin.comments.index', ['status' => 'pending', 'search' => request('search')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-amber-400 border border-amber-500/30' }}">
        🟡 Chờ duyệt ({{ $stats['pending'] }})
      </a>
      <a href="{{ route('admin.comments.index', ['status' => 'hidden', 'search' => request('search')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'hidden' ? 'bg-rose-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-rose-400 border border-rose-500/30' }}">
        🔒 Đã ẩn ({{ $stats['hidden'] }})
      </a>
      <a href="{{ route('admin.comments.index', ['status' => 'reported', 'search' => request('search')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'reported' ? 'bg-purple-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-purple-400 border border-purple-500/30' }}">
        🚨 Bị báo cáo ({{ $stats['reported'] }})
      </a>
      <a href="{{ route('admin.comments.index', ['status' => 'trashed', 'search' => request('search')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'trashed' ? 'bg-slate-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-400 border border-slate-700' }}">
        🗑️ Thùng rác ({{ $stats['trashed'] }})
      </a>
    </div>

    {{-- Form Tìm kiếm --}}
    <form method="GET" action="{{ route('admin.comments.index') }}" class="flex items-center gap-2">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tìm user, nội dung, truyện..." 
             class="px-3.5 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 outline-none focus:border-indigo-500 w-64" />
      <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-xs transition">
        Tìm
      </button>
      @if(request('search'))
        <a href="{{ route('admin.comments.index', ['status' => $statusFilter]) }}" class="px-2 py-1.5 bg-slate-800 text-slate-400 hover:text-white rounded-lg text-xs">✕</a>
      @endif
    </form>
  </div>

  {{-- Bảng Bình luận --}}
  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="border-b border-slate-800 text-xs uppercase tracking-wider text-slate-400 bg-slate-950/40">
          <th class="p-3 whitespace-nowrap">Độc giả</th>
          <th class="p-3 whitespace-nowrap">Nội dung bình luận</th>
          <th class="p-3 whitespace-nowrap">Vị trí (Truyện / Chap)</th>
          <th class="p-3 whitespace-nowrap text-center">Trạng thái</th>
          <th class="p-3 whitespace-nowrap text-center">Thời gian</th>
          <th class="p-3 whitespace-nowrap text-right">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800/60 text-sm">
        @forelse($comments as $cmt)
          <tr class="hover:bg-slate-800/30 transition">
            {{-- Độc giả --}}
            <td class="p-3 whitespace-nowrap align-top">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-pink-500 flex items-center justify-center font-bold text-white text-xs shrink-0">
                  {{ strtoupper(substr($cmt->user->name ?? 'G', 0, 1)) }}
                </div>
                <div>
                  <div class="font-bold text-slate-200">
                    {{ $cmt->user->name ?? 'Người dùng đã xóa' }}
                  </div>
                  @if($cmt->user?->isBanned())
                    <span class="inline-block mt-0.5 px-1.5 py-0.5 bg-rose-500/20 text-rose-400 border border-rose-500/30 rounded text-[10px] font-bold">
                      🚫 Bị Khóa
                    </span>
                  @elseif($cmt->user?->isAdmin())
                    <span class="inline-block mt-0.5 px-1.5 py-0.5 bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 rounded text-[10px] font-bold">
                      ⭐ Admin
                    </span>
                  @else
                    <div class="text-[11px] text-slate-500">{{ $cmt->user->email ?? '—' }}</div>
                  @endif
                </div>
              </div>
            </td>

            {{-- Nội dung --}}
            <td class="p-3 align-top max-w-xs">
              @if($cmt->parent)
                <div class="text-xs text-indigo-400/80 mb-1 flex items-center gap-1">
                  <span>↳ Trả lời</span> <strong>{{ $cmt->parent->user->name ?? 'thành viên' }}</strong>:
                  <span class="text-slate-500 italic truncate max-w-[200px]">"{{ Str::limit($cmt->parent->content, 30) }}"</span>
                </div>
              @endif
              <div class="text-slate-200 break-words leading-relaxed font-medium">
                {{ $cmt->content }}
              </div>
              @if($cmt->reports && $cmt->reports->isNotEmpty())
                <div class="mt-1 text-xs text-rose-400 font-semibold flex items-center gap-1">
                  ⚠️ Có {{ $cmt->reports->count() }} lượt báo cáo vi phạm
                </div>
              @endif
            </td>

            {{-- Truyện / Chapter --}}
            <td class="p-3 whitespace-nowrap align-top">
              @if($cmt->comic)
                <a href="{{ route('comics.show', $cmt->comic->slug) }}" target="_blank" class="font-bold text-indigo-400 hover:underline block max-w-[180px] truncate">
                  {{ $cmt->comic->title }}
                </a>
                <div class="text-xs text-slate-400 mt-0.5">
                  @if($cmt->chapter)
                    <a href="{{ route('chapters.show', [$cmt->comic->slug, $cmt->chapter->slug]) }}" target="_blank" class="hover:text-slate-300">
                      Ch.{{ $cmt->chapter->chapter_number }}
                    </a>
                  @else
                    <span class="italic text-slate-500">Trang chi tiết truyện</span>
                  @endif
                </div>
              @else
                <span class="text-slate-500 italic">Truyện đã xóa</span>
              @endif
            </td>

            {{-- Trạng thái --}}
            <td class="p-3 whitespace-nowrap text-center align-top">
              @if($cmt->trashed())
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">
                  🗑️ Đã xóa mềm
                </span>
              @elseif($cmt->status === 'approved')
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                  🟢 Approved
                </span>
              @elseif($cmt->status === 'pending')
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                  🟡 Pending
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">
                  🔒 Hidden
                </span>
              @endif
            </td>

            {{-- Thời gian --}}
            <td class="p-3 whitespace-nowrap text-xs text-slate-400 text-center align-top">
              {{ $cmt->time_ago }}
            </td>

            {{-- Thao tác --}}
            <td class="p-3 whitespace-nowrap text-right align-top">
              <div class="flex items-center justify-end gap-1.5 flex-wrap">
                @if($cmt->trashed())
                  {{-- Nút Khôi phục --}}
                  <form method="POST" action="{{ route('admin.comments.restore', $cmt->id) }}" onsubmit="return confirm('Khôi phục bình luận này?');">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 bg-indigo-500/15 hover:bg-indigo-500/30 text-indigo-400 border border-indigo-500/30 rounded-md text-xs font-semibold transition">
                      ♻️ Khôi phục
                    </button>
                  </form>
                @else
                  {{-- Duyệt / Bỏ duyệt --}}
                  @if($cmt->status !== 'approved')
                    <form method="POST" action="{{ route('admin.comments.approve', $cmt) }}">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="px-2.5 py-1 bg-emerald-500/15 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 rounded-md text-xs font-semibold transition">
                        🟢 Duyệt
                      </button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('admin.comments.hide', $cmt) }}">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="px-2.5 py-1 bg-amber-500/15 hover:bg-amber-500/30 text-amber-400 border border-amber-500/30 rounded-md text-xs font-semibold transition">
                        🔒 Ẩn
                      </button>
                    </form>
                  @endif

                  {{-- Xóa mềm --}}
                  <form method="POST" action="{{ route('admin.comments.destroy', $cmt) }}" onsubmit="return confirm('Xóa mềm bình luận này? (Vẫn có thể khôi phục)');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-2.5 py-1 bg-rose-500/15 hover:bg-rose-500/30 text-rose-400 border border-rose-500/30 rounded-md text-xs font-semibold transition" title="Xóa mềm">
                      🗑️ Xóa
                    </button>
                  </form>

                  {{-- Ban nhanh tác giả --}}
                  @if($cmt->user && !$cmt->user->isAdmin() && !$cmt->user->isBanned())
                    <form method="POST" action="{{ route('admin.comments.banUser', $cmt) }}" onsubmit="return confirm('Khóa vĩnh viễn tài khoản của user này và ẩn bình luận?');">
                      @csrf
                      <button type="submit" class="px-2 py-1 bg-slate-800 hover:bg-rose-900/60 text-slate-400 hover:text-rose-300 border border-slate-700 rounded-md text-xs font-semibold transition" title="Khóa tài khoản">
                        🚫 Ban
                      </button>
                    </form>
                  @endif
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="p-12 text-center text-slate-500">
              <div class="text-4xl mb-2">💬</div>
              <p class="font-semibold text-slate-400">Không tìm thấy bình luận nào trong danh mục này.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Phân trang --}}
  @if($comments->hasPages())
    <div class="mt-5 pt-4 border-t border-slate-800">
      {{ $comments->links() }}
    </div>
  @endif
</div>
@endsection
