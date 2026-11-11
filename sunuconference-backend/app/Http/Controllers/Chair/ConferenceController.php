<?php

namespace App\Http\Controllers\Chair;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chair\PublishProgramRequest;
use App\Http\Requests\Chair\StoreConferenceRequest;
use App\Http\Requests\Chair\UpdateConferenceRequest;
use App\Models\Conference;
use App\Services\ConferenceService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConferenceController extends Controller
{
    public function __construct(
        private readonly ConferenceService $conferenceService,
        private readonly SessionService $sessionService,
    ) {}

    public function store(StoreConferenceRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Conference creee. Elle est en attente de validation.',
            'data' => $this->conferenceService->createForChair($request->user(), $request->validated()),
        ], Response::HTTP_CREATED);
    }

    public function mine(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Conférence récupérée avec succès.',
            'data' => $request->user()->chairedConferences()->latest()->first(),
        ]);
    }

    public function update(UpdateConferenceRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Conférence mise à jour avec succès.',
            'data' => $this->conferenceService->updateForChair($request->user(), $conference, $request->validated()),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Acces PC Chair autorise.',
            'conference' => $request->user()->chairedConferences()->latest()->first(),
        ]);
    }

    public function publish(PublishProgramRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Programme publié avec succès.',
            'data' => $this->sessionService->publish($conference),
        ]);
    }

    public function unpublish(PublishProgramRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Programme dépublié avec succès.',
            'data' => $this->sessionService->unpublish($conference),
        ]);
    }
}
