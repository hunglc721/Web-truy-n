<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    /** Chưa cấu hình key (ví dụ local) thì tự tắt, không chặn người dùng. */
    public static function rules(): array
    {
        return config('services.turnstile.secret') ? ['required', new self] : [];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $ok = Http::asForm()->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret'   => config('services.turnstile.secret'),
                    'response' => (string) $value,
                    'remoteip' => request()->ip(),
                ])->json('success') === true;
        } catch (\Throwable) {
            $ok = false;
        }

        if (! $ok) {
            $fail('Xác minh chống robot thất bại. Vui lòng thử lại.');
        }
    }
}
