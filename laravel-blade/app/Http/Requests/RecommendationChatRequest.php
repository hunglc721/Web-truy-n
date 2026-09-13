<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecommendationChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->headers->set('Accept', 'application/json');
    }

    public function rules(): array
    {
        return [
            'message' => ['required_without:quick_reply', 'prohibits:quick_reply', 'nullable', 'string', 'max:4000'],
            'quick_reply' => ['required_without:message', 'prohibits:message', 'nullable', 'string', 'max:100'],
            'conversation_token' => ['nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
        ];
    }
}
