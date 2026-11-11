<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Conference;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;

class TopicService
{
    public function listForConference(Conference $conference): Collection
    {
        return $conference->topics()->orderBy('name')->get();
    }

    public function listPublicForConference(Conference $conference): Collection
    {
        $this->ensureConferenceIsValidated(
            $conference,
            'Conférence introuvable.',
            Response::HTTP_NOT_FOUND,
        );

        return $this->listForConference($conference);
    }

    public function create(Conference $conference, array $data): Topic
    {
        $this->ensureConferenceIsValidated($conference);

        return $conference->topics()->create($data);
    }

    public function update(Topic $topic, array $data): Topic
    {
        $topic->update($data);

        return $topic->refresh();
    }

    public function delete(Topic $topic): void
    {
        $reviewersCount = $topic->reviewers()->count();

        if ($reviewersCount > 0) {
            throw new ApiException(
                "Impossible de supprimer ce topic : {$reviewersCount} relecteur(s) y sont déjà assignés. Retirez d'abord les assignations avant de supprimer ce topic.",
                Response::HTTP_CONFLICT,
            );
        }

        $topic->delete();
    }

    private function ensureConferenceIsValidated(
        Conference $conference,
        string $message = "Impossible de créer un topic : la conférence n'est pas encore validée.",
        int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
    ): void {
        if ($conference->status !== Conference::STATUS_VALIDATED) {
            throw new ApiException($message, $statusCode);
        }
    }
}
