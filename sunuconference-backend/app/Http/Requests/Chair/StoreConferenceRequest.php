<?php

namespace App\Http\Requests\Chair;

use Illuminate\Foundation\Http\FormRequest;

class StoreConferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'acronym' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'call_for_papers' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'submission_deadline' => ['required', 'date', 'before_or_equal:start_date'],
            'review_deadline' => ['required', 'date', 'after_or_equal:submission_deadline', 'before_or_equal:start_date'],
            'revision_deadline' => ['required', 'date', 'after_or_equal:review_deadline', 'before_or_equal:start_date'],
            'camera_ready_deadline' => ['required', 'date', 'after_or_equal:revision_deadline', 'before_or_equal:start_date'],
        ];
    }
}
