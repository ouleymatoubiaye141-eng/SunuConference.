<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Decision;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionDecisionRecordedNotification;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ChairDecisionService
{
    /**
     * Enregistre une décision pour une soumission.
     *
     * @return array{decision: Decision, warnings: array<string>}
     */
    public function recordDecision(
        Submission $submission,
        User $chair,
        string $decision,
        ?string $comments
    ): array {
        $this->validateState($submission);

        $result = DB::transaction(function () use ($submission, $chair, $decision, $comments) {
            $newDecision = Decision::create([
                'submission_id' => $submission->id,
                'chair_id' => $chair->id,
                'decision' => $decision,
                'comments' => $comments,
                'decided_at' => now(),
            ]);

            $submission->update([
                'status' => $this->mapDecisionToStatus($decision),
            ]);

            return [
                'decision' => $newDecision,
                'warnings' => $this->calculateWarnings($submission),
            ];
        });

        $submission->author->notify(
            new SubmissionDecisionRecordedNotification($submission, $result['decision']),
        );

        return $result;
    }

    /**
     * Vérifie que la soumission est dans un état permettant une décision.
     */
    private function validateState(Submission $submission): void
    {
        // 1. Statut de la soumission
        $allowedStatuses = [
            Submission::STATUS_UNDER_REVIEW,
            Submission::STATUS_REVISED_SUBMITTED,
        ];

        if (!in_array($submission->status, $allowedStatuses, true)) {
            throw new ApiException(
                "Impossible de prendre une décision : la soumission est dans l'état '{$submission->status}'.",
                Response::HTTP_CONFLICT
            );
        }

        // 2. Au moins une review soumise
        $hasSubmittedReview = $submission->reviewerSubmissions()
            ->whereHas('review', function ($query) {
                $query->whereNotNull('submitted_at');
            })
            ->exists();

        if (!$hasSubmittedReview) {
            throw new ApiException(
                "Impossible de prendre une décision sans au moins un rapport d'évaluation soumis.",
                Response::HTTP_CONFLICT
            );
        }
    }

    /**
     * Mappe la décision vers le nouveau statut de la soumission.
     */
    private function mapDecisionToStatus(string $decision): string
    {
        return match ($decision) {
            Decision::DECISION_ACCEPT => Submission::STATUS_ACCEPTED,
            Decision::DECISION_REJECT => Submission::STATUS_REJECTED,
            Decision::DECISION_REVISION_REQUIRED => Submission::STATUS_REVISION_REQUIRED,
            default => throw new \InvalidArgumentException("Décision inconnue : {$decision}"),
        };
    }

    /**
     * Calcule les avertissements pour les reviews manquantes.
     *
     * @return array<string>
     */
    private function calculateWarnings(Submission $submission): array
    {
        $assignments = $submission->reviewerSubmissions()
            ->with('review')
            ->get();

        $total = $assignments->count();
        
        // On ne compte comme "manquantes" que celles qui ne sont ni soumises ni en conflit
        $missing = $assignments->filter(function ($assignment) {
            $status = $assignment->review?->status;
            return $status !== 'submitted' && $status !== 'conflict_declared';
        })->count();

        if ($missing > 0) {
            return [
                "{$missing} reviewer(s) sur {$total} n'ont pas encore soumis leur rapport."
            ];
        }

        return [];
    }
}
