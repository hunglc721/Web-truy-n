@extends('layouts.admin')

@section('title', 'Báo cáo Lỗi Chapter')

@section('content')
<div class="ph">
  <h1>⚠️ Trung Tâm Xử Lý Báo Cáo Sự Cố (Report Center)</h1>
  <p>Tiếp nhận và xử lý sự cố ảnh hỏng từ độc giả theo luồng trạng thái 3 bước, hỗ trợ nhảy trực tiếp tới trang ảnh bị lỗi.</p>
</div>

{{-- Thống kê nhanh --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
  <a href="{{ route('admin.reports.index', ['status' => 'all']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'all' ? 'ring-2 ring-indigo-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tổng Báo Cáo</div>
    <div class="text-2xl font-black text-indigo-400 mt-1">{{ number_format($stats['total']) }}</div>
  </a>
  <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'pending' ? 'ring-2 ring-amber-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">⏳ Chưa Xử Lý</div>
    <div class="text-2xl font-black text-amber-400 mt-1">{{ number_format($stats['pending']) }}</div>
  </a>
  <a href="{{ route('admin.reports.index', ['status' => 'processing']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'processing' ? 'ring-2 ring-blue-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">🔄 Đang Xử Lý</div>
    <div class="text-2xl font-black text-blue-400 mt-1">{{ number_format($stats['processing']) }}</div>
  </a>
  <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'resolved' ? 'ring-2 ring-emerald-500' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">✅ Đã Khắc Phục</div>
    <div class="text-2xl font-black text-emerald-400 mt-1">{{ number_format($stats['resolved']) }}</div>
  </a>
  <a href="{{ route('admin.reports.index', ['status' => 'dismissed']) }}" class="bg-slate-900/70 hover:bg-slate-850 border border-slate-800 rounded-xl p-3.5 transition {{ $statusFilter === 'dismissed' ? 'ring-2 ring-slate-400' : '' }}">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">⚪ Đã Bác Bỏ</div>
    <div class="text-2xl font-black text-slate-400 mt-1">{{ number_format($stats['dismissed']) }}</div>
  </a>
</div>

{{-- Luồng xử lý mẫu --}}
<div class="bg-slate-900/50 border border-slate-800 rounded-xl px-4 py-3 mb-6 flex items-center gap-3 text-xs text-slate-400 flex-wrap">
  <span class="font-bold text-slate-300">Luồng xử lý chuẩn:</span>
  <span class="px-2.5 py-1 rounded-full font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">1. Chưa xử lý (Pending)</span>
  <span>➔</span>
  <span class="px-2.5 py-1 rounded-full font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30">2. Đang kiểm tra / Sửa ảnh (Processing)</span>
  <span>➔</span>
  <span class="px-2.5 py-1 rounded-full font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">3. Đã khắc phục xong (Resolved)</span>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
  {{-- Header & Bộ lọc Tabs + Tìm kiếm --}}
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 mb-4 border-b border-slate-800">
    <div class="flex items-center gap-1.5 flex-wrap">
      <a href="{{ route('admin.reports.index', ['status' => 'all', 'search' => request('search'), 'type' => request('type')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-300' }}">
        Tất cả ({{ $stats['total'] }})
      </a>
      <a href="{{ route('admin.reports.index', ['status' => 'pending', 'search' => request('search'), 'type' => request('type')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-amber-400 border border-amber-500/30' }}">
        ⏳ Chưa xử lý ({{ $stats['pending'] }})
      </a>
      <a href="{{ route('admin.reports.index', ['status' => 'processing', 'search' => request('search'), 'type' => request('type')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'processing' ? 'bg-blue-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-blue-400 border border-blue-500/30' }}">
        🔄 Đang xử lý ({{ $stats['processing'] }})
      </a>
      <a href="{{ route('admin.reports.index', ['status' => 'resolved', 'search' => request('search'), 'type' => request('type')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30' }}">
        ✅ Đã khắc phục ({{ $stats['resolved'] }})
      </a>
      <a href="{{ route('admin.reports.index', ['status' => 'dismissed', 'search' => request('search'), 'type' => request('type')]) }}"
         class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $statusFilter === 'dismissed' ? 'bg-slate-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-400 border border-slate-700' }}">
        ⚪ Đã bác bỏ ({{ $stats['dismissed'] }})
      </a>
    </div>

    {{-- Form Tìm kiếm & Lọc Loại lỗi --}}
    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2 flex-wrap">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      
      <select name="type" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-200 outline-none">
        <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>Tất cả loại lỗi</option>
        <option value="broken_image" {{ request('type') == 'broken_image' ? 'selected' : '' }}>🖼️ Ảnh hỏng (404)</option>
        <option value="wrong_order" {{ request('type') == 'wrong_order' ? 'selected' : '' }}>🔄 Sai thứ tự trang</option>
        <option value="missing_page" {{ request('type') == 'missing_page' ? 'selected' : '' }}>📄 Thiếu trang</option>
        <option value="content_error" {{ request('type') == 'content_error' ? 'selected' : '' }}>⚠️ Sai nội dung / Dịch</option>
      </select>

      <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tìm truyện, mô tả, IP..." 
             class="px-3.5 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-200 outline-none focus:border-indigo-500 w-52" />
      <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-xs transition">
        Tìm
      </button>
      @if(request('search') || request('type'))
        <a href="{{ route('admin.reports.index', ['status' => $statusFilter]) }}" class="px-2 py-1.5 bg-slate-800 text-slate-400 hover:text-white rounded-lg text-xs">✕</a>
      @endif
    </form>
  </div>

  {{-- Bảng Báo cáo --}}
  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="border-b border-slate-800 text-xs uppercase tracking-wider text-slate-400 bg-slate-950/40">
          <th class="p-3 whitespace-nowrap">#ID / Thời gian</th>
          <th class="p-3 whitespace-nowrap">Vị trí Lỗi (Truyện / Chap / Trang)</th>
          <th class="p-3 whitespace-nowrap">Loại sự cố</th>
          <th class="p-3 whitespace-nowrap">Người báo</th>
          <th class="p-3 whitespace-nowrap text-center">Trạng thái</th>
          <th class="p-3 whitespace-nowrap text-right">Hành động Xử lý</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800/60 text-sm">
        @forelse($reports as $rpt)
          <tr class="hover:bg-slate-800/30 transition">
            {{-- ID & Time --}}
            <td class="p-3 whitespace-nowrap align-top">
              <div class="font-mono text-xs font-bold text-indigo-400">#RP-{{ str_pad($rpt->id, 4, '0', STR_PAD_LEFT) }}</div>
              <div class="text-xs text-slate-400 mt-1">{{ $rpt->time_ago }}</div>
            </td>

            {{-- Truyện / Chapter / Trang lỗi --}}
            <td class="p-3 align-top max-w-xs">
              @if($rpt->comic && $rpt->chapter)
                <div class="font-bold text-slate-200 truncate max-w-[200px]" title="{{ $rpt->comic->title }}">
                  {{ $rpt->comic->title }}
                </div>
                <div class="text-xs text-slate-400 mt-0.5">
                  Ch.{{ $rpt->chapter->chapter_number }} — {{ Str::limit($rpt->chapter->title ?: 'Chương ' . $rpt->chapter->chapter_number, 20) }}
                </div>

                {{-- Nút nhảy trực tiếp tới trang ảnh bị lỗi (FE-03 & BE-10) --}}
                @php
                  $rptChapSlug  = $rpt->chapter->slug ?: ('chapter-' . ($rpt->chapter->chapter_number ?? 1));
                  $rptComicSlug = $rpt->comic->slug ?: ('comic-' . $rpt->comic->id);
                  $readerUrl    = route('chapters.show', [$rptComicSlug, $rptChapSlug]) . ($rpt->page_number ? '#page-' . $rpt->page_number : '');
                @endphp
                <div class="mt-2 flex items-center gap-2">
                  <a href="{{ $readerUrl }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-500/20 hover:bg-indigo-500/35 text-indigo-300 border border-indigo-500/30 rounded text-xs font-bold transition" title="Mở reader và cuộn trực tiếp tới trang lỗi">
                    🎯 {{ $rpt->page_number ? 'Trang ' . $rpt->page_number : 'Xem Chapter' }} ↗
                  </a>
                  @if($rpt->image_url)
                    <a href="{{ $rpt->image_url }}" target="_blank" class="text-[11px] text-slate-400 hover:text-slate-200 underline" title="{{ $rpt->image_url }}">
                      Link ảnh gốc
                    </a>
                  @endif
                </div>
              @elseif($rpt->comic)
                <strong class="text-indigo-400">{{ $rpt->comic->title }}</strong>
                <div class="text-xs text-slate-400">Trang chi tiết truyện</div>
              @else
                <span class="text-slate-500 italic">Dữ liệu truyện đã bị xóa</span>
              @endif
            </td>

            {{-- Loại sự cố & Mô tả --}}
            <td class="p-3 align-top max-w-xs">
              <div>
                <span class="inline-block px-2 py-0.5 rounded text-xs font-bold {{ $rpt->type === 'broken_image' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                  {{ $rpt->type_label }}
                </span>
              </div>
              @if($rpt->description)
                <div class="text-xs text-slate-300 mt-1.5 bg-slate-950/60 p-2 rounded border border-slate-800 break-words">
                  "{{ $rpt->description }}"
                </div>
              @endif
              @if($rpt->admin_note)
                <div class="text-[11px] text-emerald-400 mt-1 font-medium">
                  💬 Ghi chú Admin: {{ $rpt->admin_note }}
                </div>
              @endif
            </td>

            {{-- Người báo --}}
            <td class="p-3 whitespace-nowrap align-top">
              @if($rpt->user)
                <div class="font-bold text-slate-200 text-xs">{{ $rpt->user->name }}</div>
                <div class="text-[11px] text-slate-400">{{ $rpt->user->email }}</div>
              @else
                <div class="text-xs text-slate-400 italic">Khách vãng lai</div>
              @endif
              @if($rpt->ip_address)
                <div class="text-[10px] font-mono text-slate-500 mt-0.5">IP: {{ $rpt->ip_address }}</div>
              @endif
            </td>

            {{-- Trạng thái --}}
            <td class="p-3 whitespace-nowrap text-center align-top">
              @if($rpt->status === 'pending')
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                  🟡 Chưa xử lý
                </span>
              @elseif($rpt->status === 'processing')
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30">
                  🔄 Đang xử lý
                </span>
              @elseif($rpt->status === 'resolved')
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                  ✅ Đã khắc phục
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">
                  ⚪ Đã bác bỏ
                </span>
              @endif
            </td>

            {{-- Thao tác thay đổi trạng thái --}}
            <td class="p-3 whitespace-nowrap text-right align-top">
              <div class="flex items-center justify-end gap-1.5 flex-wrap">
                {{-- Form Chuyển sang Đang xử lý --}}
                @if($rpt->status === 'pending')
                  <form method="POST" action="{{ route('admin.reports.updateStatus', $rpt) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="processing">
                    <button type="submit" class="px-2.5 py-1 bg-blue-500/15 hover:bg-blue-500/30 text-blue-400 border border-blue-500/30 rounded-md text-xs font-semibold transition" title="Bắt đầu xử lý">
                      🔄 Xử lý
                    </button>
                  </form>
                @endif

                {{-- Form Đã khắc phục --}}
                @if($rpt->status !== 'resolved')
                  <form method="POST" action="{{ route('admin.reports.updateStatus', $rpt) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="resolved">
                    <button type="submit" class="px-2.5 py-1 bg-emerald-500/15 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 rounded-md text-xs font-semibold transition" title="Đánh dấu đã sửa xong">
                      ✅ Đã sửa
                    </button>
                  </form>
                @endif

                {{-- Form Mở lại / Bác bỏ --}}
                @if($rpt->status === 'resolved' || $rpt->status === 'dismissed')
                  <form method="POST" action="{{ route('admin.reports.updateStatus', $rpt) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-md text-xs font-semibold transition" title="Mở lại báo cáo">
                      ↩ Mở lại
                    </button>
                  </form>
                @elseif($rpt->status !== 'dismissed')
                  <form method="POST" action="{{ route('admin.reports.updateStatus', $rpt) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="dismissed">
                    <button type="submit" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-400 border border-slate-700 rounded-md text-xs font-semibold transition" title="Bác bỏ báo cáo">
                      ✕ Bác bỏ
                    </button>
                  </form>
                @endif

                {{-- Xóa báo cáo --}}
                <form method="POST" action="{{ route('admin.reports.destroy', $rpt) }}" onsubmit="return confirm('Xóa bản ghi báo cáo sự cố này?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 border border-rose-500/20 rounded-md text-xs font-semibold transition" title="Xóa">
                    🗑️
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="p-12 text-center text-slate-500">
              <div class="text-4xl mb-2">📭</div>
              <p class="font-semibold text-slate-400">Không có báo cáo sự cố nào trong danh mục này.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Phân trang --}}
  @if($reports->hasPages())
    <div class="mt-5 pt-4 border-t border-slate-800">
      {{ $reports->links() }}
    </div>
  @endif
</div>
@endsection
