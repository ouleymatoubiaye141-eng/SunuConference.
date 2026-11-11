<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Conference;
use App\Models\ReviewerSubmission;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\ReviewerAssignedToSubmissionNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ReviewAssignmentService
{
    public function assignReviewerToSubmission(
        Conference $conference,
        Submission $submission,
        User $reviewer,
        User $chair,
    ): ReviewerSubmission {
        $this->ensureReviewerRole($reviewer);
        $this->ensureReviewerIsCompetentForSubmissionTopic($submission, $reviewer);
        $this->ensureSubmissionCanReceiveReviewer($submission);

        $result = DB::transaction(function () use ($submission, $reviewer, $chair): array {
            $reviewerSubmission = ReviewerSubmission::firstOrCreate(
                [
                    'submission_id' => $submission->id,
                    'reviewer_id' => $reviewer->id,
                ],
                [
                    'assigned_by' => $chair->id,
                    'assigned_at' => now(),
                ],
            );

            $wasRecentlyCreated = $reviewerSubmission->wasRecentlyCreated;

            if ($submission->status === Submission::STATUS_SUBMITTED) {
                $submission->update([
                    'status' => Submission::STATUS_UNDER_REVIEW,
                ]);
            }

            return [
                'reviewer_submission' => $reviewerSubmission->fresh([
                    'submission.conference',
                    'submission.topic',
                    'reviewer',
                    'assignedBy',
                ]),
                'was_recently_created' => $wasRecentlyCreated,
            ];
        });

        $reviewerSubmission = $result['reviewer_submission'];

        if ($result['was_recently_created']) {
            $reviewer->notify(
                new ReviewerAssignedToSubmissionNotification($reviewerSubmission->submission),
            );
        }

        return $reviewerSubmission;
    }

    public function listAssignmentsForSubmission(Submission $submission): Collection
    {
        return $submission->reviewerSubmissions()
            ->with(['reviewer', 'review'])
            ->join('users', 'reviewer_submission.reviewer_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('reviewer_submission.*')
            ->get();
    }

    public function removeReviewerFromSubmission(Submission $submission, User $reviewer): void
    {
        $reviewerSubmission = ReviewerSubmission::query()
            ->with('review')
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $reviewer->id)
            ->first();

        if (! $reviewerSubmission) {
            return;
        }

        $status = $reviewerSubmission->review?->status;

        if (in_array($status, ['submitted', 'conflict_declared'], true)) {
            $message = $status === 'submitted'
                ? 'Impossible de retirer un reviewer dont la review a déjà été soumise.'
                : 'Impossible de retirer un reviewer ayant déclaré un conflit d’intérêt.';

            throw new ApiException($message, Response::HTTP_CONFLICT);
        }

        $reviewerSubmission->delete();
    }

    private function ensureReviewerRole(User $reviewer): void
    {
        if ($reviewer->role !== User::ROLE_REVIEWER) {
            throw new ApiException(
                'Seul un compte reviewer peut être assigné à une soumission.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    private function ensureReviewerIsCompetentForSubmissionTopic(Submission $submission, User $reviewer): void
    {
        $isAssignedToTopic = DB::table('reviewer_topic')
            ->where('topic_id', $submission->topic_id)
            ->where('reviewer_id', $reviewer->id)
            ->exists();

        if (! $isAssignedToTopic) {
            throw new ApiException(
                "Ce reviewer doit d'abord être assigné au topic de cette soumission avant de recevoir une relecture.",
                Response::HTTP_CONFLICT,
            );
        }
    }

    private function ensureSubmissionCanReceiveReviewer(Submission $submission): void
    {
        if (in_array($submission->status, [
            Submission::STATUS_ACCEPTED,
            Submission::STATUS_REJECTED,
            Submission::STATUS_CAMERA_READY_SUBMITTED,
        ], true)) {
            throw new ApiException(
                'Cette soumission ne peut plus recevoir de reviewer.',
                Response::HTTP_CONFLICT,
            );
        }
    }
}
