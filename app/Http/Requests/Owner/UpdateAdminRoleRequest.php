<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['owner', 'manager']) ?? false;
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'desc' => ['nullable', 'string', 'max:255'],
            'checkedIds' => ['sometimes', 'array'],
            'checkedIds.*' => ['string'],
        ];
    }
}
