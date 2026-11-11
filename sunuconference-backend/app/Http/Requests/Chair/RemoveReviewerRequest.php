<?php

namespace App\Http\Requests\Chair;

use App\Models\Conference;
use App\Models\ConferenceReviewer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RemoveReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conference = $this->route('conference');
        $reviewer = $this->route('reviewer');
        $user = $this->user();

        return $conference instanceof Conference
            && $reviewer instanceof User
            && $user
            && $user->role === User::ROLE_CHAIR
            && $conference->chair_id === $user->id
            && ConferenceReviewer::where('conference_id', $conference->id)
                ->where('user_id', $reviewer->id)
                ->exists();
    }

    public function rules(): array
    {
        return [];
    }
}
