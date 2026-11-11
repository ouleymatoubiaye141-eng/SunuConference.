<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectConferenceRequest;
use App\Models\Conference;
use App\Services\ConferenceService;
use Illuminate\Http\JsonResponse;

class ConferenceValidationController extends Controller
{
    public function __construct(private readonly ConferenceService $conferenceService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Conference::query()->with('chair:id,name,email,affiliation')->latest()->get(),
        ]);
    }

    public function validateConference(Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Conference validee avec succes.',
            'data' => $this->conferenceService->validate($conference),
        ]);
    }

    public function reject(RejectConferenceRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Conference rejetee.',
            'data' => $this->conferenceService->reject($conference, $request->validated('rejection_reason')),
        ]);
    }
}
