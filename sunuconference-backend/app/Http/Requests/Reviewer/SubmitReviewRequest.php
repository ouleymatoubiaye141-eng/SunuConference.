<?php

namespace App\Http\Requests\Reviewer;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'originality_score' => ['required', 'integer', 'min:1', 'max:5'],
            'relevance_score' => ['required', 'integer', 'min:1', 'max:5'],
            'presentation_score' => ['required', 'integer', 'min:1', 'max:5'],
            'overall_score' => ['required', 'integer', 'min:1', 'max:5'],
            'public_comment' => ['required', 'string'],
            'private_comment' => ['nullable', 'string'],
            'recommendation' => ['required', 'string', 'in:accept,minor_revision,major_revision,reject'],
        ];
    }
}
