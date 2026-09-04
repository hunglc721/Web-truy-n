<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        Log::info('Public contact form submission', [
            ...$validated,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        return back()->with('success', 'Tin nhắn đã được ghi nhận. Ban quản trị sẽ kiểm tra và phản hồi khi có thể.');
    }
}
