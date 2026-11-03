<?php

namespace App\Rules;

use App\Models\Therapist;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveTherapistRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Therapist::where('id', $value)
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->exists();

        if (!$exists) {
            $fail('Terapis tidak ditemukan atau akunnya tidak aktif.');
        }
    }
}
