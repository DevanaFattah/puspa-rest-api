<?php

namespace App\Services;

use App\Actions\TherapySession\GetTherapySessionsAction;
use Illuminate\Pagination\LengthAwarePaginator;

class TherapySessionService
{
    public function __construct(
        private GetTherapySessionsAction $getTherapySessionsAction,
    ) {}

    /**
     * Retrieve paginated therapy sessions with the given filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getTherapySessions(array $filters = []): LengthAwarePaginator
    {
        return $this->getTherapySessionsAction->execute($filters);
    }
}
