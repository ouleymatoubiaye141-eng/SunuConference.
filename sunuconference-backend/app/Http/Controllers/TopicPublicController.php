<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use App\Services\TopicService;
use Illuminate\Http\JsonResponse;

class TopicPublicController extends Controller
{
    public function __construct(private readonly TopicService $topicService) {}

    public function index(Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Topics récupérés avec succès.',
            'data' => $this->topicService->listPublicForConference($conference),
        ]);
    }
}
