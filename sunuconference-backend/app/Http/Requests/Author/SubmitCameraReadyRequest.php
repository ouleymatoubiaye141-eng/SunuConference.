<?php

namespace App\Http\Requests\Author;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SubmitCameraReadyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_AUTHOR;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
