<?php

namespace App\Http\Requests\Chair;

use App\Models\Conference;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CreateReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conference = $this->route('conference');
        $user = $this->user();

        return $conference instanceof Conference
            && $user
            && $user->role === User::ROLE_CHAIR
            && $conference->chair_id === $user->id
            && $conference->status === Conference::STATUS_VALIDATED;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ];
    }
}
