<?php

namespace App\Http\Requests\Chair;

use App\Models\Conference;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conference = $this->route('conference');
        $user = $this->user();

        return $conference instanceof Conference
            && $user
            && $user->role === User::ROLE_CHAIR
            && $conference->chair_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
