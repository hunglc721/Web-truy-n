@if ($paginator->hasPages())
  <div class="pagination-wrapper">
    <div class="pagination-info">
      Hiển thị <span>{{ $paginator->firstItem() ?? 0 }}</span>–<span>{{ $paginator->lastItem() ?? 0 }}</span> trong <span>{{ $paginator->total() }}</span> kết quả
    </div>

    <nav class="pagination-nav" role="navigation" aria-label="Phân trang">
      <ul class="pagination-list">
        {{-- Nút Trang Trước --}}
        @if ($paginator->onFirstPage())
          <li class="page-item disabled" aria-disabled="true" aria-label="Trang trước">
            <span class="page-link page-arrow" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </span>
          </li>
        @else
          <li class="page-item">
            <a class="page-link page-arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
          </li>
        @endif

        {{-- Danh sách trang --}}
        @foreach ($elements as $element)
          {{-- Dấu "..." --}}
          @if (is_string($element))
            <li class="page-item disabled" aria-disabled="true">
              <span class="page-link page-dots">{{ $element }}</span>
            </li>
          @endif

          {{-- Các trang số --}}
          @if (is_array($element))
            @foreach ($element as $page => $url)
              @if ($page == $paginator->currentPage())
                <li class="page-item active" aria-current="page">
                  <span class="page-link">{{ $page }}</span>
                </li>
              @else
                <li class="page-item">
                  <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
              @endif
            @endforeach
          @endif
        @endforeach

        {{-- Nút Trang Sau --}}
        @if ($paginator->hasMorePages())
          <li class="page-item">
            <a class="page-link page-arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </li>
        @else
          <li class="page-item disabled" aria-disabled="true" aria-label="Trang sau">
            <span class="page-link page-arrow" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
          </li>
        @endif
      </ul>
    </nav>
  </div>
@endif
