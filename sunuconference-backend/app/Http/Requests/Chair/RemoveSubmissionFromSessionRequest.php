<?php

namespace App\Http\Requests\Chair;

use App\Models\Conference;
use App\Models\ConferenceSession;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RemoveSubmissionFromSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conference = $this->route('conference');
        $session = $this->route('session');
        $submission = $this->route('submission');
        $user = $this->user();

        return $conference instanceof Conference
            && $session instanceof ConferenceSession
            && $submission instanceof Submission
            && $user
            && $user->role === User::ROLE_CHAIR
            && $conference->chair_id === $user->id
            && $session->conference_id === $conference->id
            && $submission->conference_id === $conference->id;
    }

    public function rules(): array
    {
        return [];
    }
}
