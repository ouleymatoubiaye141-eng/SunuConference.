<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\AuthorResponse;
use App\Models\Conference;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SubmissionService
{
    public function listForConference(Conference $conference): Collection
    {
        return $conference->submissions()
            ->with(['topic', 'author', 'reviewerSubmissions.review'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function findForChair(Conference $conference, int $submissionId): Submission
    {
        return $conference->submissions()
            ->with(['topic', 'author', 'coAuthors', 'reviewerSubmissions.reviewer', 'reviewerSubmissions.review', 'decisions', 'authorResponses'])
            ->whereKey($submissionId)
            ->firstOrFail();
    }

    public function listForAuthor(User $author): Collection
    {
        return $author->submissions()
            ->with(['conference', 'topic'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function findForAuthor(User $author, int $submissionId): Submission
    {
        return $author->submissions()
            ->with(['conference', 'topic', 'coAuthors', 'publicReviews', 'authorResponses', 'latestDecision'])
            ->whereKey($submissionId)
            ->firstOrFail();
    }

    public function create(User $author, Conference $conference, Topic $topic, array $data, UploadedFile $file): Submission
    {
        $this->ensureConferenceAcceptsSubmissions($conference);
        $this->ensureTopicBelongsToConference($conference, $topic);
        $this->ensureSubmissionDeadlineIsNotExceeded($conference);

        $storedPath = null;

        try {
            return DB::transaction(function () use ($author, $conference, $topic, $data, $file, &$storedPath): Submission {
                $submission = Submission::create([
                    'conference_id' => $conference->id,
                    'topic_id' => $topic->id,
                    'author_id' => $author->id,
                    'title' => $data['title'],
                    'abstract' => $data['abstract'],
                    'keywords' => $data['keywords'] ?? null,
                    'initial_file_path' => '__pending__',
                    'status' => Submission::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                ]);

                $storedPath = $file->storeAs(
                    "submissions/{$conference->id}/{$submission->id}",
                    'initial.pdf',
                    'local',
                );

                if ($storedPath === false) {
                    throw new ApiException(
                        "Le fichier n'a pas pu être enregistré.",
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                    );
                }

                $submission->update([
                    'initial_file_path' => $storedPath,
                ]);

                foreach ($data['co_authors'] ?? [] as $position => $coAuthor) {
                    $submission->coAuthors()->create([
                        'name' => $coAuthor['name'],
                        'email' => $coAuthor['email'] ?? null,
                        'affiliation' => $coAuthor['affiliation'] ?? null,
                        'position' => $position,
                    ]);
                }

                return $submission->fresh(['conference', 'topic', 'coAuthors']);
            });
        } catch (Throwable $e) {
            if ($storedPath && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $e;
        }
    }

    public function submitRevision(User $author, int $submissionId, array $data, UploadedFile $file): Submission
    {
        $submission = $author->submissions()
            ->with('conference')
            ->whereKey($submissionId)
            ->firstOrFail();

        $this->ensureSubmissionAcceptsRevision($submission);
        $this->ensureRevisionDeadlineIsNotExceeded($submission->conference);

        $storedPath = null;

        try {
            return DB::transaction(function () use ($author, $submission, $data, $file, &$storedPath): Submission {
                $storedPath = $file->storeAs(
                    "submissions/{$submission->conference_id}/{$submission->id}",
                    'revised.pdf',
                    'local',
                );

                if ($storedPath === false) {
                    throw new ApiException(
                        "Le fichier révisé n'a pas pu être enregistré.",
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                    );
                }

                $updates = [
                    'revised_file_path' => $storedPath,
                    'status' => Submission::STATUS_REVISED_SUBMITTED,
                    'revised_at' => now(),
                ];

                foreach (['title', 'abstract', 'keywords'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $updates[$field] = $data[$field];
                    }
                }

                $submission->update($updates);

                $submission->authorResponses()->create([
                    'author_id' => $author->id,
                    'type' => AuthorResponse::TYPE_REVISION_RESPONSE,
                    'content' => $data['response_to_reviewers'],
                ]);

                return $submission->fresh(['conference', 'topic', 'coAuthors', 'authorResponses']);
            });
        } catch (Throwable $e) {
            if ($storedPath && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $e;
        }
    }

    public function submitCameraReady(User $author, int $submissionId, UploadedFile $file): Submission
    {
        $submission = $author->submissions()
            ->with('conference')
            ->whereKey($submissionId)
            ->firstOrFail();

        $this->ensureSubmissionAcceptsCameraReady($submission);
        $this->ensureCameraReadyDeadlineIsNotExceeded($submission->conference);

        $storedPath = null;

        try {
            return DB::transaction(function () use ($submission, $file, &$storedPath): Submission {
                $storedPath = $file->storeAs(
                    "submissions/{$submission->conference_id}/{$submission->id}",
                    'camera_ready.pdf',
                    'local',
                );

                if ($storedPath === false) {
                    throw new ApiException(
                        "Le fichier camera-ready n'a pas pu être enregistré.",
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                    );
                }

                $submission->update([
                    'camera_ready_file_path' => $storedPath,
                    'status' => Submission::STATUS_CAMERA_READY_SUBMITTED,
                    'camera_ready_submitted_at' => now(),
                ]);

                return $submission->fresh(['conference', 'topic', 'coAuthors']);
            });
        } catch (Throwable $e) {
            if ($storedPath && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $e;
        }
    }

    public function submitRebuttal(User $author, int $submissionId, string $content): AuthorResponse
    {
        $submission = $author->submissions()
            ->with('reviewerSubmissions.review')
            ->whereKey($submissionId)
            ->firstOrFail();

        $this->ensureSubmissionAcceptsRebuttal($submission);
        $this->ensureAllNonConflictingReviewsAreCompleted($submission);

        return $submission->authorResponses()->create([
            'author_id' => $author->id,
            'type' => AuthorResponse::TYPE_PRE_DECISION,
            'content' => $content,
        ]);
    }

    /**
     * Résout le chemin disque et le nom de fichier à servir pour un
     * téléchargement de soumission, après vérification que l'utilisateur
     * (auteur, reviewer assigné, ou chair de la conférence) y a droit.
     *
     * @return array{path: string, filename: string}
     */
    public function resolveFileForDownload(User $user, Submission $submission, string $type): array
    {
        $column = match ($type) {
            'initial' => 'initial_file_path',
            'revised' => 'revised_file_path',
            'camera-ready' => 'camera_ready_file_path',
            default => throw new ApiException('Type de fichier invalide.', Response::HTTP_NOT_FOUND),
        };

        $this->ensureUserCanAccessSubmissionFiles($user, $submission);

        $path = $submission->{$column};

        if (! $path || ! Storage::disk('local')->exists($path)) {
            throw new ApiException('Aucun fichier disponible pour cette version.', Response::HTTP_NOT_FOUND);
        }

        return [
            'path' => $path,
            'filename' => "{$submission->id}-{$type}.pdf",
        ];
    }

    private function ensureUserCanAccessSubmissionFiles(User $user, Submission $submission): void
    {
        if ($submission->author_id === $user->id) {
            return;
        }

        if ($user->role === User::ROLE_CHAIR && $submission->conference->chair_id === $user->id) {
            return;
        }

        if ($user->role === User::ROLE_REVIEWER) {
            $isAssigned = $submission->reviewerSubmissions()
                ->where('reviewer_id', $user->id)
                ->exists();

            if ($isAssigned) {
                return;
            }
        }

        throw new ApiException("Vous n'avez pas accès aux fichiers de cette soumission.", Response::HTTP_FORBIDDEN);
    }

    private function ensureConferenceAcceptsSubmissions(Conference $conference): void
    {
        if ($conference->status !== Conference::STATUS_VALIDATED) {
            throw new ApiException(
                "Cette conférence n'accepte pas de soumissions.",
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    private function ensureTopicBelongsToConference(Conference $conference, Topic $topic): void
    {
        if ($topic->conference_id !== $conference->id) {
            throw new ApiException(
                'Topic introuvable pour cette conférence.',
                Response::HTTP_NOT_FOUND,
            );
        }
    }

    private function ensureSubmissionDeadlineIsNotExceeded(Conference $conference): void
    {
        if (now()->greaterThan($conference->submission_deadline)) {
            throw new ApiException(
                'La date limite de soumission pour cette conférence est dépassée.',
                Response::HTTP_CONFLICT,
            );
        }
    }

    private function ensureSubmissionAcceptsRevision(Submission $submission): void
    {
        if ($submission->status !== Submission::STATUS_REVISION_REQUIRED) {
            throw new ApiException(
                'Cette soumission ne peut pas recevoir de révision.',
                Response::HTTP_CONFLICT,
            );
        }
    }

    private function ensureRevisionDeadlineIsNotExceeded(Conference $conference): void
    {
        if ($conference->revision_deadline === null) {
            throw new ApiException(
                'La date limite de révision n’est pas configurée pour cette conférence.',
                Response::HTTP_CONFLICT,
            );
        }

        if (now()->greaterThan($conference->revision_deadline)) {
            throw new ApiException(
                'La date limite de révision pour cette conférence est dépassée.',
                Response::HTTP_CONFLICT,
            );
        }
    }

    private function ensureSubmissionAcceptsCameraReady(Submission $submission): void
    {
        if ($submission->status !== Submission::STATUS_ACCEPTED) {
            throw new ApiException(
                'Cette soumission ne peut pas recevoir de version camera-ready.',
                Response::HTTP_CONFLICT,
            );
        }
    }

    private function ensureCameraReadyDeadlineIsNotExceeded(Conference $conference): void
    {
        if ($conference->camera_ready_deadline === null) {
            throw new ApiException(
                'La date limite de dépôt camera-ready n’est pas configurée pour cette conférence.',
                Response::HTTP_CONFLICT,
            );
        }

        if (now()->greaterThan($conference->camera_ready_deadline)) {
            throw new ApiException(
                'La date limite de dépôt camera-ready pour cette conférence est dépassée.',
                Response::HTTP_CONFLICT,
            );
        }
    }

    private function ensureSubmissionAcceptsRebuttal(Submission $submission): void
    {
        if ($submission->status !== Submission::STATUS_UNDER_REVIEW) {
            throw new ApiException(
                'Cette soumission ne peut pas recevoir de rebuttal pré-décision.',
                Response::HTTP_CONFLICT,
            );
        }
    }

    private function ensureAllNonConflictingReviewsAreCompleted(Submission $submission): void
    {
        $missing = $submission->reviewerSubmissions
            ->filter(function ($assignment): bool {
                $status = $assignment->review?->status;

                return $status !== 'submitted' && $status !== 'conflict_declared';
            })
            ->count();

        if ($missing > 0) {
            $message = $missing === 1
                ? "1 rapport d'évaluation non conflictuel est encore attendu."
                : "{$missing} rapports d'évaluation non conflictuels sont encore attendus.";

            throw new ApiException(
                $message,
                Response::HTTP_CONFLICT,
            );
        }
    }
}
