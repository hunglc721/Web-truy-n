<link rel="stylesheet" href="{{ asset('css/recommendation-chat-widget.css') }}">
<aside id="recommendation-chat-widget" aria-label="Trợ lý tìm truyện">
  <button id="recommendation-chat-toggle" type="button" aria-label="Mở trợ lý tìm truyện" aria-expanded="false" aria-controls="recommendation-chat-panel">
    <span aria-hidden="true">🤖</span> Tìm truyện cho tôi
  </button>
  <section id="recommendation-chat-panel" role="dialog" aria-labelledby="recommendation-chat-title" hidden>
    <header class="recommendation-chat-header">
      <h2 id="recommendation-chat-title">Trợ lý tìm truyện</h2>
      <button id="recommendation-chat-close" type="button" aria-label="Đóng trợ lý tìm truyện">×</button>
    </header>
    <div class="recommendation-chat-body">
      <span class="recommendation-chat-icon" aria-hidden="true">🤖</span>
      <p>Mô tả truyện bạn muốn đọc, mình sẽ giúp tìm trong thư viện.</p>
    </div>
  </section>
</aside>
<script src="{{ asset('js/recommendation-chat-widget.js') }}" defer></script>
