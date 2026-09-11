<dialog id="reader-chapter-picker" aria-labelledby="reader-picker-title">
  <div class="reader-picker-heading">
    <strong id="reader-picker-title">Chọn chapter</strong>
    <button type="button" class="reader-controls-btn" id="reader-picker-close" aria-label="Đóng danh sách chapter">✕</button>
  </div>
  <label for="reader-chapter-search">Tìm số hoặc tên chapter</label>
  <input id="reader-chapter-search" type="search" placeholder="Tìm chapter…" autocomplete="off">
  <nav class="reader-picker-list" aria-label="Danh sách chapter">
    @foreach($allChapters as $item)
      <a href="{{ route('chapters.show', [$comic->slug, $item->slug]) }}"
         @if($item->id == $chapter->id) aria-current="page" @endif>
        Ch.{{ $item->chapter_number }} — {{ $item->title }}
      </a>
    @endforeach
  </nav>
  <p id="reader-picker-empty" hidden>Không tìm thấy chapter.</p>
</dialog>
