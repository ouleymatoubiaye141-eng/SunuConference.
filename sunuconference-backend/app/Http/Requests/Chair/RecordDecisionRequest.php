<?php

namespace App\Http\Requests\Chair;

use App\Models\Conference;
use App\Models\Decision;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conference = $this->route('conference');
        $submission = $this->route('submission');
        $user = $this->user();

        return $conference instanceof Conference
            && $submission instanceof Submission
            && $user instanceof User
            && $user->role === User::ROLE_CHAIR
            && $conference->chair_id === $user->id
            && $submission->conference_id === $conference->id;
    }

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                'string',
                Rule::in([
                    Decision::DECISION_ACCEPT,
                    Decision::DECISION_REJECT,
                    Decision::DECISION_REVISION_REQUIRED,
                ]),
            ],
            'comments' => [
                'required_if:decision,' . Decision::DECISION_REJECT . ',' . Decision::DECISION_REVISION_REQUIRED,
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'comments.required_if' => 'Le commentaire est obligatoire pour un rejet ou une demande de révision.',
        ];
    }
}
