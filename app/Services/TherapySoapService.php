<?php

namespace App\Services;

use App\Actions\TherapySoap\CreateTherapySoapAction;
use App\Actions\TherapySoap\ExportTherapySoapsAction;
use App\Actions\TherapySoap\GetTherapySoapBySessionAction;
use App\Actions\TherapySoap\GetTherapySoapExportFilterOptionsAction;
use App\Models\TherapySession;
use App\Models\TherapySoap;
use Illuminate\Database\Eloquent\Collection;

class TherapySoapService
{
    public function __construct(
        private GetTherapySoapBySessionAction            $getTherapySoapBySessionAction,
        private CreateTherapySoapAction                 $createTherapySoapAction,
        private GetTherapySoapExportFilterOptionsAction $getFilterOptionsAction,
        private ExportTherapySoapsAction                 $exportTherapySoapsAction,
    )
    {
    }

    /**
     * Retrieve the therapy SOAP note for a specific therapy session.
     */
    public function getTherapySoapBySession(TherapySession $session): ?TherapySoap
    {
        return $this->getTherapySoapBySessionAction->execute($session);
    }

    /**
     * Retrieve paginated therapy SOAP notes with the given filters.
     *
     * @param  array<string, mixed>  $filters
     */

    public function createTherapySoap(array $data): TherapySoap
    {
        return $this->createTherapySoapAction->execute($data);
    }

    /**
     * Get filter options for SOAP export for a specific therapist.
     *
     * @param string $therapistId
     * @return array<string, mixed>
     */
    public function getExportFilterOptions(string $therapistId): array
    {
        return $this->getFilterOptionsAction->execute($therapistId);
    }

    /**
     * Retrieve all matching SOAP notes for export (unpaginated).
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, TherapySoap>
     */
    public function exportTherapySoaps(array $filters = []): Collection
    {
        return $this->exportTherapySoapsAction->execute($filters);
    }
}
