<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('add_school') ?? false;
    }

    public function rules(): array
    {
        return [
            'username'       => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'email'          => ['required', 'email', 'max:100', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'],
            'school_name'    => ['required', 'string', 'max:150'],
            'npsn'           => ['nullable', 'string', 'max:20', 'unique:schools,npsn'],
            'school_phone'   => ['nullable', 'string', 'max:20'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'pic_name'       => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required'    => 'Username wajib diisi.',
            'username.unique'      => 'Username sudah digunakan.',
            'email.required'       => 'Email wajib diisi.',
            'email.unique'         => 'Email sudah digunakan.',
            'password.required'    => 'Password wajib diisi.',
            'password.regex'       => 'Password harus mengandung setidaknya satu huruf besar, satu angka, dan satu simbol.',
            'school_name.required' => 'Nama sekolah wajib diisi.',
            'npsn.unique'          => 'NPSN sudah terdaftar.',
        ];
    }
}

