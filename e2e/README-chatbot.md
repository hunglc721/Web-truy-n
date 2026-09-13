# Kiểm thử chatbot

Yêu cầu: PHP có SQLite, Composer dependencies của Laravel và Playwright/Chromium hiện có.

Từ thư mục `e2e`:

```sh
npx playwright test recommendation-chatbot.spec.js --config=playwright.chatbot.config.js
```

Config kế thừa Playwright của repo, chỉ chạy desktop và tự khởi động PHP ở cổng 8765.
Helper tạo SQLite mới trong thư mục tạm `comicx-chatbot-e2e-*`, chạy migration và seed đúng hai truyện giả.
`AI_MAX_CALLS=0` dùng rule-based fallback, không gọi provider; browser chặn request ngoài origin.
Không cần `.env`, dữ liệu truyện thật hoặc server chạy sẵn. Helper tắt PHP sau test; SQLite tạm được giữ để chẩn đoán.

Hai luồng: yêu cầu tự nhiên → loading → loại Harem → mở chi tiết; câu hỏi → quick reply → reload → giữ preference/token.
