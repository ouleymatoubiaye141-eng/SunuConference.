<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Services\ReviewerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function __construct(private readonly ReviewerService $reviewerService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => "Topics assign\u{00E9}s r\u{00E9}cup\u{00E9}r\u{00E9}s avec succ\u{00E8}s.",
            'data' => $this->reviewerService->listAssignedTopicsForReviewer($request->user()),
        ]);
    }
}
