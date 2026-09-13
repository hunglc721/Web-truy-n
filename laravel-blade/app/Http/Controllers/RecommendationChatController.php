<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecommendationChatRequest;
use App\Services\RecommendationChatService;
use Illuminate\Http\JsonResponse;

class RecommendationChatController extends Controller
{
    public function __invoke(RecommendationChatRequest $request, RecommendationChatService $chat): JsonResponse
    {
        return response()->json($chat->reply($request->validated(), $request->user()));
    }
}
