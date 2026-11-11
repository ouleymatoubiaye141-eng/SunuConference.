<?php

namespace App\Http\Requests\Reviewer;

use Illuminate\Foundation\Http\FormRequest;

class SaveReviewDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'originality_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'relevance_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'presentation_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'overall_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'public_comment' => ['nullable', 'string'],
            'private_comment' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string', 'in:accept,minor_revision,major_revision,reject'],
        ];
    }
}
