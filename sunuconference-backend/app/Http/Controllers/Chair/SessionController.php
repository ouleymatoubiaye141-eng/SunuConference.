<?php

namespace App\Http\Controllers\Chair;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chair\AssignSubmissionToSessionRequest;
use App\Http\Requests\Chair\DestroySessionRequest;
use App\Http\Requests\Chair\IndexSessionRequest;
use App\Http\Requests\Chair\RemoveSubmissionFromSessionRequest;
use App\Http\Requests\Chair\StoreSessionRequest;
use App\Http\Requests\Chair\UpdateSessionRequest;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Submission;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SessionController extends Controller
{
    public function __construct(private readonly SessionService $sessionService) {}

    public function index(IndexSessionRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Sessions récupérées avec succès.',
            'data' => $this->sessionService->listForConference($conference),
        ]);
    }

    public function store(StoreSessionRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Session créée avec succès.',
            'data' => $this->sessionService->create($conference, $request->validated()),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateSessionRequest $request, Conference $conference, ConferenceSession $session): JsonResponse
    {
        return response()->json([
            'message' => 'Session mise à jour avec succès.',
            'data' => $this->sessionService->update($session, $request->validated()),
        ]);
    }

    public function destroy(DestroySessionRequest $request, Conference $conference, ConferenceSession $session): JsonResponse
    {
        $this->sessionService->delete($session);

        return response()->json([
            'message' => 'Session supprimée avec succès.',
        ]);
    }

    public function assignSubmission(
        AssignSubmissionToSessionRequest $request,
        Conference $conference,
        ConferenceSession $session,
    ): JsonResponse {
        $submission = Submission::findOrFail($request->validated('submission_id'));

        return response()->json([
            'message' => 'Article programmé avec succès.',
            'data' => $this->sessionService->assignSubmission($conference, $session, $submission),
        ]);
    }

    public function removeSubmission(
        RemoveSubmissionFromSessionRequest $request,
        Conference $conference,
        ConferenceSession $session,
        Submission $submission,
    ): JsonResponse {
        $this->sessionService->removeSubmission($session, $submission);

        return response()->json([
            'message' => 'Article retiré de la session avec succès.',
        ]);
    }
}
