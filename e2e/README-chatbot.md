# Kiểm thử chatbot

Yêu cầu: PHP có SQLite, Composer dependencies của Laravel và Playwright/Chromium hiện có.

Từ thư mục `e2e`:

```sh
CI=1 npm run test:chatbot
```

Config kế thừa Playwright của repo, chạy desktop/mobile và tự khởi động PHP ở cổng 8765.
Helper tạo SQLite mới trong thư mục tạm `comicx-chatbot-e2e-*`, chạy migration và seed đúng hai truyện giả.
`AI_MAX_CALLS=0` dùng rule-based fallback, không gọi provider; browser chặn request ngoài origin.
Không cần `.env`, dữ liệu truyện thật hoặc server chạy sẵn. Helper tắt PHP sau test; SQLite tạm được giữ để chẩn đoán.

Các luồng: yêu cầu tự nhiên → loading → loại Harem → mở chi tiết; câu hỏi → quick reply → reload → giữ preference/token; đóng/mở/gửi ở màn hình thấp với header thật.

CI chạy config này riêng; `npm test` mặc định không thu thập chatbot spec vì bộ đó dùng DB khác.
Kết quả chatbot nằm trong `test-results/chatbot` và `playwright-report/chatbot`.

## Hội thoại và quota

`RecommendationChatService` giữ flow parse → merge state → scoring/query hiện có,
sau đó gọi `RecommendationResponseService` để tạo message, cards thật, follow-up và chips theo state.
Chỉ chọn genre sẽ hỏi thêm về nhân vật. Kết quả rỗng đề nghị nới tiêu chí;
`Bỏ Main OP` và `Cho phép Harem` chỉ sửa state khi người dùng bấm.

`AI_CONVERSATIONAL_RESPONSE=true` cho phép thêm một lần gọi response khi câu tự nhiên có ít nhất
5 từ, parser AI thành công không lấy cache, có candidates và còn quota.
Parser và response cùng dùng `AI_MAX_CALLS`/`AI_DECAY_SECONDS` qua `AIQuota`;
scope guest vẫn kiểm tra conversation, session và IP. Quota đếm lần gọi client;
HTTP retry bên trong client giữ giới hạn cấu hình hiện có.

Để kiểm chứng grounding một cách xác định, AI chọn nguyên văn trong các câu tiếng Việt
đã được backend tạo từ tiêu chí và số kết quả thật. Đây là lựa chọn cách diễn đạt,
không phải sinh văn tự do: mọi tên truyện/claim/HTML hoặc field ngoài hợp đồng đều bị loại
và dùng template. Cards, ID, URL, cover, score và matched reasons luôn lấy từ backend.
Không gửi secret hoặc system prompt đến frontend; fallback log chỉ có mã lý do.

Quick reply: trước/sau đều **0** AI calls. Câu tự nhiên chưa cache: trước tối đa **1** lần gọi
parser; sau tối đa **2** lần gọi (parser + response), trong cùng ngân sách.
Câu đơn giản, parser fallback, AI disabled, hết quota, không có kết quả hoặc provider lỗi
dùng template cho response. Cache parser bỏ qua lần gọi response tiếp theo.

Widget render text → cards (một reason ngắn) → follow-up → chips bằng `textContent`.
History lưu JSON role/content/type trong `sessionStorage`, tối đa 60 entries mỗi tab/tài khoản;
guest phải khớp token. Reload khôi phục text/cards/chips, đóng tab sẽ mất history.
Database vẫn chỉ lưu preference, không thêm raw chat. History không đồng bộ giữa thiết bị.

Feature tests fake AI kiểm tra grounding, timeout, HTML, quota chung, exclude và thao tác nới tiêu chí.
8 E2E cases trên desktop/mobile kiểm tra thứ tự hội thoại, ảnh/link, history không nhân đôi,
quick reply cập nhật state, viewport thấp và XSS cả trước/sau reload. Provider luôn tắt trong E2E.
