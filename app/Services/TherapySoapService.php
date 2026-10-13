<?php

namespace App\Services;

use App\Actions\TherapySoap\CreateTherapySoapAction;
use App\Actions\TherapySoap\ExportTherapySoapsAction;
use App\Actions\TherapySoap\GetTherapySoapExportFilterOptionsAction;
use App\Actions\TherapySoap\GetTherapySoapsAction;
use App\Models\TherapySoap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TherapySoapService
{
    public function __construct(
        private GetTherapySoapsAction                    $getTherapySoapsAction,
        private CreateTherapySoapAction                 $createTherapySoapAction,
        private GetTherapySoapExportFilterOptionsAction $getFilterOptionsAction,
        private ExportTherapySoapsAction                 $exportTherapySoapsAction,
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
