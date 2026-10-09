<?php

namespace App\Services;

use App\Actions\TherapySoap\GetTherapySoapsAction;
use Illuminate\Pagination\LengthAwarePaginator;

class TherapySoapService
{
    public function __construct(
        private GetTherapySoapsAction $getTherapySoapsAction,
    ) {}

    /**
     * Retrieve paginated therapy SOAP notes with the given filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getTherapySoaps(array $filters = []): LengthAwarePaginator
    {
        return $this->getTherapySoapsAction->execute($filters);
    }
}
