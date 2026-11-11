<?php

namespace App\Http\Controllers\Chair;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chair\IndexChairSubmissionRequest;
use App\Http\Requests\Chair\ShowChairSubmissionRequest;
use App\Models\Conference;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;

class SubmissionController extends Controller
{
    public function __construct(private readonly SubmissionService $submissionService) {}

    public function index(IndexChairSubmissionRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => 'Soumissions récupérées avec succès.',
            'data' => $this->submissionService->listForConference($conference),
        ]);
    }

    public function show(ShowChairSubmissionRequest $request, Conference $conference, Submission $submission): JsonResponse
    {
        return response()->json([
            'message' => 'Soumission récupérée avec succès.',
            'data' => $this->submissionService->findForChair($conference, $submission->id),
        ]);
    }
}
