<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Comment Business Rules
    |--------------------------------------------------------------------------
    |
    | Tập trung cấu hình bình luận tại đây để Policy, Validation,
    | và Service đều đọc từ một nguồn duy nhất — không hardcode.
    |
    */

    // Số phút tối đa được phép chỉnh sửa bình luận sau khi đăng (user thường)
    'edit_window_minutes' => (int) env('COMMENT_EDIT_WINDOW_MINUTES', 15),

    // Độ sâu tối đa của nested reply (1 = chỉ reply trực tiếp vào bình luận gốc)
    'max_depth' => 1,

    // Số ký tự tối đa cho nội dung bình luận
    'max_length' => 1000,

    // Số ký tự tối thiểu sau khi trim()
    'min_length' => 1,
];
