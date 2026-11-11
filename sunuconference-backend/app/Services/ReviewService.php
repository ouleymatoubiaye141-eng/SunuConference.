<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Review;
use App\Models\ReviewerSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;

class ReviewService
{
    public function listAssignedSubmissionsForReviewer(User $reviewer): Collection
    {
        return ReviewerSubmission::query()
            ->with(['submission.conference', 'submission.topic', 'review'])
            ->where('reviewer_id', $reviewer->id)
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();
    }

    public function findForReviewer(int $id, User $reviewer): ReviewerSubmission
    {
        return ReviewerSubmission::where('id', $id)
            ->where('reviewer_id', $reviewer->id)
            ->firstOrFail();
    }

    public function saveDraft(ReviewerSubmission $reviewerSubmission, array $data): Review
    {
        $this->ensureReviewIsMutable($reviewerSubmission);

        return Review::updateOrCreate(
            ['reviewer_submission_id' => $reviewerSubmission->id],
            array_merge($data, ['submitted_at' => null])
        );
    }

    public function submit(ReviewerSubmission $reviewerSubmission, array $data): Review
    {
        $this->ensureReviewIsMutable($reviewerSubmission);

        return Review::updateOrCreate(
            ['reviewer_submission_id' => $reviewerSubmission->id],
            array_merge($data, ['submitted_at' => now()])
        );
    }

    private function ensureReviewIsMutable(ReviewerSubmission $reviewerSubmission): void
    {
        $status = $reviewerSubmission->review?->status;

        if (in_array($status, ['submitted', 'conflict_declared'], true)) {
            $message = $status === 'submitted'
                ? 'Impossible de modifier un rapport déjà soumis.'
                : 'Impossible de modifier un rapport après avoir déclaré un conflit d’intérêt.';

            throw new ApiException($message, Response::HTTP_CONFLICT);
        }
    }
}
