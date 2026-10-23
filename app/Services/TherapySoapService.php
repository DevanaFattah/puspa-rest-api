<?php

namespace App\Services;

use App\Actions\TherapySoap\CreateTherapySoapAction;
use App\Actions\TherapySoap\ExportTherapySoapsAction;
use App\Actions\TherapySoap\GetTherapySoapBySessionAction;
use App\Actions\TherapySoap\GetTherapySoapExportFilterOptionsAction;
use App\Models\TherapySession;
use App\Models\TherapySoap;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TherapySoapService
{
    public function __construct(
        private GetTherapySoapBySessionAction            $getTherapySoapBySessionAction,
        private CreateTherapySoapAction                 $createTherapySoapAction,
        private GetTherapySoapExportFilterOptionsAction $getFilterOptionsAction,
        private ExportTherapySoapsAction                 $exportTherapySoapsAction,
    ) {}

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
     * Get filter options for SOAP export for a therapist or admin.
     *
     * @param User $user
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getExportFilterOptions(User $user, array $filters = []): array
    {
        return $this->getFilterOptionsAction->execute($user, $filters);
    }

    /**
     * Generate DomPDF instance for SOAP export.
     *
     * @param array<string, mixed> $filters
     * @param \App\Models\User|null $user
     * @return array{pdf: \Barryvdh\DomPDF\PDF, view_type: string}
     */
    public function export(User $user, array $filters = []): array
    {
        return $this->exportTherapySoapsAction->execute($user, $filters);
    }
}
