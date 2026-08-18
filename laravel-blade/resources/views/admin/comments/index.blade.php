@extends('layouts.admin')

@section('title', 'Quản lý Bình luận')

@section('content')
<div class="ph">
  <h1>💬 Quản lý Bình luận Độc giả</h1>
  <p>Duyệt, ẩn hoặc xóa các bình luận đóng góp từ độc giả trên các bộ truyện.</p>
</div>

{{-- Thống kê nhanh --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-slate-900/60 border border-slate-800 rounded-xl p-4">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tổng Bình Luận</div>
    <div class="text-2xl font-black text-indigo-400 mt-1">1,482</div>
  </div>
  <div class="bg-slate-900/60 border border-slate-800 rounded-xl p-4">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Đã Duyệt</div>
    <div class="text-2xl font-black text-emerald-400 mt-1">1,410</div>
  </div>
  <div class="bg-slate-900/60 border border-slate-800 rounded-xl p-4">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Chờ Duyệt</div>
    <div class="text-2xl font-black text-amber-400 mt-1">54</div>
  </div>
  <div class="bg-slate-900/60 border border-slate-800 rounded-xl p-4">
    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Vi phạm / Spam</div>
    <div class="text-2xl font-black text-rose-400 mt-1">18</div>
  </div>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-xl p-5">
  {{-- Header & Thanh Bộ Lọc Nhanh --}}
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 mb-4 border-b border-slate-800">
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-sm font-bold text-slate-300 mr-2">Lọc trạng thái:</span>
      <button onclick="filterStatus('all')" class="status-tab-btn active px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 text-white transition whitespace-nowrap">
        Tất cả (1,482)
      </button>
      <button onclick="filterStatus('approved')" class="status-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 transition whitespace-nowrap">
        🟢 Đã duyệt (Approved)
      </button>
      <button onclick="filterStatus('pending')" class="status-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-amber-400 border border-amber-500/30 transition whitespace-nowrap">
        🟡 Chờ duyệt (Pending)
      </button>
      <button onclick="filterStatus('spam')" class="status-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-rose-400 border border-rose-500/30 transition whitespace-nowrap">
        🔴 Spam / Vi phạm
      </button>
    </div>

    <input type="text" id="cmt-search" placeholder="🔍 Tìm kiếm bình luận..." 
           class="px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 outline-none focus:border-indigo-500 max-w-xs"
           onkeyup="filterCmt()" />
  </div>

  {{-- Bảng Bình luận --}}
  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="border-b border-slate-800 text-xs uppercase tracking-wider text-slate-400 bg-slate-950/40">
          <th class="p-3 whitespace-nowrap">Độc giả</th>
          <th class="p-3 w-px whitespace-nowrap">Bộ truyện</th>
          <th class="p-3">Nội dung</th>
          <th class="p-3 w-px whitespace-nowrap text-center">Trạng thái</th>
          <th class="p-3 w-px whitespace-nowrap text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody id="cmt-tbody" class="divide-y divide-slate-800/60 text-sm">
        {{-- Row Approved --}}
        <tr class="cmt-row hover:bg-slate-800/30" data-status="approved">
          <td class="p-3 whitespace-nowrap">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-pink-500 flex items-center justify-center font-bold text-white text-xs shrink-0">N</div>
              <div>
                <div class="font-bold text-slate-200">Nguyễn Văn An</div>
                <div class="text-xs text-slate-400">2 phút trước</div>
              </div>
            </div>
          </td>
          <td class="p-3 w-px whitespace-nowrap">
            <strong class="text-indigo-400">Solo Leveling</strong>
            <div class="text-xs text-slate-400">Chap 200</div>
          </td>
          <td class="p-3 text-slate-300">Chapter này đỉnh quá admin ơi, combat cháy ***! 🔥🔥</td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 whitespace-nowrap">
              🟢 Approved
            </span>
          </td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <div class="flex items-center gap-1.5 justify-center">
              <button onclick="toggleStatus(this)" class="px-2.5 py-1 bg-emerald-500/15 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 rounded-md text-xs font-semibold whitespace-nowrap transition">
                🟢 Duyệt
              </button>
              <button onclick="deleteRow(this)" class="px-2.5 py-1 bg-rose-500/15 hover:bg-rose-500/30 text-rose-400 border border-rose-500/30 rounded-md text-xs font-semibold whitespace-nowrap transition">
                🗑️ Xóa
              </button>
            </div>
          </td>
        </tr>

        {{-- Row Pending --}}
        <tr class="cmt-row hover:bg-slate-800/30" data-status="pending">
          <td class="p-3 whitespace-nowrap">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-amber-500 to-rose-500 flex items-center justify-center font-bold text-white text-xs shrink-0">T</div>
              <div>
                <div class="font-bold text-slate-200">Trần Thị Bích</div>
                <div class="text-xs text-slate-400">15 phút trước</div>
              </div>
            </div>
          </td>
          <td class="p-3 w-px whitespace-nowrap">
            <strong class="text-indigo-400">Tower of God</strong>
            <div class="text-xs text-slate-400">Chap 590</div>
          </td>
          <td class="p-3 text-slate-300">Khi nào ra chapter tiếp theo vậy ạ? Hóng quá đi thôi!</td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 whitespace-nowrap">
              🟡 Pending
            </span>
          </td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <div class="flex items-center gap-1.5 justify-center">
              <button onclick="toggleStatus(this)" class="px-2.5 py-1 bg-emerald-500/15 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 rounded-md text-xs font-semibold whitespace-nowrap transition">
                🟢 Duyệt
              </button>
              <button onclick="deleteRow(this)" class="px-2.5 py-1 bg-rose-500/15 hover:bg-rose-500/30 text-rose-400 border border-rose-500/30 rounded-md text-xs font-semibold whitespace-nowrap transition">
                🗑️ Xóa
              </button>
            </div>
          </td>
        </tr>

        {{-- Row Spam --}}
        <tr class="cmt-row hover:bg-slate-800/30" data-status="spam">
          <td class="p-3 whitespace-nowrap">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-500 to-blue-500 flex items-center justify-center font-bold text-white text-xs shrink-0">L</div>
              <div>
                <div class="font-bold text-slate-200">Lê Minh Cường</div>
                <div class="text-xs text-slate-400">1 giờ trước</div>
              </div>
            </div>
          </td>
          <td class="p-3 w-px whitespace-nowrap">
            <strong class="text-indigo-400">Omniscient Reader</strong>
            <div class="text-xs text-slate-400">Chap 185</div>
          </td>
          <td class="p-3 text-slate-300">Bấm vào link xxx-test.com để nhận coin miễn phí...</td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30 whitespace-nowrap">
              🔴 Spam
            </span>
          </td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <div class="flex items-center gap-1.5 justify-center">
              <button onclick="toggleStatus(this)" class="px-2.5 py-1 bg-emerald-500/15 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 rounded-md text-xs font-semibold whitespace-nowrap transition">
                🟢 Duyệt
              </button>
              <button onclick="deleteRow(this)" class="px-2.5 py-1 bg-rose-500/15 hover:bg-rose-500/30 text-rose-400 border border-rose-500/30 rounded-md text-xs font-semibold whitespace-nowrap transition">
                🗑️ Xóa
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script>
let currentFilter = 'all';

function filterStatus(status) {
  currentFilter = status;
  
  // Highlight active tab
  document.querySelectorAll('.status-tab-btn').forEach(btn => {
    btn.classList.remove('bg-indigo-600', 'text-white');
    btn.classList.add('bg-slate-800', 'text-slate-300');
  });
  event.target.classList.remove('bg-slate-800', 'text-slate-300');
  event.target.classList.add('bg-indigo-600', 'text-white');

  filterCmt();
}

function filterCmt() {
  const query = document.getElementById('cmt-search').value.toLowerCase();
  document.querySelectorAll('.cmt-row').forEach(row => {
    const textMatch = row.innerText.toLowerCase().includes(query);
    const statusMatch = currentFilter === 'all' || row.getAttribute('data-status') === currentFilter;
    row.style.display = (textMatch && statusMatch) ? '' : 'none';
  });
}

function toggleStatus(btn) {
  const tr = btn.closest('tr');
  const badge = tr.querySelector('td:nth-child(4) span');
  const currentStatus = tr.getAttribute('data-status');

  if (currentStatus === 'approved') {
    tr.setAttribute('data-status', 'pending');
    badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 whitespace-nowrap';
    badge.innerHTML = '🟡 Pending';
  } else {
    tr.setAttribute('data-status', 'approved');
    badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 whitespace-nowrap';
    badge.innerHTML = '🟢 Approved';
  }
}

function deleteRow(btn) {
  if (confirm('Xóa bình luận này?')) {
    btn.closest('tr').remove();
  }
}
</script>
@endsection
