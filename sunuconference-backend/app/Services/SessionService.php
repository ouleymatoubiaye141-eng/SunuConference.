<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;

class SessionService
{
    public function listForConference(Conference $conference): Collection
    {
        return $conference->sessions()
            ->with(['submissions.author', 'submissions.topic'])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();
    }

    public function listPublishedForConference(Conference $conference): Collection
    {
        if ($conference->status !== Conference::STATUS_VALIDATED || ! $conference->program_published) {
            throw new ApiException(
                "Le programme de cette conférence n'a pas encore été publié.",
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->listForConference($conference);
    }

    public function create(Conference $conference, array $data): ConferenceSession
    {
        $this->ensureConferenceIsValidated($conference);

        return $conference->sessions()->create($data);
    }

    public function update(ConferenceSession $session, array $data): ConferenceSession
    {
        $session->update($data);

        return $session->refresh();
    }

    public function delete(ConferenceSession $session): void
    {
        $session->delete();
    }

    public function assignSubmission(Conference $conference, ConferenceSession $session, Submission $submission): Submission
    {
        if ($submission->conference_id !== $conference->id) {
            throw new ApiException("Cette soumission n'appartient pas à cette conférence.", Response::HTTP_FORBIDDEN);
        }

        if (! in_array($submission->status, [Submission::STATUS_ACCEPTED, Submission::STATUS_CAMERA_READY_SUBMITTED], true)) {
            throw new ApiException(
                'Seuls les articles acceptés peuvent être programmés dans une session.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $submission->update(['conference_session_id' => $session->id]);

        return $submission->refresh();
    }

    public function removeSubmission(ConferenceSession $session, Submission $submission): void
    {
        if ($submission->conference_session_id !== $session->id) {
            return;
        }

        $submission->update(['conference_session_id' => null]);
    }

    public function publish(Conference $conference): Conference
    {
        $this->ensureConferenceIsValidated($conference);

        $conference->update(['program_published' => true]);

        return $conference->refresh();
    }

    public function unpublish(Conference $conference): Conference
    {
        $conference->update(['program_published' => false]);

        return $conference->refresh();
    }

    private function ensureConferenceIsValidated(Conference $conference): void
    {
        if ($conference->status !== Conference::STATUS_VALIDATED) {
            throw new ApiException(
                "Impossible de gérer le programme : la conférence n'est pas encore validée.",
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }
}
