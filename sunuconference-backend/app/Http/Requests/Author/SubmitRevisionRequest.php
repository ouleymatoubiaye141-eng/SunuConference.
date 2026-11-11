<?php

namespace App\Http\Requests\Author;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_AUTHOR;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'abstract' => ['sometimes', 'string'],
            'keywords' => ['sometimes', 'nullable', 'array'],
            'keywords.*' => ['string', 'max:50'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'response_to_reviewers' => ['required', 'string'],
        ];
    }
}
