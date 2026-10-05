<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_date' => ['required', 'date', 'after_or_equal:today'],
            'new_therapist_id' => ['nullable', 'string', 'exists:therapists,id'],
            'reason' => ['required', 'string', 'max:500'],
            'requested_by' => ['required', Rule::in(['children', 'therapist', 'guardian'])],
        ];
    }
}
