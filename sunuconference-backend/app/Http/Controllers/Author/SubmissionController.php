<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Http\Requests\Author\StoreSubmissionRequest;
use App\Http\Requests\Author\SubmitCameraReadyRequest;
use App\Http\Requests\Author\SubmitRebuttalRequest;
use App\Http\Requests\Author\SubmitRevisionRequest;
use App\Http\Resources\PublicReviewResource;
use App\Models\Conference;
use App\Models\Topic;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => "Soumissions r\u{00E9}cup\u{00E9}r\u{00E9}es avec succ\u{00E8}s.",
            'data' => $this->submissionService->listForAuthor($request->user()),
        ]);
    }

    public function show(Request $request, int $submission): JsonResponse
    {
        $submissionData = $this->submissionService->findForAuthor($request->user(), $submission);
        $reviews = PublicReviewResource::collection($submissionData->publicReviews);

        $submissionData->unsetRelation('publicReviews');

        return response()->json([
            'message' => 'Soumission récupérée avec succès.',
            'data' => array_merge($submissionData->toArray(), [
                'reviews' => $reviews,
            ]),
        ]);
    }

    public function store(StoreSubmissionRequest $request, Conference $conference, Topic $topic): JsonResponse
    {
        return response()->json([
            'message' => 'Article soumis avec succès.',
            'data' => $this->submissionService->create(
                $request->user(),
                $conference,
                $topic,
                $request->validated(),
                $request->file('file'),
            ),
        ], Response::HTTP_CREATED);
    }

    public function submitRevision(SubmitRevisionRequest $request, int $submission): JsonResponse
    {
        return response()->json([
            'message' => 'Révision soumise avec succès.',
            'data' => $this->submissionService->submitRevision(
                $request->user(),
                $submission,
                $request->validated(),
                $request->file('file'),
            ),
        ]);
    }

    public function submitCameraReady(SubmitCameraReadyRequest $request, int $submission): JsonResponse
    {
        return response()->json([
            'message' => 'Version camera-ready soumise avec succès.',
            'data' => $this->submissionService->submitCameraReady(
                $request->user(),
                $submission,
                $request->file('file'),
            ),
        ]);
    }

    public function submitRebuttal(SubmitRebuttalRequest $request, int $submission): JsonResponse
    {
        return response()->json([
            'message' => 'Rebuttal pré-décision soumis avec succès.',
            'data' => $this->submissionService->submitRebuttal(
                $request->user(),
                $submission,
                $request->validated('content'),
            ),
        ], Response::HTTP_CREATED);
    }
}
