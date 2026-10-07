<?php

namespace App\Services;

use App\Actions\TherapySession\GetTherapySessionDetailAction;
use App\Actions\TherapySession\GetTherapySessionsAction;
use App\Models\TherapySession;
use Illuminate\Pagination\LengthAwarePaginator;

class TherapySessionService
{
    public function __construct(
        private GetTherapySessionsAction       $getTherapySessionsAction,
        private GetTherapySessionDetailAction  $getTherapySessionDetailAction,
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

    /**
     * Load a single TherapySession with all detail relationships.
     */
    public function getTherapySessionDetail(TherapySession $session): TherapySession
    {
        return $this->getTherapySessionDetailAction->execute($session);
    }
}
