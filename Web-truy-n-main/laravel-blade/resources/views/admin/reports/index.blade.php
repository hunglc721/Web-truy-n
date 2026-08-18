@extends('layouts.admin')

@section('title', 'Báo cáo Lỗi Chapter')

@section('content')
<div class="ph">
  <h1>⚠️ Danh sách Báo cáo Lỗi Chapter</h1>
  <p>Tiếp nhận và phản hồi xử lý các sự cố ảnh hỏng, sai thứ tự hoặc mất trang từ độc giả.</p>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Danh sách Báo cáo Sự cố</span>
    <span class="text-xs text-slate-400">Hiện 3 báo cáo</span>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="border-b border-slate-800 text-xs uppercase tracking-wider text-slate-400 bg-slate-900/50">
          <th class="p-3 w-px whitespace-nowrap">#ID</th>
          <th class="p-3">Bộ Truyện</th>
          <th class="p-3 whitespace-nowrap">Chapter</th>
          <th class="p-3 w-px whitespace-nowrap">Loại Lỗi</th>
          <th class="p-3">Mô tả chi tiết</th>
          <th class="p-3 w-px whitespace-nowrap text-center">Trạng thái</th>
          <th class="p-3 w-px whitespace-nowrap text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800/60 text-sm">
        <tr class="hover:bg-slate-800/30">
          <td class="p-3 font-mono text-xs text-slate-400 w-px whitespace-nowrap">#RP-104</td>
          <td class="p-3"><strong class="text-indigo-400">Demon Slayer</strong></td>
          <td class="p-3 whitespace-nowrap">Chap 205</td>
          <td class="p-3 w-px whitespace-nowrap">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30 whitespace-nowrap">
              🖼️ Ảnh hỏng (404)
            </span>
          </td>
          <td class="p-3 text-slate-300">Ảnh số 5 và số 8 không hiển thị được, bị trắng xóa.</td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 whitespace-nowrap">
              🟡 Chưa xử lý
            </span>
          </td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <button class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-md text-xs font-semibold whitespace-nowrap transition">✔️ Đã sửa</button>
          </td>
        </tr>

        <tr class="hover:bg-slate-800/30">
          <td class="p-3 font-mono text-xs text-slate-400 w-px whitespace-nowrap">#RP-103</td>
          <td class="p-3"><strong class="text-indigo-400">Jujutsu Kaisen</strong></td>
          <td class="p-3 whitespace-nowrap">Chap 254</td>
          <td class="p-3 w-px whitespace-nowrap">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 whitespace-nowrap">
              🔄 Sai thứ tự trang
            </span>
          </td>
          <td class="p-3 text-slate-300">Trang 12 bị đảo lên trước trang 10.</td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 whitespace-nowrap">
              🟡 Chưa xử lý
            </span>
          </td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <button class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-md text-xs font-semibold whitespace-nowrap transition">✔️ Đã sửa</button>
          </td>
        </tr>

        <tr class="hover:bg-slate-800/30">
          <td class="p-3 font-mono text-xs text-slate-400 w-px whitespace-nowrap">#RP-102</td>
          <td class="p-3"><strong class="text-indigo-400">Solo Leveling</strong></td>
          <td class="p-3 whitespace-nowrap">Chap 198</td>
          <td class="p-3 w-px whitespace-nowrap">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30 whitespace-nowrap">
              📄 Thiếu trang
            </span>
          </td>
          <td class="p-3 text-slate-300">Thiếu mất 2 trang cuối của chapter.</td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 whitespace-nowrap">
              🟢 Đã khắc phục
            </span>
          </td>
          <td class="p-3 w-px whitespace-nowrap text-center">
            <button class="px-3 py-1.5 bg-slate-800 text-slate-500 rounded-md text-xs font-semibold whitespace-nowrap cursor-not-allowed" disabled>Đã xong</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
@endsection
