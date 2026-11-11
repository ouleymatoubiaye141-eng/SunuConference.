<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use App\Services\ConferenceService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;

class ConferencePublicController extends Controller
{
    public function __construct(
        private readonly ConferenceService $conferenceService,
        private readonly SessionService $sessionService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Conférences récupérées avec succès.',
            'data' => $this->conferenceService->listPublicValidated(),
        ]);
    }

    public function show(Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Conférence récupérée avec succès.',
            'data' => $this->conferenceService->showPublic($conference),
        ]);
    }

    public function programme(Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Programme récupéré avec succès.',
            'data' => $this->sessionService->listPublishedForConference($conference),
        ]);
    }
}
