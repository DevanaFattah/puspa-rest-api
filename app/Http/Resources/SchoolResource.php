<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isVerified = ($this->verification_status === 'verified') || ($this->user?->is_active ?? false);
        $statusLabel = $isVerified ? 'Terverifikasi' : ($this->verification_status === 'rejected' ? 'Ditolak' : 'Tidak Terverifikasi');

        $jenjangMap = [
            'PAUD' => 'Pendidikan Anak Usia Dini',
            'SD'   => 'Sekolah Dasar',
            'SMP'  => 'Sekolah Menengah Pertama',
            'SMA'  => 'Sekolah Menengah Atas',
            'SMK'  => 'Sekolah Menengah Kejuruan',
        ];

        $rawJenjang = strtoupper((string) ($this->jenjang ?? 'SD'));
        $jenjangKode = isset($jenjangMap[$rawJenjang]) ? $rawJenjang : ($this->jenjang ?: 'SD');
        $jenjangLabel = $jenjangMap[$jenjangKode] ?? ($this->jenjang ?: 'Sekolah Dasar');

        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'username'       => $this->user?->username ?? '-',
            'email'          => $this->user?->email ?? '-',
            'school_name'    => $this->school_name,
            'namaSekolah'    => $this->school_name,
            'npsn'           => $this->npsn,
            'jenjang'        => $this->jenjang,
            'jenjangKode'    => $jenjangKode,
            'jenjangLabel'   => $jenjangLabel,
            'school_phone'   => $this->school_phone,
            'noTelepon'      => $this->school_phone ?? '-',
            'school_address' => $this->school_address,
            'alamat'         => $this->school_address ?? '-',
            'pic_name'       => $this->pic_name,
            'status'         => $statusLabel,
            'verification_status' => $this->verification_status ?? ($isVerified ? 'verified' : 'pending'),
            'students_count' => $this->whenCounted('children'),
            'created_at'     => $this->created_at?->format('Y-m-d H:i'),
            'tanggalPendaftaran' => $this->created_at?->format('d F Y H:i:s') ?? '-',
        ];
    }
}

