<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexTherapySoapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        return $user->hasRole(['asesor', 'terapis']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'therapy_type' => ['nullable', 'string', 'in:okupasi,wicara,paedagog,fisio'],
            'search'       => ['nullable', 'string', 'max:100'],
            'date_range'   => ['nullable', 'string', 'in:today,tomorrow,this_week,this_month'],
            'date'         => ['nullable', 'date', 'date_format:Y-m-d'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'therapy_type.in'  => 'Jenis terapi tidak valid. Pilihan: okupasi, wicara, paedagog, fisio.',
            'date_range.in'    => 'Rentang tanggal tidak valid. Pilihan: today, tomorrow, this_week, this_month.',
            'date.date_format' => 'Format tanggal harus Y-m-d (contoh: 2025-01-01).',
            'per_page.integer' => 'Per halaman harus berupa angka.',
            'per_page.min'     => 'Per halaman minimal 1.',
            'per_page.max'     => 'Per halaman maksimal 100.',
        ];
    }
}
