<?php

namespace App\Http\Controllers\Chair;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chair\AssignReviewerToTopicRequest;
use App\Http\Requests\Chair\DestroyTopicRequest;
use App\Http\Requests\Chair\IndexTopicRequest;
use App\Http\Requests\Chair\ListTopicReviewersRequest;
use App\Http\Requests\Chair\StoreTopicRequest;
use App\Http\Requests\Chair\UnassignReviewerFromTopicRequest;
use App\Http\Requests\Chair\UpdateTopicRequest;
use App\Models\Conference;
use App\Models\Topic;
use App\Models\User;
use App\Services\ReviewerService;
use App\Services\TopicService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService,
        private readonly ReviewerService $reviewerService,
    ) {}

    public function index(IndexTopicRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => "Topics r\u{00E9}cup\u{00E9}r\u{00E9}s avec succ\u{00E8}s.",
            'data' => $this->topicService->listForConference($conference),
        ]);
    }

    public function store(StoreTopicRequest $request, Conference $conference): JsonResponse
    {
        return response()->json([
            'message' => "Topic cr\u{00E9}\u{00E9} avec succ\u{00E8}s.",
            'data' => $this->topicService->create($conference, $request->validated()),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateTopicRequest $request, Conference $conference, Topic $topic): JsonResponse
    {
        return response()->json([
            'message' => "Topic modifi\u{00E9} avec succ\u{00E8}s.",
            'data' => $this->topicService->update($topic, $request->validated()),
        ]);
    }

    public function destroy(DestroyTopicRequest $request, Conference $conference, Topic $topic): JsonResponse
    {
        $this->topicService->delete($topic);

        return response()->json([
            'message' => "Topic supprim\u{00E9} avec succ\u{00E8}s.",
        ]);
    }

    public function assignedReviewers(ListTopicReviewersRequest $request, Conference $conference, Topic $topic): JsonResponse
    {
        return response()->json([
            'message' => "Reviewers assign\u{00E9}s r\u{00E9}cup\u{00E9}r\u{00E9}s avec succ\u{00E8}s.",
            'data' => $this->reviewerService->listAssignedReviewers($topic),
        ]);
    }

    public function assignReviewer(AssignReviewerToTopicRequest $request, Conference $conference, Topic $topic): JsonResponse
    {
        $reviewer = User::findOrFail($request->validated('reviewer_id'));

        $this->reviewerService->assignToTopic($topic, $reviewer);

        return response()->json([
            'message' => "Reviewer assign\u{00E9} au topic avec succ\u{00E8}s.",
        ]);
    }

    public function unassignReviewer(UnassignReviewerFromTopicRequest $request, Conference $conference, Topic $topic): JsonResponse
    {
        $reviewer = User::findOrFail($request->validated('reviewer_id'));

        $this->reviewerService->unassignFromTopic($topic, $reviewer);

        return response()->json([
            'message' => "Reviewer retir\u{00E9} du topic avec succ\u{00E8}s.",
        ]);
    }
}
