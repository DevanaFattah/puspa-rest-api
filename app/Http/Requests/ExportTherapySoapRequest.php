<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportTherapySoapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        return $user->hasRole(['admin', 'asesor', 'terapis']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'child_id'     => ['nullable'],
            'therapist_id' => ['nullable'],
            'therapy_type' => ['nullable', 'string', 'in:okupasi,wicara,paedagog,fisio'],
            'period_type'  => ['nullable', 'string', 'in:daily,weekly,monthly,custom'],
            'date'         => ['nullable', 'date', 'date_format:Y-m-d'],
            'week'         => ['nullable', 'integer', 'min:1', 'max:53'],
            'month'        => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'         => ['nullable', 'integer'],
            'date_from'    => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to'      => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_type.in'         => 'Pilihan period_type tidak valid. Pilihan: daily, weekly, monthly, custom.',
            'date.date_format'       => 'Format date harus Y-m-d (contoh: 2026-10-13).',
            'week.integer'           => 'Nomor minggu harus berupa angka.',
            'week.min'               => 'Nomor minggu minimal 1.',
            'week.max'               => 'Nomor minggu maksimal 53.',
            'month.integer'          => 'Bulan harus berupa angka (1-12).',
            'month.min'              => 'Bulan minimal 1.',
            'month.max'              => 'Bulan maksimal 12.',
            'year.integer'           => 'Tahun harus berupa 4 digit angka.',
            'date_from.date_format'  => 'Format date_from harus Y-m-d (contoh: 2026-01-01).',
            'date_to.date_format'    => 'Format date_to harus Y-m-d (contoh: 2026-01-01).',
            'date_to.after_or_equal' => 'date_to harus sama dengan atau setelah date_from.',
        ];
    }
}
