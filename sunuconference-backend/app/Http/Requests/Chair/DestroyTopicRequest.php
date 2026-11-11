<?php

namespace App\Http\Requests\Chair;

use App\Models\Conference;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DestroyTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conference = $this->route('conference');
        $topic = $this->route('topic');
        $user = $this->user();

        return $conference instanceof Conference
            && $topic instanceof Topic
            && $user
            && $user->role === User::ROLE_CHAIR
            && $conference->chair_id === $user->id
            && $topic->conference_id === $conference->id;
    }

    public function rules(): array
    {
        return [];
    }
}
