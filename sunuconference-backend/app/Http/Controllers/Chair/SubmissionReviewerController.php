<?php

namespace App\Http\Controllers\Chair;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chair\AssignReviewerToSubmissionRequest;
use App\Http\Requests\Chair\ListSubmissionReviewersRequest;
use App\Http\Requests\Chair\RecordDecisionRequest;
use App\Http\Requests\Chair\RemoveReviewerFromSubmissionRequest;
use App\Http\Requests\Chair\ViewReviewSummaryRequest;
use App\Models\Conference;
use App\Models\Submission;
use App\Models\User;
use App\Services\ChairDecisionService;
use App\Services\ReviewAssignmentService;
use App\Services\ReviewSummaryService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SubmissionReviewerController extends Controller
{
    public function __construct(
        private readonly ReviewAssignmentService $reviewAssignmentService,
        private readonly ReviewSummaryService $reviewSummaryService,
        private readonly ChairDecisionService $chairDecisionService,
    ) {}

    public function index(
        ListSubmissionReviewersRequest $request,
        Conference $conference,
        Submission $submission,
    ): JsonResponse {
        return response()->json([
            'message' => 'Assignations de reviewers récupérées avec succès.',
            'data' => $this->reviewAssignmentService->listAssignmentsForSubmission($submission),
        ]);
    }

    public function store(
        AssignReviewerToSubmissionRequest $request,
        Conference $conference,
        Submission $submission,
    ): JsonResponse {
        $reviewer = User::findOrFail($request->validated('reviewer_id'));

        $this->reviewAssignmentService->assignReviewerToSubmission(
            $conference,
            $submission,
            $reviewer,
            $request->user(),
        );

        return response()->json([
            'message' => 'Reviewer assigné à la soumission avec succès.',
        ]);
    }

    public function destroy(
        RemoveReviewerFromSubmissionRequest $request,
        Conference $conference,
        Submission $submission,
    ): JsonResponse {
        $reviewer = User::findOrFail($request->validated('reviewer_id'));

        $this->reviewAssignmentService->removeReviewerFromSubmission($submission, $reviewer);

        return response()->json([
            'message' => 'Reviewer retiré de la soumission avec succès.',
        ]);
    }

    public function reviewsSummary(
        ViewReviewSummaryRequest $request,
        Conference $conference,
        Submission $submission,
    ): JsonResponse {
        return response()->json([
            'message' => 'Synthèse des évaluations récupérée avec succès.',
            'data' => $this->reviewSummaryService->buildSummary($submission),
        ]);
    }

    public function recordDecision(
        RecordDecisionRequest $request,
        Conference $conference,
        Submission $submission,
    ): JsonResponse {
        $result = $this->chairDecisionService->recordDecision(
            $submission,
            $request->user(),
            $request->validated('decision'),
            $request->validated('comments'),
        );

        $response = [
            'message' => 'Décision enregistrée avec succès.',
            'data' => $result['decision'],
        ];

        if (!empty($result['warnings'])) {
            $response['warnings'] = $result['warnings'];
        }

        return response()->json($response, Response::HTTP_CREATED);
    }
}
