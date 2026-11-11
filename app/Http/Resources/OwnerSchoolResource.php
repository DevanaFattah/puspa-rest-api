<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnerSchoolResource extends JsonResource
{
    /**
     * Transform the resource into an array for Owner / Manager UI.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isVerified = ($this->verification_status === 'verified') || ($this->user?->is_active ?? false);
        $statusLabel = $isVerified ? 'Terverifikasi' : ($this->verification_status === 'rejected' ? 'Ditolak' : 'Tidak Terverifikasi');

        return [
            'id' => $this->id,
            'namaSekolah' => $this->school_name,
            'school_name' => $this->school_name,
            'npsn' => $this->npsn,
            'username' => $this->user?->username ?? '-',
            'email' => $this->user?->email ?? '-',
            'noTelepon' => $this->school_phone ?? '-',
            'school_phone' => $this->school_phone ?? '-',
            'alamat' => $this->school_address ?? '-',
            'school_address' => $this->school_address ?? '-',
            'pic_name' => $this->pic_name ?? '-',
            'status' => $statusLabel,
            'verification_status' => $this->verification_status ?? ($isVerified ? 'verified' : 'pending'),
            'tanggalPendaftaran' => $this->created_at?->format('d F Y H:i:s') ?? '-',
            'created_at' => $this->created_at?->format('d F Y H:i:s') ?? '-',
            'students_count' => $this->whenCounted('children'),
        ];
    }
}

