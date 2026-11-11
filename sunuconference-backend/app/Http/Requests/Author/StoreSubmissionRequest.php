<?php

namespace App\Http\Requests\Author;

use App\Models\Conference;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conference = $this->route('conference');
        $topic = $this->route('topic');
        $user = $this->user();

        return $conference instanceof Conference
            && $topic instanceof Topic
            && $user
            && $user->role === User::ROLE_AUTHOR
            && $topic->conference_id === $conference->id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:50'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'co_authors' => ['nullable', 'array'],
            'co_authors.*.name' => ['required_with:co_authors', 'string', 'max:255'],
            'co_authors.*.email' => ['nullable', 'email'],
            'co_authors.*.affiliation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
