<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'has_completed_assessment' => ['nullable', 'in:true,false'],
        ];
    }
}
