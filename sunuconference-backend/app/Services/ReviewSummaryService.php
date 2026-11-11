<?php

namespace App\Services;

use App\Models\Submission;
use Illuminate\Support\Collection;

class ReviewSummaryService
{
    public function buildSummary(Submission $submission): array
    {
        $assignments = $submission->reviewerSubmissions()
            ->with(['reviewer', 'review'])
            ->get();

        $submittedReviews = new Collection();
        $conflicts = new Collection();
        $pending = new Collection();

        foreach ($assignments as $assignment) {
            $status = $assignment->review?->status;

            if ($status === 'submitted') {
                $review = $assignment->review;
                // On casse le cycle avant d'attacher l'assignation à la review
                $assignment->unsetRelation('review');
                $review->setRelation('reviewerSubmission', $assignment);
                $submittedReviews->push($review);
            } elseif ($status === 'conflict_declared') {
                $conflicts->push($assignment);
            } else {
                $pending->push($assignment);
            }
        }

        return [
            'submission_id' => $submission->id,
            'submitted_reviews' => $submittedReviews,
            'average_scores' => $this->calculateAverages($submittedReviews),
            'recommendations_distribution' => $this->calculateDistribution($submittedReviews),
            'conflicts' => $conflicts,
            'pending_assignments' => $pending,
        ];
    }

    private function calculateAverages(Collection $reviews): ?array
    {
        if ($reviews->isEmpty()) {
            return [
                'originality' => null,
                'relevance' => null,
                'presentation' => null,
                'overall' => null,
            ];
        }

        return [
            'originality' => round($reviews->avg('originality_score'), 1),
            'relevance' => round($reviews->avg('relevance_score'), 1),
            'presentation' => round($reviews->avg('presentation_score'), 1),
            'overall' => round($reviews->avg('overall_score'), 1),
        ];
    }

    private function calculateDistribution(Collection $reviews): array
    {
        return $reviews->groupBy('recommendation')
            ->map(fn (Collection $group) => $group->count())
            ->toArray();
    }
}
