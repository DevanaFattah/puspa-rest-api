<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexAssessmentsByStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->route('status'),
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:scheduled,completed'],
            'type'   => ['nullable', 'string', 'in:fisio,okupasi,wicara,paedagog'],
            'date'   => ['nullable', 'date', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status asesmen wajib diisi',
            'status.in'       => 'Jenis status tidak valid',
            'type.in'         => 'Tipe asesmen tidak valid',
            'date.date'       => 'Format tanggal tidak valid',
            'date.date_format' => 'Format tanggal harus Y-m-d',
            'search.max'      => 'Kata kunci pencarian maksimal 100 karakter',
        ];
    }
}
