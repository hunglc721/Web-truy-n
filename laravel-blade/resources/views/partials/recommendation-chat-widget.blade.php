<link rel="stylesheet" href="{{ asset('css/recommendation-chat-widget.css') }}?v=9">
<aside id="recommendation-chat-widget" aria-label="Trợ lý tìm truyện" data-history-scope="{{ auth()->check() ? 'user:'.auth()->id() : 'guest' }}" data-endpoint="{{ route('recommendation.chat') }}" data-placeholder="{{ asset('images/default-brand.svg') }}">
  <button id="recommendation-chat-toggle" type="button" aria-label="Mở Trợ Lý Comics" aria-expanded="false" aria-controls="recommendation-chat-panel" title="Trợ Lý Comics">
    <span class="recommendation-toggle-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect width="18" height="12" x="3" y="6" rx="3"></rect>
        <circle cx="9" cy="12" r="1.5" fill="currentColor"></circle>
        <circle cx="15" cy="12" r="1.5" fill="currentColor"></circle>
        <path d="M12 2v4"></path>
      </svg>
    </span>
    <span class="recommendation-fab-tooltip">Trợ Lý Comics</span>
  </button>

  <section id="recommendation-chat-panel" role="dialog" aria-labelledby="recommendation-chat-title" hidden>
    
    <!-- AI Header -->
    <header class="recommendation-chat-header">
      <div class="recommendation-chat-brand">
        <div class="recommendation-chat-avatar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect width="18" height="12" x="3" y="6" rx="3"></rect>
            <circle cx="9" cy="12" r="1.5" fill="currentColor"></circle>
            <circle cx="15" cy="12" r="1.5" fill="currentColor"></circle>
            <path d="M12 2v4"></path>
          </svg>
        </div>
        <div>
          <h2 id="recommendation-chat-title">
            Trợ Lý Comics
            <span class="recommendation-chat-online-dot"></span>
          </h2>
          <span class="recommendation-chat-subtitle">Gợi ý truyện theo cảm xúc &amp; gu đọc</span>
        </div>
      </div>
      <button id="recommendation-chat-close" type="button" aria-label="Đóng trợ lý tìm truyện">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </header>

    <!-- AI Messages Log Area -->
    <div id="recommendation-chat-messages" class="recommendation-chat-body" role="log" aria-label="Tin nhắn" aria-live="polite" aria-relevant="additions">
      <div class="recommendation-chat-bot-wrapper">
        <div class="recommendation-chat-bot-icon">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect width="18" height="12" x="3" y="6" rx="3"></rect>
            <circle cx="9" cy="12" r="1.5" fill="currentColor"></circle>
            <circle cx="15" cy="12" r="1.5" fill="currentColor"></circle>
            <path d="M12 2v4"></path>
          </svg>
        </div>
        <div class="recommendation-chat-bot-inner">
          <div class="recommendation-chat-bubble recommendation-chat-bot">
            <p style="margin: 0 0 8px;">Xin chào! Mình là trợ lý ảo Comics. Bạn đang muốn tìm kiếm truyện tranh thuộc thể loại nào hôm nay?</p>
            <div class="recommendation-chat-quick-tags">
              <button type="button" class="recommendation-quick-chip" onclick="window.sendChatQuickPrompt('Tìm truyện Manhwa hệ thống bá đạo')">⚔️ Manhwa Bá Đạo</button>
              <button type="button" class="recommendation-quick-chip" onclick="window.sendChatQuickPrompt('Gợi ý truyện Tu Tiên hài hước võ mõm')">🧘 Tu Tiên Hài Hước</button>
              <button type="button" class="recommendation-quick-chip" onclick="window.sendChatQuickPrompt('Truyện tình cảm lãng mạn nhẹ nhàng')">💖 Ngôn Tình Ngọt</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <p id="recommendation-chat-loading" role="status" hidden>Đang tìm truyện…</p>
    <p id="recommendation-chat-error" role="alert" hidden></p>
    <div id="recommendation-chat-chips" aria-label="Gợi ý nhanh"></div>

    <!-- AI Input Box -->
    <form id="recommendation-chat-form">
      <label class="recommendation-chat-input-label sr-only" for="recommendation-chat-input">Mô tả gu truyện bạn muốn tìm</label>
      <div class="recommendation-chat-compose">
        <input type="text" id="recommendation-chat-input" maxlength="4000" placeholder="Mô tả gu truyện bạn muốn tìm..." autocomplete="off">
        <button id="recommendation-chat-send" type="submit" aria-label="Gửi tin nhắn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m22 2-7 20-4-9-9-4Z"></path>
            <path d="M22 2 11 13"></path>
          </svg>
        </button>
      </div>
    </form>

  </section>
</aside>
<script>
  window.sendChatQuickPrompt = function(text) {
    const inp = document.getElementById('recommendation-chat-input');
    const send = document.getElementById('recommendation-chat-send');
    if (inp && send) {
      inp.value = text;
      send.click();
    }
  };
</script>
<script src="{{ asset('js/recommendation-chat-widget.js') }}" defer></script>
