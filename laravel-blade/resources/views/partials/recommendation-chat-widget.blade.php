<link rel="stylesheet" href="{{ asset('css/recommendation-chat-widget.css') }}">
<aside id="recommendation-chat-widget" aria-label="Trợ lý tìm truyện" data-endpoint="{{ route('recommendation.chat') }}" data-placeholder="{{ asset('images/default-brand.svg') }}">
  <button id="recommendation-chat-toggle" type="button" aria-label="Mở trợ lý tìm truyện" aria-expanded="false" aria-controls="recommendation-chat-panel">
    <span aria-hidden="true">🤖</span> Tìm truyện cho tôi
  </button>
  <section id="recommendation-chat-panel" role="dialog" aria-labelledby="recommendation-chat-title" hidden>
    <header class="recommendation-chat-header">
      <h2 id="recommendation-chat-title">Trợ lý tìm truyện</h2>
      <button id="recommendation-chat-close" type="button" aria-label="Đóng trợ lý tìm truyện">×</button>
    </header>
    <div id="recommendation-chat-messages" class="recommendation-chat-body" role="log" aria-label="Tin nhắn" aria-live="polite" aria-relevant="additions">
      <p class="recommendation-chat-bubble recommendation-chat-bot">Mô tả truyện bạn muốn đọc, mình sẽ giúp tìm trong thư viện.</p>
    </div>
    <p id="recommendation-chat-loading" role="status" hidden>Đang tìm truyện…</p>
    <p id="recommendation-chat-error" role="alert" hidden></p>
    <div id="recommendation-chat-chips" aria-label="Gợi ý nhanh"></div>
    <form id="recommendation-chat-form">
      <label class="recommendation-chat-input-label" for="recommendation-chat-input">Bạn muốn đọc truyện gì?</label>
      <div class="recommendation-chat-compose">
        <textarea id="recommendation-chat-input" rows="2" maxlength="4000" placeholder="Ví dụ: fantasy main mạnh, không harem"></textarea>
        <button id="recommendation-chat-send" type="submit">Gửi</button>
      </div>
    </form>
  </section>
</aside>
<script src="{{ asset('js/recommendation-chat-widget.js') }}" defer></script>
