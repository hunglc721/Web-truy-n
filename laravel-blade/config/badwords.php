<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Danh sách Từ cấm & Từ nhạy cảm mở rộng (150+ Bad Words & Teencode)
    |--------------------------------------------------------------------------
    | Bao gồm tục tĩu, 18+, cờ bạc, lừa đảo, teencode (d1t, l0n, c4c), 
    | từ tiếng Anh và các cách viết lách luật phổ biến tại Việt Nam.
    */

    'words' => [
        // ── 1. TỤC TĨU TIẾNG VIỆT CÓ DẤU, KHÔNG DẤU & TEENCODE ───────────────
        'địt', 'dit', 'd1t', 'd17', 'đị7', 'đị-t', 'đ.ị.t', 'd.i.t', 'địt mẹ', 'dit me', 'ditme', 'địtmẹ',
        'địt má', 'dit ma', 'địt cụ', 'dit cu', 'địt con mẹ', 'dit con me', 'đ.m', 'dm', 'dkm', 'đkm',
        'đmm', 'dmm', 'vkl', 'vcl', 'vl', 'clm', 'clgt', 'cc', 'cặc', 'cac', 'c4c', 'cặk', 'cak', 'đầu cặc',
        'dau cac', 'dái', 'dai', 'đái', 'lồn', 'lon', 'l0n', 'l.ồ.n', 'l.o.n', 'lồnn', 'lonn', 'xàm lồn',
        'xam lon', 'mặt lồn', 'mat lon', 'mẹ kiếp', 'me kiep', 'chó đẻ', 'cho de', 'bố láo', 'bo lao',
        'óc chó', 'oc cho', 'đồ ngu', 'do ngu', 'ngu lồn', 'ngu lon', 'súc vật', 'suc vat', 'đụ', 'du',
        'd7u', 'đụ má', 'du ma', 'duma', 'đụ mẹ', 'du me', 'dume', 'chịch', 'chich', 'ch1ch', 'chỉch',
        'xoạc', 'xoac', 'xoạkk', 'liếm lồn', 'liem lon', 'bú cặc', 'bu cac', 'bú lồn', 'bu lon',

        // ── 2. TỪ 18+, KHIÊU DÂM, NHẠY CẢM ──────────────────────────────────
        'sex', 's.e.x', 's3x', 's-e-x', 'hentai', 'h.e.n.t.a.i', 'xem sex', 'xem s3x', 'phim 18+',
        'gái gọi', 'gai goi', 'kích dục', 'kich duc', 'thủ dâm', 'thu dam', 'bắn tinh', 'ban tinh',
        'dâm dục', 'dam duc', 'xuất tinh', 'xuat tinh', 'hiếp dâm', 'hiep dam', 'loạn luân', 'loan luan',
        'ấu dâm', 'au dam', 'khoe hàng', 'khoe hang', 'lột đồ', 'lot do', 'nude', 'ảnh nude', 'anh nude',
        'clip nóng', 'clip nong', 'show hàng', 'show hang', 'phim người lớn', 'phim nguoi lon',

        // ── 3. CỜ BẠC, CÁ ĐỘ, LỪA ĐẢO, GAME BÀI ────────────────────────────
        'tài xỉu', 'tai xiu', 't.à.i x.ỉ.u', 'cá độ', 'ca do', 'c.á đ.ộ', 'bóng đá 88', 'bong da 88',
        'kubet', 'k.u.b.e.t', 'shbet', 's.h.b.e.t', '789bet', 'jun88', 'hi88', 'okvip', 'thabet',
        'sunwin', 's.u.n.w.i.n', 'hitclub', 'go88', 'g.o.8.8', 'b52', 'b52club', 'xóc đĩa', 'xoc dia',
        'kèo bóng', 'keo bong', 'nạp coin miễn phí', 'nap coin mien phi', 'nhận coin free', 'nhan coin free',
        'hack coin', 'rút tiền tự động', 'rut tien tu dong', 'kiếm tiền online', 'kiem tien online',
        'làm việc tại nhà', 'lam viec tai nha', 'đầu tư tài chính', 'dau tu tai chinh', 'nhận giftcode',
        'nhan giftcode', 'tặng code', 'tang code', 'nổ hũ', 'no hu', 'bắn cá đổi thưởng', 'ban ca doi thuong',

        // ── 4. TỪ TIẾNG ANH TỤC TĨU (ENGLISH PROFANITY) ─────────────────────
        'fuck', 'f.u.c.k', 'f*ck', 'fck', 'fuk', 'fukking', 'fucking', 'fucker', 'shit', 's.h.i.t',
        'sh1t', 'bitch', 'b.i.t.c.h', 'b1tch', 'asshole', 'a.s.s.h.o.l.e', 'bastard', 'cunt', 'c.u.n.t',
        'dick', 'd.i.c.k', 'd1ck', 'pussy', 'p.u.s.s.y', 'nigger', 'nigga', 'motherfucker', 'slut',
        'whore', 'w.h.o.r.e', 'cock', 'c.o.c.k', 'blowjob', 'handjob', 'porn', 'porno', 'pornography'
    ],

    /*
    |--------------------------------------------------------------------------
    | Regex nhận diện đường link Spam, Tên miền & Số điện thoại
    |--------------------------------------------------------------------------
    */
    'spam_patterns' => [
        '/https?:\/\/[^\s]+/i',                                      // URL http / https
        '/[a-zA-Z0-9-]+\.(com|net|org|xyz|info|top|club|vip|site|online|tech|download|store|link|space|fun|today|work|monster)/i', // Tên miền quốc tế & rác
        '/(zalo|z\.alo|telegram|tele|t\.me)\s*:?\s*[a-zA-Z0-9._\-]+/i', // Liên hệ Zalo / Telegram
        '/(0[3|5|7|8|9]\d{8,9}|\+84[3|5|7|8|9]\d{8,9})/i',             // Số điện thoại Việt Nam
        '/(quảng cáo|liên hệ ngay|kết bạn zalo|nhắn zalo|box zalo)/i' // Từ khóa chèo kéo Zalo/Spam
    ],
];
