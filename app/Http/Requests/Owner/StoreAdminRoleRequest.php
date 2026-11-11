<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('owner') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'desc' => ['nullable', 'string', 'max:255'],
            'checkedIds' => ['nullable', 'array'],
            'checkedIds.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis admin wajib diisi.',
            'name.unique' => 'Nama jenis admin tersebut sudah terdaftar.',
        ];
    }
}

