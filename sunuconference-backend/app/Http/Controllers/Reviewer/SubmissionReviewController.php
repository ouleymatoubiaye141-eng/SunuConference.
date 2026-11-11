<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reviewer\DeclareConflictRequest;
use App\Http\Requests\Reviewer\SaveReviewDraftRequest;
use App\Http\Requests\Reviewer\SubmitReviewRequest;
use App\Services\ReviewConflictService;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly ReviewConflictService $reviewConflictService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Soumissions assignées récupérées avec succès.',
            'data' => $this->reviewService->listAssignedSubmissionsForReviewer($request->user()),
        ]);
    }

    public function saveDraft(SaveReviewDraftRequest $request, int $id): JsonResponse
    {
        $assignment = $this->reviewService->findForReviewer($id, $request->user());

        $this->reviewService->saveDraft($assignment, $request->validated());

        return response()->json([
            'message' => 'Brouillon du rapport sauvegardé avec succès.',
        ]);
    }

    public function submit(SubmitReviewRequest $request, int $id): JsonResponse
    {
        $assignment = $this->reviewService->findForReviewer($id, $request->user());

        $this->reviewService->submit($assignment, $request->validated());

        return response()->json([
            'message' => 'Rapport de relecture soumis avec succès.',
        ]);
    }

    public function declareConflict(DeclareConflictRequest $request, int $id): JsonResponse
    {
        $assignment = $this->reviewService->findForReviewer($id, $request->user());

        $this->reviewConflictService->declareConflict($assignment, $request->validated('reason'));

        return response()->json([
            'message' => 'Conflit d’intérêt déclaré avec succès.',
        ]);
    }
}
