<?php

namespace App\Services;

use App\Actions\TherapySoap\CreateTherapySoapAction;
use App\Actions\TherapySoap\GetTherapySoapsAction;
use App\Models\TherapySoap;
use Illuminate\Pagination\LengthAwarePaginator;

class TherapySoapService
{
    public function __construct(
        private GetTherapySoapsAction $getTherapySoapsAction,
        private CreateTherapySoapAction $createTherapySoapAction,
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

    public function createTherapySoap(array $data): TherapySoap
    {
        return $this->createTherapySoapAction->execute($data);
    }
}
