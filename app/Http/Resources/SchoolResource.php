<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'username'       => $this->user?->username,
            'email'          => $this->user?->email,
            'school_name'    => $this->school_name,
            'npsn'           => $this->npsn,
            'school_phone'   => $this->school_phone,
            'school_address' => $this->school_address,
            'pic_name'       => $this->pic_name,
            'students_count' => $this->whenCounted('children'),
            'created_at'     => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}

