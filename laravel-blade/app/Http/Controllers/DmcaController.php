<?php

namespace App\Http\Controllers;

use App\Models\DmcaReport;
use Illuminate\Http\Request;

class DmcaController extends Controller
{
    public function show()
    {
        return view('pages.dmca');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'            => 'required|string|max:120',
            'email'                => 'required|email|max:150',
            'company_name'         => 'nullable|string|max:150',
            'work_title'           => 'required|string|max:200',
            'infringing_url'       => 'required|string|max:500',
            'original_work_proof'  => 'required|string|max:1000',
            'details'              => 'nullable|string|max:2000',
            'good_faith_statement' => 'accepted',
        ], [
            'full_name.required'            => 'Vui lòng nhập họ và tên của bạn.',
            'email.required'                => 'Vui lòng cung cấp email liên hệ hợp lệ.',
            'work_title.required'           => 'Vui lòng nhập tên tác phẩm gốc bị vi phạm.',
            'infringing_url.required'       => 'Vui lòng cung cấp đường dẫn (URL) nội dung vi phạm trên trang web.',
            'original_work_proof.required'  => 'Vui lòng cung cấp bằng chứng hoặc liên kết chứng minh quyền sở hữu hợp pháp.',
            'good_faith_statement.accepted' => 'Bạn phải xác nhận cam kết thông tin cung cấp là trung thực và chính xác.',
        ]);

        DmcaReport::create([
            'full_name'            => $validated['full_name'],
            'email'                => $validated['email'],
            'company_name'         => $validated['company_name'] ?? null,
            'work_title'           => $validated['work_title'],
            'infringing_url'       => $validated['infringing_url'],
            'original_work_proof'  => $validated['original_work_proof'],
            'details'              => $validated['details'] ?? null,
            'good_faith_statement' => true,
            'status'               => 'pending',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Yêu cầu khiếu nại bản quyền của bạn đã được tiếp nhận. Ban quản trị sẽ tiến hành thẩm định và xử lý trong vòng 24–48 giờ làm việc.',
            ]);
        }

        return back()->with('success', 'Yêu cầu khiếu nại bản quyền của bạn đã được gửi thành công! Ban quản trị sẽ phản hồi qua email trong thời gian sớm nhất.');
    }
}
