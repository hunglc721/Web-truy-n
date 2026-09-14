<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecommendationChatRequest;
use App\Services\RecommendationChatService;
use Illuminate\Http\JsonResponse;

class RecommendationChatController extends Controller
{
    public function __invoke(RecommendationChatRequest $request, RecommendationChatService $chat): JsonResponse
    {
        // These scopes are derived on the server, never accepted from the payload.
        $guestQuotaScopes = $request->user() ? [] : [
            'session:'.$request->session()->getId(), 'ip:'.$request->ip(),
        ];

        return response()->json($chat->reply($request->validated(), $request->user(), $guestQuotaScopes));
    }
}
