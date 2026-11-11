<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Review;
use App\Models\ReviewerSubmission;
use Symfony\Component\HttpFoundation\Response;

class ReviewConflictService
{
    public function declareConflict(ReviewerSubmission $reviewerSubmission, string $reason): Review
    {
        $review = $reviewerSubmission->review;

        if ($review?->status === 'submitted') {
            throw new ApiException(
                'Impossible de déclarer un conflit pour un rapport déjà soumis.',
                Response::HTTP_CONFLICT
            );
        }

        return Review::updateOrCreate(
            ['reviewer_submission_id' => $reviewerSubmission->id],
            [
                'conflict_of_interest' => true,
                'conflict_reason' => $reason,
                'submitted_at' => null,
            ]
        );
    }
}
