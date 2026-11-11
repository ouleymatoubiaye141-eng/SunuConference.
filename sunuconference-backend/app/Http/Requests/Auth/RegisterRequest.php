<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in([User::ROLE_AUTHOR, User::ROLE_CHAIR])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
