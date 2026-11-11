<?php

namespace App\Http\Controllers\Chair;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chair\CreateReviewerRequest;
use App\Http\Requests\Chair\ListReviewersRequest;
use App\Http\Requests\Chair\RemoveReviewerRequest;
use App\Http\Requests\Chair\ResendReviewerCredentialsRequest;
use App\Models\Conference;
use App\Models\User;
use App\Services\ReviewerService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReviewerController extends Controller
{
    public function __construct(private readonly ReviewerService $reviewerService) {}

    public function store(CreateReviewerRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Reviewer créé avec succès.',
            'data' => $this->reviewerService->create($conference, $request->validated()),
        ], Response::HTTP_CREATED);
    }

    public function index(ListReviewersRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Reviewers récupérés avec succès.',
            'data' => $this->reviewerService->listForConference($conference),
        ]);
    }

    public function resendCredentials(
        ResendReviewerCredentialsRequest $request,
        Conference $conference,
        User $reviewer,
    ): JsonResponse {
        $this->reviewerService->resendCredentials($reviewer);

        return response()->json([
            'message' => 'Nouveaux identifiants générés avec succès.',
        ]);
    }

    public function destroy(RemoveReviewerRequest $request, Conference $conference, User $reviewer): JsonResponse
    {
        $this->reviewerService->removeFromConference($conference, $reviewer);

        return response()->json([
            'message' => 'Reviewer retiré de la conférence avec succès.',
        ]);
    }
}
