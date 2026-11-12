<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAdminWithRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('owner') ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}

