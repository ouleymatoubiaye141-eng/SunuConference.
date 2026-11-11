<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Conference;
use App\Models\User;
use App\Notifications\ConferenceRejectedNotification;
use App\Notifications\ConferenceValidatedNotification;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;

class ConferenceService
{
    public function listPublicValidated(): Collection
    {
        return Conference::query()
            ->where('status', Conference::STATUS_VALIDATED)
            ->orderByDesc('start_date')
            ->get();
    }

    public function showPublic(Conference $conference): Conference
    {
        if ($conference->status !== Conference::STATUS_VALIDATED) {
            throw new ApiException('Conf\u{00E9}rence introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $conference;
    }

    public function createForChair(User $chair, array $data): Conference
    {
        if ($chair->role !== User::ROLE_CHAIR) {
            throw new ApiException('Seul un PC Chair peut creer une conference.', Response::HTTP_FORBIDDEN);
        }

        $existingConference = $chair->chairedConferences()
            ->whereIn('status', [Conference::STATUS_PENDING, Conference::STATUS_VALIDATED])
            ->first();

        if ($existingConference) {
            throw new ApiException('Vous avez deja une conference en cours.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return Conference::create([
            ...$data,
            'chair_id' => $chair->id,
            'status' => Conference::STATUS_PENDING,
            'rejection_reason' => null,
            'program_published' => false,
        ]);
    }

    public function updateForChair(User $chair, Conference $conference, array $data): Conference
    {
        if ($conference->chair_id !== $chair->id) {
            throw new ApiException("Vous n'êtes pas le chair de cette conférence.", Response::HTTP_FORBIDDEN);
        }

        if ($conference->status === Conference::STATUS_PENDING) {
            throw new ApiException(
                'Impossible de modifier une conférence en attente de validation.',
                Response::HTTP_CONFLICT,
            );
        }

        $wasRejected = $conference->status === Conference::STATUS_REJECTED;

        $conference->update([
            ...$data,
            // Une conférence rejetée repasse en attente après modification ;
            // une conférence déjà validée le reste.
            'status' => $wasRejected ? Conference::STATUS_PENDING : $conference->status,
            'rejection_reason' => $wasRejected ? null : $conference->rejection_reason,
        ]);

        return $conference->refresh();
    }

    public function validate(Conference $conference): Conference
    {
        $conference->update([
            'status' => Conference::STATUS_VALIDATED,
            'rejection_reason' => null,
        ]);

        $conference = $conference->refresh();

        $conference->chair->notify(new ConferenceValidatedNotification($conference));

        return $conference;
    }

    public function reject(Conference $conference, string $reason): Conference
    {
        $conference->update([
            'status' => Conference::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        $conference = $conference->refresh();

        $conference->chair->notify(new ConferenceRejectedNotification($conference, $reason));

        return $conference;
    }
}
